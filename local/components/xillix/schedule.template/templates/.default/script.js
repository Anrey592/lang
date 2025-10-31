(function () {
    'use strict';

    if (typeof BX.Xillix === 'undefined') {
        BX.Xillix = {};
    }

    BX.Xillix.ScheduleTemplate = function (config) {
        this.config = config || {};
        this.teacherId = config.teacherId;
        this.dayOnlyMode = config.defaultDayOnly !== false;
        this.selectedSlots = new Set(); // Храним выбранные слоты в формате "day-hour"
        this.templateData = [];

        this.init();
    };

    BX.Xillix.ScheduleTemplate.prototype = {
        init: function () {
            this.timezone = this.config.currentTimezone || 'Europe/Moscow';
            this.bindEvents();
            this.generateTimeSlots();
            this.loadTemplate();
        },

        bindEvents: function () {
            // Checkbox "Только день"
            BX.bind(document.getElementById('dayOnlyToggleSchedule'), 'change', BX.proxy(async function (e) {
                this.dayOnlyMode = e.target.checked;
                this.generateTimeSlots();
                await this.loadTemplate();
            }, this));

            // Часовой пояс
            BX.bind(document.getElementById('teacher-timezone-template'), 'change', BX.proxy(async (e) => {
                await this.changeTimezone(e.target.value);
            }, this));

            // Кнопки действий
            BX.bind(document.getElementById('saveTemplate'), 'click', BX.proxy(async function () {
                await this.saveTemplate();
            }, this));

            BX.bind(document.getElementById('clearTemplate'), 'click', BX.proxy(async function () {
                await this.clearTemplate();
            }, this));
        },

        generateTimeSlots: function () {
            const scheduleBody = document.getElementById('scheduleTemplateBody');
            scheduleBody.innerHTML = '';

            // КОГДА ЧЕКБОКС ОТМЕЧЕН: только день (8:00-22:00)
            // КОГДА ЧЕКБОКС НЕ ОТМЕЧЕН: полные сутки (0:00-24:00)
            const startHour = this.dayOnlyMode ? 8 : 0;
            const endHour = this.dayOnlyMode ? 22 : 24;

            for (let hour = startHour; hour < endHour; hour++) { // обратите внимание: < вместо <=
                const row = document.createElement('tr');
                row.className = 'time-row';
                row.setAttribute('data-hour', hour);

                // Ячейка с временем
                const timeCell = document.createElement('td');
                timeCell.className = 'time-label';
                timeCell.textContent = hour.toString().padStart(2, '0') + ':00';
                row.appendChild(timeCell);

                // Ячейки для каждого дня недели
                for (let day = 1; day <= 7; day++) {
                    const slotCell = document.createElement('td');
                    slotCell.className = 'time-slot';
                    slotCell.setAttribute('data-day', day);
                    slotCell.setAttribute('data-hour', hour);
                    slotCell.setAttribute('data-slot-key', `${day}-${hour}`);

                    // Обработчики для выделения
                    BX.bind(slotCell, 'click', BX.proxy(this.handleSlotClick, this));

                    row.appendChild(slotCell);
                }

                scheduleBody.appendChild(row);
            }

            // Добавляем обработчики для заголовков дней
            const dayHeaders = document.querySelectorAll('.day-column');
            dayHeaders.forEach(header => {
                BX.bind(header, 'click', BX.proxy(this.handleDayHeaderClick, this));
            });

            // Добавляем обработчики для строк времени
            const timeLabels = document.querySelectorAll('.time-label');
            timeLabels.forEach(label => {
                BX.bind(label, 'click', BX.proxy(this.handleTimeLabelClick, this));
            });
        },

        handleSlotClick: function (e) {
            const slot = e.currentTarget;
            const slotKey = slot.getAttribute('data-slot-key');
            const day = slot.getAttribute('data-day');
            const hour = slot.getAttribute('data-hour');

            this.toggleSlotSelection(day, hour, slotKey);
        },

        handleSlotMouseEnter: function (e) {
            if (this.isSelecting) {
                const slot = e.currentTarget;
                const slotKey = slot.getAttribute('data-slot-key');
                const day = slot.getAttribute('data-day');
                const hour = slot.getAttribute('data-hour');

                if (!this.selectedSlots.has(slotKey)) {
                    this.selectSlot(day, hour, slotKey);
                }
            }
        },

        handleSlotMouseLeave: function (e) {
            // Можно добавить логику для drag selection
        },

        handleDayHeaderClick: function (e) {
            const header = e.currentTarget;
            const day = header.getAttribute('data-day');

            this.toggleDaySelection(day);
        },

        handleTimeLabelClick: function (e) {
            const label = e.currentTarget;
            const hour = label.parentElement.getAttribute('data-hour');

            this.toggleTimeSelection(hour);
        },

        toggleSlotSelection: function (day, hour, slotKey) {
            if (this.selectedSlots.has(slotKey)) {
                this.deselectSlot(day, hour, slotKey);
            } else {
                this.selectSlot(day, hour, slotKey);
            }
        },

        selectSlot: function (day, hour, slotKey) {
            this.selectedSlots.add(slotKey);
            const slot = document.querySelector(`[data-slot-key="${slotKey}"]`);
            if (slot) {
                slot.classList.add('selected');
            }
        },

        deselectSlot: function (day, hour, slotKey) {
            this.selectedSlots.delete(slotKey);
            const slot = document.querySelector(`[data-slot-key="${slotKey}"]`);
            if (slot) {
                slot.classList.remove('selected');
            }
        },

        toggleDaySelection: function (day) {
            const startHour = this.dayOnlyMode ? 8 : 0;
            const endHour = this.dayOnlyMode ? 22 : 24;

            // Проверяем, все ли слоты дня выбраны
            let allSelected = true;
            for (let hour = startHour; hour <= endHour; hour++) {
                const slotKey = `${day}-${hour}`;
                if (!this.selectedSlots.has(slotKey)) {
                    allSelected = false;
                    break;
                }
            }

            // Выбираем или снимаем выбор
            for (let hour = startHour; hour <= endHour; hour++) {
                const slotKey = `${day}-${hour}`;
                if (allSelected) {
                    this.deselectSlot(day, hour, slotKey);
                } else {
                    this.selectSlot(day, hour, slotKey);
                }
            }
        },

        toggleTimeSelection: function (hour) {
            const startHour = this.dayOnlyMode ? 8 : 0;
            const endHour = this.dayOnlyMode ? 22 : 24;

            if (hour < startHour || hour > endHour) {
                return; // не обрабатываем часы вне текущего диапазона
            }

            // Проверяем, все ли слоты времени выбраны
            let allSelected = true;
            for (let day = 1; day <= 7; day++) {
                const slotKey = `${day}-${hour}`;
                if (!this.selectedSlots.has(slotKey)) {
                    allSelected = false;
                    break;
                }
            }

            // Выбираем или снимаем выбор
            for (let day = 1; day <= 7; day++) {
                const slotKey = `${day}-${hour}`;
                if (allSelected) {
                    this.deselectSlot(day, hour, slotKey);
                } else {
                    this.selectSlot(day, hour, slotKey);
                }
            }
        },

        loadTemplate: async function () {
            try {
                this.showLoader();

                const response = await BX.ajax.runComponentAction('xillix:schedule.template', 'getTemplate', {
                    mode: 'class',
                    data: {
                        dayOnly: this.dayOnlyMode
                    }
                });

                this.hideLoader();

                if (response.data && response.data.success) {
                    this.templateData = response.data.template || [];
                    // Ждем обновления DOM перед рендером
                    await this.delay(10);
                    await this.renderTemplate();
                } else {
                    const errorMsg = response.data?.error || 'Unknown error';
                    this.showError('Ошибка загрузки: ' + errorMsg);
                }
            } catch (error) {
                this.hideLoader();
                console.error('Load template error:', error);
                this.showError('Ошибка загрузки шаблона');
            }
        },

        showLoader: function () {
            const container = document.querySelector('.xillix-schedule-template');
            if (container) {
                container.classList.add('loader-table');
            }
        },

        hideLoader: function () {
            const container = document.querySelector('.xillix-schedule-template');
            if (container) {
                container.classList.remove('loader-table');
            }
        },

        renderTemplate: async function () {
            this.selectedSlots.clear();

            // Ждем пока DOM обновится
            await this.delay(10);

            const allSlots = document.querySelectorAll('.time-slot');
            allSlots.forEach(slot => {
                slot.classList.remove('selected');
            });

            // Восстанавливаем выделения
            await this.restoreSelections();
        },

        restoreSelections: async function () {
            for (const slot of this.templateData) {
                const startHour = parseInt(slot.UF_START_TIME.split(':')[0]);
                const endHour = parseInt(slot.UF_END_TIME.split(':')[0]);
                const day = parseInt(slot.UF_DAY_OF_WEEK);

                for (let hour = startHour; hour < endHour; hour++) {
                    const slotKey = `${day}-${hour}`;

                    // Ждем немного между операциями для стабильности
                    await this.delay(0);

                    const slotElement = document.querySelector(`[data-slot-key="${slotKey}"]`);
                    if (slotElement) {
                        this.selectedSlots.add(slotKey);
                        slotElement.classList.add('selected');
                    }
                }
            }
        },

        saveTemplate: async function () {
            try {
                const groupedSlots = this.groupSelectedSlots();
                this.showLoader();

                const response = await BX.ajax.runComponentAction('xillix:schedule.template', 'saveTemplate', {
                    mode: 'class',
                    data: {
                        slots: groupedSlots,
                        timezone: this.timezone
                    }
                });

                this.hideLoader();

                if (response.data?.success) {
                    this.showMessage(this.config.messages.SAVE_SUCCESS || 'Шаблон сохранен');
                    await this.loadTemplate(); // Перезагружаем для подтверждения
                } else {
                    const errorMsg = response.data?.errors?.join(', ') || response.data?.error || 'Неизвестная ошибка';
                    this.showError('Ошибка сохранения: ' + errorMsg);
                }
            } catch (error) {
                this.hideLoader();
                console.error('Save template error:', error);
                this.showError('Ошибка сохранения шаблона');
            }
        },

        clearTemplate: async function () {
            if (!confirm(this.config.messages.CONFIRM_CLEAR || 'Вы уверены, что хотите очистить шаблон расписания?')) {
                return;
            }

            try {
                const response = await BX.ajax.runComponentAction('xillix:schedule.template', 'clearTemplate', {
                    mode: 'class'
                });

                if (response.data?.success) {
                    this.showMessage(this.config.messages.CLEAR_SUCCESS || 'Шаблон очищен');
                    this.selectedSlots.clear();
                    await this.renderTemplate();
                } else {
                    const errorMsg = response.data?.error || 'Unknown error';
                    this.showError(this.config.messages.ERROR || 'Ошибка: ' + errorMsg);
                }
            } catch (error) {
                console.error('Clear template error:', error);
                this.showError(this.config.messages.ERROR || 'Ошибка очистки шаблона');
            }
        },

        changeTimezone: async function (newTimezone) {
            try {
                this.timezone = newTimezone;

                const response = await BX.ajax.runComponentAction('xillix:schedule.template', 'saveTimezone', {
                    mode: 'class',
                    data: {
                        timezone: newTimezone
                    }
                });

                if (response.data?.success) {
                    this.showMessage('Часовой пояс обновлен');
                    await this.loadTemplate(); // Перезагружаем данные
                } else {
                    const errorMsg = response.data?.error || 'Unknown error';
                    this.showError('Ошибка сохранения: ' + errorMsg);
                }
            } catch (error) {
                console.error('Timezone save error:', error);
                this.showError('Ошибка сохранения часового пояса');
            }
        },

        delay: function (ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        waitForElement: async function (selector, timeout = 5000) {
            const startTime = Date.now();

            while (Date.now() - startTime < timeout) {
                const element = document.querySelector(selector);
                if (element) return element;
                await this.delay(50);
            }

            throw new Error(`Element ${selector} not found within ${timeout}ms`);
        },

        groupSelectedSlots: function () {
            const slotsByDay = {};

            // Группируем по дням
            this.selectedSlots.forEach(slotKey => {
                const [day, hour] = slotKey.split('-').map(Number);

                const startHour = this.dayOnlyMode ? 8 : 0;
                const endHour = this.dayOnlyMode ? 22 : 24;

                if (hour >= startHour && hour <= endHour) {
                    if (!slotsByDay[day]) {
                        slotsByDay[day] = [];
                    }
                    slotsByDay[day].push(hour);
                }
            });

            // Объединяем смежные часы в интервалы
            const result = [];

            for (const day in slotsByDay) {
                const hours = slotsByDay[day].sort((a, b) => a - b);
                let startHour = hours[0];
                let endHour = hours[0];

                for (let i = 1; i < hours.length; i++) {
                    if (hours[i] === endHour + 1) {
                        endHour = hours[i];
                    } else {
                        // Сохраняем текущий интервал
                        result.push({
                            day: parseInt(day),
                            startTime: startHour.toString().padStart(2, '0') + ':00',
                            endTime: (endHour + 1).toString().padStart(2, '0') + ':00'
                        });

                        startHour = hours[i];
                        endHour = hours[i];
                    }
                }

                // Сохраняем последний интервал
                result.push({
                    day: parseInt(day),
                    startTime: startHour.toString().padStart(2, '0') + ':00',
                    endTime: (endHour + 1).toString().padStart(2, '0') + ':00'
                });
            }

            return result;
        },

        showMessage: function (message) {
            this.showNotification(message, 'success');
        },

        showError: function (message) {
            this.showNotification(message, 'error');
        },

        showNotification: function (message, type) {
            // Проверяем доступность Bitrix уведомлений
            if (typeof BX.UI !== 'undefined' && typeof BX.UI.Notification !== 'undefined') {
                BX.UI.Notification.Center.notify({
                    content: message,
                    autoHideDelay: type === 'error' ? 5000 : 3000,
                    type: type
                });
            } else {
                // Fallback - кастомные уведомления
                this.showCustomNotification(message, type);
            }
        },

        showCustomNotification: function (message, type) {
            // Создаем кастомное уведомление
            const notification = document.createElement('div');
            notification.className = `xillix-notification xillix-notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            z-index: 10000;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight var(--time-trans) ease;
            ${type === 'success' ? 'background: #28a745;' : 'background: #dc3545;'}
        `;

            document.body.appendChild(notification);

            // Автоматически скрываем через 3 секунды
            setTimeout(() => {
                notification.style.animation = 'slideOutRight var(--time-trans) ease forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, type === 'error' ? 5000 : 3000);
        },
    };

})();