(function () {
    'use strict';

    if (typeof BX.Xillix === 'undefined') {
        BX.Xillix = {};
    }

    BX.Xillix.Schedule = function (config) {
        this.config = config || {};
        this.mode = this.config.mode || 'teacher';
        this.isTeacherMode = this.mode === 'teacher';
        this.isStudentMode = this.mode === 'student';
        this.currentWeek = new Date();
        this.timezone = this.config.currentTimezone || 'Europe/Moscow';
        this.dayOnlyMode = this.config.defaultDayOnly !== false;
        this.scheduleData = [];

        this.init();
    };

    BX.Xillix.Schedule.prototype = {
        init: function () {
            this.mode = this.config.mode || 'teacher';
            this.isTeacherMode = this.mode === 'teacher';
            this.isStudentMode = this.mode === 'student';
            this.currentWeek = new Date();
            this.timezone = this.config.currentTimezone || 'Europe/Moscow';
            this.dayOnlyMode = this.config.defaultDayOnly !== false;
            this.scheduleData = [];

            this.bindEvents();
            this.generateTimeSlots();
            this.renderWeek();
            this.loadSchedule();
        },

        bindEvents: function () {
            // Навигация по неделям
            BX.bind(document.getElementById('prevWeek'), 'click', BX.proxy(async () => {
                await this.prevWeek();
            }, this));

            BX.bind(document.getElementById('nextWeek'), 'click', BX.proxy(async () => {
                await this.nextWeek();
            }, this));

            // Часовой пояс
            BX.bind(document.getElementById('teacher-timezone'), 'change', BX.proxy(async (e) => {
                await this.changeTimezone(e.target.value);
            }, this));

            // Checkbox "Только день"
            BX.bind(document.getElementById('dayOnlyToggle'), 'change', BX.proxy(async (e) => {
                this.dayOnlyMode = e.target.checked;
                this.generateTimeSlots();
                await this.loadSchedule();
            }, this));

            // Добавление слота (только для преподавателей)
            const addSlotBtn = document.getElementById('addSlot');
            if (addSlotBtn) {
                BX.bind(addSlotBtn, 'click', BX.proxy(() => {
                    this.showAddModal();
                }, this));
            }

            // Модальные окна
            const closeModalBtn = document.getElementById('closeModal');
            if (closeModalBtn) {
                BX.bind(closeModalBtn, 'click', BX.proxy(() => {
                    this.hideModal();
                }, this));
            }

            BX.bind(document.getElementById('closeStudentModal'), 'click', BX.proxy(() => {
                this.hideStudentModal();
            }, this));

            BX.bind(document.getElementById('closeStudentBtn'), 'click', BX.proxy(() => {
                this.hideStudentModal();
            }, this));

            // Обработчик формы (только для преподавателей)
            const scheduleForm = document.getElementById('scheduleForm');
            if (scheduleForm) {
                BX.bind(scheduleForm, 'submit', BX.proxy((e) => {
                    e.preventDefault();
                    this.saveSlot();
                }, this));
            }

            // Кнопка отмены
            const cancelBtn = document.getElementById('cancelBtn');
            if (cancelBtn) {
                BX.bind(cancelBtn, 'click', BX.proxy(() => {
                    this.hideModal();
                }, this));
            }

            // Клик вне модальных окон
            BX.bind(window, 'click', BX.proxy((e) => {
                if (e.target.id === 'scheduleModal') {
                    this.hideModal();
                }
                if (e.target.id === 'studentLessonModal') {
                    this.hideStudentModal();
                }
            }, this));

            // Модальное окно записи ученика
            BX.bind(document.getElementById('closeBookStudentModal'), 'click', BX.proxy(() => {
                this.hideModal('bookStudentModal');
            }, this));

            BX.bind(document.getElementById('cancelBookStudentBtn'), 'click', BX.proxy(() => {
                this.hideModal('bookStudentModal');
            }, this));

            BX.bind(document.getElementById('bookStudentForm'), 'submit', BX.proxy((e) => {
                e.preventDefault();
                this.confirmBookStudents(); // новый метод для нескольких учеников
            }, this));

            BX.bind(window, 'click', BX.proxy((e) => {
                if (e.target.id === 'bookStudentModal') {
                    this.hideModal('bookStudentModal');
                }
            }, this));
        },

        hideStudentModal: function () {
            document.getElementById('studentLessonModal').style.display = 'none';
        },

        hideModal: function (modalId) {
            if (modalId) {
                // Если передан конкретный ID модального окна
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                }
            } else {
                // Старая логика для scheduleModal
                const modal = document.getElementById('scheduleModal');
                if (modal) {
                    modal.style.display = 'none';
                }
            }
        },

        generateTimeSlots: function () {
            const scheduleBody = document.getElementById('scheduleBody');
            scheduleBody.innerHTML = '';

            const startHour = this.dayOnlyMode ? 8 : 0;
            const endHour = this.dayOnlyMode ? 22 : 24;

            for (let hour = startHour; hour <= endHour; hour++) {
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
                    BX.bind(slotCell, 'click', BX.proxy((e) => {
                        this.handleSlotClick(e);
                    }, this));
                    row.appendChild(slotCell);
                }

                scheduleBody.appendChild(row);
            }
        },

        renderWeek: function () {
            const weekStart = this.getWeekStart();
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);

            document.getElementById('currentWeek').textContent =
                'Неделя ' + this.formatDate(weekStart) + ' - ' + this.formatDate(weekEnd);

            for (let day = 1; day <= 7; day++) {
                const date = new Date(weekStart);
                date.setDate(date.getDate() + day - 1);

                const dateElement = document.querySelector('.day-column[data-day="' + this.getDayName(day) + '"] .date');
                if (dateElement) {
                    const dateString = date.toISOString().split('T')[0];
                    dateElement.textContent = this.formatDate(date);
                    dateElement.setAttribute('data-date', dateString);
                }
            }
        },

        loadSchedule: async function () {
            try {
                this.showLoaderSchedule();

                const weekStart = this.getWeekStart();
                const weekStartStr = weekStart ? weekStart.toISOString().split('T')[0] : null;

                const response = await BX.ajax.runComponentAction('xillix:schedule', 'getSchedule', {
                    mode: 'class',
                    data: {
                        weekStart: weekStartStr,
                        timezone: this.timezone
                    }
                });

                this.hideLoaderSchedule();

                if (response.data?.success) {
                    this.scheduleData = response.data.schedule || [];
                    this.mode = response.data.mode || this.mode;
                    this.isTeacherMode = this.mode === 'teacher';
                    this.isStudentMode = this.mode === 'student';
                    await this.renderSchedule();
                } else {
                    const errorMsg = response.data?.error || 'Unknown error';
                    this.showError('Ошибка загрузки: ' + errorMsg);
                }
            } catch (error) {
                this.hideLoaderSchedule();
                console.error('Load schedule error:', error);
                this.showError('Ошибка загрузки расписания');
            }
        },

        showLoaderSchedule: function () {
            const table = document.querySelector('.xillix-schedule');
            if (table) {
                table.classList.add('loader-table');
            }
        },

        hideLoaderSchedule: function () {
            const table = document.querySelector('.xillix-schedule');
            if (table) {
                table.classList.remove('loader-table');
            }
        },

        renderSchedule: async function () {
            // Очищаем ячейки
            const slots = document.querySelectorAll('.time-slot');
            for (let i = 0; i < slots.length; i++) {
                slots[i].innerHTML = '';
                slots[i].className = 'time-slot';
                slots[i].setAttribute('data-slot-id', '');
                slots[i].removeAttribute('data-slot-data');
                slots[i].removeAttribute('title');
            }

            // Ждем обновления DOM
            await this.delay(10);

            // Создаем карту всех возможных слотов на неделю
            const weekStart = this.getWeekStart();
            const allSlotsMap = this.createAllSlotsMap(weekStart);

            // Обновляем карту реальными данными из расписания
            for (const slot of this.scheduleData) {
                const displayDate = slot.DISPLAY_DATE || slot.UF_DATE;
                const slotDate = this.parseDateString(displayDate);

                if (!slotDate) continue;

                const dayOfWeek = slotDate.getDay() || 7;
                const startTime = this.parseBitrixDateTime(slot.UF_START_TIME);
                const startHour = startTime.getHours();

                const slotKey = `${dayOfWeek}_${startHour}`;
                if (allSlotsMap[slotKey]) {
                    allSlotsMap[slotKey].realData = slot;
                }
            }

            // Рендерим все слоты с данными
            for (const slotKey in allSlotsMap) {
                const slotInfo = allSlotsMap[slotKey];
                const cell = document.querySelector(`.time-slot[data-day="${slotInfo.day}"][data-hour="${slotInfo.hour}"]`);

                if (cell) {
                    await this.renderSlotWithData(cell, slotInfo);
                }
            }
        },

        renderSlotWithData: async function (cell, slotInfo) {
            const {day, hour, date, realData} = slotInfo;

            let slotData;
            let statusClass = '';
            let title = '';
            let isClickable = false;
            let contentText = '';

            // Ищем реальные данные для этого слота (правильное сопоставление)
            let matchedRealData = null;
            if (realData) {
                matchedRealData = realData;
            } else {
                // Ищем в scheduleData слот с подходящей датой и временем
                for (const slot of this.scheduleData) {
                    const displayDate = slot.DISPLAY_DATE || slot.UF_DATE;
                    const slotDate = this.parseDateString(displayDate);

                    if (slotDate) {
                        const slotDateKey = slotDate.toISOString().split('T')[0];
                        const startTime = this.parseBitrixDateTime(slot.UF_START_TIME);
                        const slotHour = startTime.getHours();

                        if (slotDateKey === date && slotHour === hour) {
                            matchedRealData = slot;
                            break;
                        }
                    }
                }
            }

            if (matchedRealData) {
                // Слот с реальными данными
                slotData = matchedRealData;
                const hasStudent = matchedRealData.UF_STUDENT_ID && matchedRealData.UF_STUDENT_ID > 0;

                const isPast = this.isPastDate(date + ' ' + slotData.UF_START_TIME);

                if (this.isTeacherMode) {
                    if (hasStudent) {
                        statusClass = 'booked';
                        title = 'Занятие с учеником';
                        isClickable = true;
                        contentText = '👨‍🎓 Занятие';
                    } else if (matchedRealData.UF_STATUS === 'free') {
                        statusClass = 'available';
                        title = 'Свободное время - нажмите для записи учеников';
                        isClickable = true;
                    } else {
                        statusClass = 'unavailable';
                        title = 'Недоступно';
                        isClickable = false;
                        contentText = '❌ Недоступно';
                    }
                } else {
                    // Режим ученика
                    if (hasStudent) {
                        statusClass = 'booked';
                        title = 'Ваше занятие';
                        isClickable = true;
                        contentText = 'Занятие';
                    } else {
                        statusClass = 'unavailable';
                        title = 'Не ваше занятие';
                        isClickable = false;
                        contentText = '❌ Занято';
                    }
                }

                // Для прошедших занятий меняем стиль
                if (isPast) {
                    statusClass = 'past-date';
                    title = 'Прошедшее занятие';
                    isClickable = true;
                }

                // Устанавливаем ID реального слота
                cell.setAttribute('data-slot-id', matchedRealData.ID);

            } else {
                // Пустой слот (нет в расписании)
                const isPast = this.isPastDate(date + ' ' + hour + ':00:00');

                // Создаем базовые данные для пустого слота
                slotData = {
                    ID: 'empty_' + date + '_' + hour,
                    UF_DATE: this.formatDateForDisplay(date),
                    UF_START_TIME: hour + ':00:00',
                    UF_END_TIME: (hour + 1) + ':00:00',
                    UF_STATUS: 'free',
                    UF_STUDENT_ID: null,
                    UF_TEACHER_ID: this.isTeacherMode ? this.config.currentUserId : null,
                    UF_SUBJECT: 'Английский язык',
                    IS_EMPTY: true
                };

                if (isPast) {
                    statusClass = 'past-date';
                    title = 'Прошедшее время';
                    isClickable = false;
                    contentText = '';
                } else {
                    statusClass = 'available';
                    title = 'Свободное время - нажмите для записи учеников';
                    isClickable = this.isTeacherMode; // Только преподаватели могут добавлять в пустые слоты
                }

                // Устанавливаем ID пустого слота
                cell.setAttribute('data-slot-id', slotData.ID);
            }

            // Всегда устанавливаем data-slot-data
            cell.setAttribute('data-slot-data', JSON.stringify(slotData));
            cell.setAttribute('data-slot-date', date);
            cell.setAttribute('data-slot-hour', hour);
            cell.className = `time-slot ${statusClass}`;
            cell.title = title;

            if (isClickable) {
                cell.style.cursor = 'pointer';
            } else {
                cell.style.cursor = 'not-allowed';
            }

            // Добавляем контент в ячейку
            if (contentText) {
                const content = document.createElement('div');
                content.className = 'slot-content';
                content.textContent = contentText;
                cell.appendChild(content);
            }
        },

        formatDateForDisplay: function (dateString) {
            const date = new Date(dateString + 'T00:00:00');
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${day}.${month}.${year}`;
        },

        createAllSlotsMap: function (weekStart) {
            const slotsMap = {};
            const startHour = this.dayOnlyMode ? 8 : 0;
            const endHour = this.dayOnlyMode ? 22 : 24;

            // Создаем дни недели (понедельник = 1, воскресенье = 7)
            for (let day = 1; day <= 7; day++) {
                const date = new Date(weekStart);
                // Корректно добавляем дни (day-1 потому что понедельник уже установлен)
                date.setDate(weekStart.getDate() + (day));
                const dateKey = date.toISOString().split('T')[0];

                // Создаем часы для каждого дня
                for (let hour = startHour; hour <= endHour; hour++) {
                    const slotKey = `${day}_${hour}`;
                    slotsMap[slotKey] = {
                        day: day,
                        hour: hour,
                        date: dateKey,
                        realData: null
                    };
                }
            }

            return slotsMap;
        },

        renderScheduleSlot: async function (slot) {
            // Используем DISPLAY_DATE если есть (конвертированная дата), иначе UF_DATE
            const displayDate = slot.DISPLAY_DATE || slot.UF_DATE;
            const slotDate = this.parseDateString(displayDate);

            if (!slotDate) {
                console.warn('Invalid date format:', displayDate);
                return;
            }

            const dayOfWeek = slotDate.getDay() || 7; // 0-воскр -> 7-воскр, 1-пон -> 1-пон

            // Получаем час начала занятия
            const startTime = this.parseBitrixDateTime(slot.UF_START_TIME);
            const startHour = startTime.getHours();

            // Проверяем, находится ли время в текущем диапазоне
            const currentStartHour = this.dayOnlyMode ? 8 : 0;
            const currentEndHour = this.dayOnlyMode ? 22 : 24;

            if (startHour >= currentStartHour && startHour <= currentEndHour) {
                const cell = document.querySelector(`.time-slot[data-day="${dayOfWeek}"][data-hour="${startHour}"]`);

                if (cell) {
                    await this.renderSlotContent(cell, slot);
                }
            }
        },

        renderSlotContent: async function (cell, slot) {
            let statusClass = '';
            let title = '';
            let isClickable = false;
            let contentText = '';

            const status = slot.UF_STATUS;
            const hasStudent = slot.UF_STUDENT_ID && slot.UF_STUDENT_ID > 0;
            const displayDate = slot.DISPLAY_DATE || slot.UF_DATE;
            const isPast = this.isPastDate(displayDate);

            if (this.isTeacherMode) {
                // Режим преподавателя
                if (hasStudent) {
                    statusClass = 'booked';
                    title = 'Занятие с учеником';
                    isClickable = true;
                    contentText = '👨‍🎓 Занятие';
                } else if (status === 'free') {
                    statusClass = 'available';
                    title = 'Свободное время - нажмите для записи учеников';
                    isClickable = true;
                } else {
                    statusClass = 'unavailable';
                    title = 'Недоступно';
                    isClickable = false;
                    contentText = '❌ Недоступно';
                }
            } else {
                // Режим ученика
                if (hasStudent) {
                    statusClass = 'booked';
                    title = 'Ваше занятие';
                    isClickable = true;
                    contentText = 'Занятие';
                } else {
                    statusClass = 'unavailable';
                    title = 'Не ваше занятие';
                    isClickable = false;
                    contentText = '❌ Занято';
                }
            }

            // Для прошедших занятий меняем стиль и поведение
            if (isPast) {
                statusClass = 'past-date';
                title = 'Прошедшее занятие';
                isClickable = true; // Разрешаем клик для показа информации
            }

            cell.className = `time-slot ${statusClass}`;
            cell.setAttribute('data-slot-data', JSON.stringify(slot));
            cell.title = title;

            // ВСЕГДА устанавливаем data-slot-id для кликабельных слотов
            if (isClickable) {
                // Для свободных слотов создаем специальный ID
                if (status === 'free' && !hasStudent && this.isTeacherMode) {
                    // Создаем ID для свободного слота на основе даты и времени
                    const slotDate = this.parseDateString(displayDate);
                    if (slotDate) {
                        const dateKey = slotDate.toISOString().split('T')[0];
                        const startTime = this.parseBitrixDateTime(slot.UF_START_TIME);
                        const hour = startTime.getHours();
                        cell.setAttribute('data-slot-id', 'empty_' + dateKey + '_' + hour);
                    } else {
                        cell.setAttribute('data-slot-id', slot.ID); // fallback
                    }
                } else {
                    cell.setAttribute('data-slot-id', slot.ID);
                }
                cell.style.cursor = 'pointer';
            } else {
                cell.setAttribute('data-slot-id', '');
                cell.style.cursor = 'not-allowed';
            }

            // Добавляем контент в ячейку
            const content = document.createElement('div');
            content.className = 'slot-content';

            // Время занятия
            const startTime = this.parseBitrixDateTime(slot.UF_START_TIME);
            const endTime = this.parseBitrixDateTime(slot.UF_END_TIME);

            const timeHtml = `<div class="slot-time ${statusClass}">
        ${startTime.getHours().toString().padStart(2, '0')}:${startTime.getMinutes().toString().padStart(2, '0')} - 
        ${endTime.getHours().toString().padStart(2, '0')}:${endTime.getMinutes().toString().padStart(2, '0')}
    </div>`;

            content.innerHTML = timeHtml;
            cell.appendChild(content);
        },

        parseDateString: function (dateString) {
            if (!dateString) return null;

            // Пробуем разные форматы даты
            let date;

            // Формат "d.m.Y H:i:s" (20.10.2025 14:00:00)
            if (dateString.includes('.')) {
                const parts = dateString.split(' ');
                if (parts.length === 2) {
                    const datePart = parts[0]; // "20.10.2025"
                    const timePart = parts[1]; // "14:00:00"

                    const dateParts = datePart.split('.');
                    if (dateParts.length === 3) {
                        const timeParts = timePart.split(':');
                        if (timeParts.length === 3) {
                            date = new Date(
                                parseInt(dateParts[2]), // год
                                parseInt(dateParts[1]) - 1, // месяц (0-11)
                                parseInt(dateParts[0]), // день
                                parseInt(timeParts[0]), // часы
                                parseInt(timeParts[1]), // минуты
                                parseInt(timeParts[2])  // секунды
                            );
                        }
                    }
                }
            }

            // Формат "Y-m-d H:i:s" (2025-10-20 14:00:00)
            if (!date || isNaN(date.getTime())) {
                date = new Date(dateString);
            }

            // Формат "Y-m-d" (2025-10-20)
            if (!date || isNaN(date.getTime())) {
                date = new Date(dateString + 'T00:00:00');
            }

            // Формат "d.m.Y" (20.10.2025)
            if (!date || isNaN(date.getTime())) {
                const parts = dateString.split('.');
                if (parts.length === 3) {
                    date = new Date(parts[2], parts[1] - 1, parts[0]);
                }
            }

            return date && !isNaN(date.getTime()) ? date : null;
        },

        markPastEmptyCells: async function () {
            const slots = document.querySelectorAll('.time-slot:not([data-slot-id])');
            for (const slot of slots) {
                const day = slot.getAttribute('data-day');
                const dateElement = document.querySelector(`.day-column[data-day="${this.getDayName(day)}"] .date`);
                const cellDate = dateElement?.getAttribute('data-date');

                if (cellDate && this.isPastDate(cellDate)) {
                    slot.classList.add('past-date');
                    slot.title = 'Прошедшее время';
                    slot.style.cursor = 'not-allowed';
                }
            }
        },

        handleSlotClick: function (e) {
            const slot = e.currentTarget;
            const slotId = slot.getAttribute('data-slot-id');
            const slotDataJson = slot.getAttribute('data-slot-data');
            const slotData = slotDataJson ? JSON.parse(slotDataJson) : null;

            const displayDate = slotData.UF_DATE + ' ' + slotData.UF_START_TIME;
            const isPast = this.isPastDate(displayDate);

            if (slotData.IS_EMPTY && isPast) {
                return; // Пустой слот - ничего не делаем
            }

            if (!slotData.IS_EMPTY) {
                this.showLessonInfoModal(slotData);
            }

            // Для будущих занятий - разная логика для преподавателей и учеников
            if (this.isStudentMode) {
                // Для ученика - показываем информацию о занятии только если есть студент
                if (slotData.UF_STUDENT_ID && slotData.UF_STUDENT_ID > 0) {
                    this.showLessonInfoModal(slotData);
                }
            } else {
                // Для преподавателя
                if (slotData.UF_STUDENT_ID && slotData.UF_STUDENT_ID > 0) {
                    // Занятый слот - показываем информацию
                    this.showLessonInfoModal(slotData);
                } else if (slotData.UF_STATUS === 'free') {
                    // Свободный слот - показываем модальное окно для записи учеников
                    this.showBookStudentsModal(slotData);
                }
            }
        },

        /**
         * Показать модальное окно для освобождения слота
         */
        showFreeSlotModal: function (slotData) {
            if (!confirm('Освободить этот слот? Ученик будет отписан от занятия.')) {
                return;
            }

            this.freeSlot(slotData.ID);
        },

        /**
         * Освободить слот
         */
        freeSlot: async function (slotId) {
            try {
                const response = await BX.ajax.runComponentAction('xillix:schedule', 'freeSlot', {
                    mode: 'class',
                    data: {
                        slotId: slotId
                    }
                });

                if (response.data?.success) {
                    this.showMessage(response.data.message || 'Слот успешно освобожден');
                    await this.loadSchedule();
                } else {
                    const errorMsg = response.data?.error || 'Ошибка освобождения';
                    this.showError(errorMsg);
                }
            } catch (error) {
                console.error('Free slot error:', error);
                this.showError('Ошибка при освобождении слота');
            }
        },

        /**
         * Загрузить список учеников преподавателя
         */
        loadTeacherStudents: async function () {
            try {
                const response = await BX.ajax.runComponentAction('xillix:schedule', 'getTeacherStudents', {
                    mode: 'class',
                    data: {}
                });

                if (response.data?.success) {
                    return response.data.students || [];
                } else {
                    this.showError('Ошибка загрузки списка учеников');
                    return [];
                }
            } catch (error) {
                console.error('Load teacher students error:', error);
                this.showError('Ошибка загрузки списка учеников');
                return [];
            }
        },

        showLessonInfoModal: function (slotData) {
            let startTime, endTime;

            try {
                startTime = this.parseBitrixDateTime(slotData.UF_START_TIME);
                endTime = this.parseBitrixDateTime(slotData.UF_END_TIME);
            } catch (e) {
                console.error('Date parsing error in info modal:', e);
                startTime = new Date();
                endTime = new Date();
                endTime.setHours(endTime.getHours() + 1);
            }

            // Используем конвертированную дату если есть
            const displayDate = slotData.DISPLAY_DATE || slotData.UF_DATE;
            const formattedDate = this.formatDisplayDate(displayDate);

            const formattedTime = startTime.getHours().toString().padStart(2, '0') + ':' +
                startTime.getMinutes().toString().padStart(2, '0') + ' - ' +
                endTime.getHours().toString().padStart(2, '0') + ':' +
                endTime.getMinutes().toString().padStart(2, '0');

            // Заполняем данные
            document.getElementById('lessonDate').textContent = formattedDate;
            document.getElementById('lessonTime').textContent = formattedTime;

            // Статус занятия
            const statusText = this.getStatusText(slotData.UF_STATUS);

            // Используем UF_START_TIME напрямую для проверки (уже в формате 2025-10-31T03:00:00+03:00)
            const isPast = this.isPastDate(slotData.UF_START_TIME);
            let statusDisplay = statusText;

            if (isPast) {
                statusDisplay = 'Прошедшее занятие';
            }

            document.getElementById('lessonStatus').textContent = statusDisplay;

            // Информация о человеке (ученик или преподаватель)
            const personContainer = document.getElementById('personContainer');
            if (this.isStudentMode) {
                // Для ученика показываем преподавателя
                this.loadTeacherInfo(slotData.UF_TEACHER_ID).then(teacherInfo => {
                    if (teacherInfo.url) {
                        personContainer.innerHTML = `<a href="${teacherInfo.url}" class="teacher-link" target="_blank">${teacherInfo.name}</a>`;
                    } else {
                        personContainer.textContent = teacherInfo.name;
                    }
                });
            } else {
                // Для преподавателя показываем учеников
                if (slotData.UF_STUDENT_IDS && slotData.UF_STUDENT_IDS.length > 0) {
                    // Если есть несколько учеников
                    this.loadMultipleStudentsInfo(slotData.UF_STUDENT_IDS).then(students => {
                        const studentsHtml = students.map(student =>
                            `<a href="/personal/ucheniki/uchenik/?student_id=${student.id}" class="student-link" target="_blank">${student.name}</a>`
                        ).join(', ');
                        personContainer.innerHTML = studentsHtml;

                        // Добавляем кнопку для добавления учеников (только для будущих занятий)
                        if (!isPast) {
                            const addButton = document.createElement('button');
                            addButton.className = 'btn btn-white btn-add-slot';
                            addButton.textContent = '+ Добавить учеников';
                            addButton.style.marginLeft = '10px';
                            addButton.onclick = () => {
                                this.hideStudentModal();
                                this.showAddStudentsToLessonModal(slotData);
                            };
                            personContainer.appendChild(addButton);
                        }
                    });
                } else if (slotData.UF_STUDENT_ID && slotData.UF_STUDENT_ID > 0) {
                    // Если один ученик (старая структура)
                    this.loadStudentInfo(slotData.UF_STUDENT_ID).then(studentName => {
                        const studentUrl = `/personal/ucheniki/uchenik/?student_id=${slotData.UF_STUDENT_ID}`;
                        personContainer.innerHTML = `<a href="${studentUrl}" class="student-link" target="_blank">${studentName}</a>`;

                        // Добавляем кнопку для добавления учеников (только для будущих занятий)
                        if (!isPast) {
                            const addButton = document.createElement('button');
                            addButton.className = 'btn btn-white btn-add-slot';
                            addButton.textContent = '+ Добавить учеников';
                            addButton.style.marginLeft = '10px';
                            addButton.onclick = () => {
                                this.hideStudentModal();
                                this.showAddStudentsToLessonModal(slotData);
                            };
                            personContainer.appendChild(addButton);
                        }
                    });
                } else {
                    // Свободное время - показываем кнопку для записи учеников
                    personContainer.innerHTML = 'Свободное время';
                    if (!isPast) {
                        const addButton = document.createElement('button');
                        addButton.className = 'btn btn-white btn-add-slot';
                        addButton.textContent = '+ Записать учеников';
                        addButton.style.marginLeft = '10px';
                        addButton.onclick = () => {
                            this.hideStudentModal();
                            this.showAddStudentsToLessonModal(slotData);
                        };
                        personContainer.appendChild(addButton);
                    }
                }
            }

            // Информация о конвертации времени
            const timezoneInfo = document.getElementById('timezoneInfo');
            if (timezoneInfo && slotData.timezone_converted) {
                timezoneInfo.style.display = 'block';
            } else if (timezoneInfo) {
                timezoneInfo.style.display = 'none';
            }

            document.getElementById('studentLessonModal').style.display = 'block';
        },

        /**
         * Загрузить информацию о нескольких учениках
         */
        loadMultipleStudentsInfo: function (studentIds) {
            return new Promise((resolve) => {
                const promises = studentIds.map(studentId =>
                    this.loadStudentInfo(studentId).then(name => ({ id: studentId, name: name }))
                );
                Promise.all(promises).then(students => {
                    resolve(students);
                });
            });
        },

        /**
         * Показать модальное окно для добавления учеников к существующему занятию
         */
        showAddStudentsToLessonModal: function (slotData) {
            this.selectedSlot = slotData;

            // Заполняем информацию о времени
            const startTime = this.parseBitrixDateTime(slotData.UF_START_TIME);
            const endTime = this.parseBitrixDateTime(slotData.UF_END_TIME);

            const displayDate = slotData.DISPLAY_DATE || slotData.UF_DATE;
            const formattedDate = this.formatDisplayDate(displayDate);
            const formattedTime = startTime.getHours().toString().padStart(2, '0') + ':' +
                startTime.getMinutes().toString().padStart(2, '0') + ' - ' +
                endTime.getHours().toString().padStart(2, '0') + ':' +
                endTime.getMinutes().toString().padStart(2, '0');

            // Обновляем модальное окно
            document.getElementById('bookStudentDate').textContent = formattedDate;
            document.getElementById('bookStudentTime').textContent = formattedTime;
            document.getElementById('bookStudentSlotId').value = slotData.ID;

            // Загружаем список учеников
            this.loadTeacherStudents().then(students => {
                const select = document.getElementById('studentSelect');
                select.innerHTML = '<option value="">-- Выберите учеников --</option>';

                students.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = student.name + (student.notes ? ' (' + student.notes + ')' : '');

                    // Помечаем уже записанных учеников как выбранные
                    if (slotData.UF_STUDENT_IDS && slotData.UF_STUDENT_IDS.includes(student.id.toString())) {
                        option.selected = true;
                    } else if (slotData.UF_STUDENT_ID && slotData.UF_STUDENT_ID == student.id) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });

                // Делаем select множественным
                select.multiple = true;
                select.size = Math.min(6, students.length + 1);
            });

            document.getElementById('bookStudentModal').style.display = 'block';
        },

        /**
         * Показать модальное окно для записи учеников на новый слот
         */
        showBookStudentsModal: function (slotData) {
            this.selectedSlot = slotData;

            // Заполняем информацию о времени
            const startTime = this.parseBitrixDateTime(slotData.UF_START_TIME);
            const endTime = this.parseBitrixDateTime(slotData.UF_END_TIME);

            const displayDate = slotData.DISPLAY_DATE || slotData.UF_DATE;
            const formattedDate = this.formatDisplayDate(displayDate);
            const formattedTime = startTime.getHours().toString().padStart(2, '0') + ':' +
                startTime.getMinutes().toString().padStart(2, '0') + ' - ' +
                endTime.getHours().toString().padStart(2, '0') + ':' +
                endTime.getMinutes().toString().padStart(2, '0');

            // Обновляем модальное окно bookStudentModal
            document.getElementById('bookStudentDate').textContent = formattedDate;
            document.getElementById('bookStudentTime').textContent = formattedTime;
            document.getElementById('bookStudentSlotId').value = slotData.ID;

            // Загружаем список учеников
            this.loadTeacherStudents().then(students => {
                const select = document.getElementById('studentSelect');
                select.innerHTML = '<option value="">-- Выберите учеников --</option>';

                students.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = student.name + (student.notes ? ' (' + student.notes + ')' : '');
                    select.appendChild(option);
                });

                // Делаем select множественным
                select.multiple = true;
                select.size = Math.min(6, students.length + 1); // Ограничиваем размер
            });

            document.getElementById('bookStudentModal').style.display = 'block';
        },

        /**
         * Подтвердить запись учеников
         */
        confirmBookStudents: async function () {
            const slotId = document.getElementById('bookStudentSlotId').value;
            const studentSelect = document.getElementById('studentSelect');

            // Получаем выбранных учеников
            const selectedStudents = Array.from(studentSelect.selectedOptions)
                .map(option => option.value)
                .filter(value => value !== '');

            if (!slotId || selectedStudents.length === 0) {
                this.showError('Выберите хотя бы одного ученика');
                return;
            }

            try {
                const response = await BX.ajax.runComponentAction('xillix:schedule', 'saveSlotNew', {
                    mode: 'class',
                    data: {
                        slotData: {
                            slot_id: slotId,
                            student_ids: selectedStudents,
                            timezone: this.timezone
                        }
                    }
                });

                if (response.data?.success) {
                    const message = response.data.student_count > 1 ?
                        `${response.data.student_count} ученика записаны на урок` :
                        'Ученик успешно записан на урок';

                    this.showMessage(message);
                    this.hideModal('bookStudentModal');
                    await this.loadSchedule();
                } else {
                    const errorMsg = response.data?.error || 'Ошибка записи';
                    this.showError(errorMsg);
                }
            } catch (error) {
                console.error('Book students error:', error);
                this.showError('Ошибка при записи учеников');
            }
        },


        formatDisplayDate: function (dateString) {
            const date = this.parseDateString(dateString);
            if (!date) return dateString;

            return date.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                weekday: 'long'
            });
        },

        getStatusText: function (status) {
            const statusMap = {
                'free': 'Свободно',
                'blocked': 'Забронировано',
                'canceled': 'Отменено'
            };
            return statusMap[status] || 'Неизвестно';
        },

        loadTeacherInfo: function (teacherId) {
            return new Promise((resolve) => {
                BX.ajax.runComponentAction('xillix:schedule', 'getTeacherInfo', {
                    mode: 'class',
                    data: {teacherId: teacherId}
                }).then(response => {
                    if (response.data?.success) {
                        resolve({
                            name: response.data.teacherName,
                            url: response.data.teacherUrl
                        });
                    } else {
                        resolve({
                            name: 'Преподаватель',
                            url: ''
                        });
                    }
                }).catch(() => {
                    resolve({
                        name: 'Преподаватель',
                        url: ''
                    });
                });
            });
        },

        isPastDate: function (dateString) {
            if (!dateString) return false;
            const today = new Date();
            // today.setHours(0, 0, 0, 0);

            const date = this.parseDateString(dateString);
            if (!date) return false;

            // date.setHours(0, 0, 0, 0);
            return date < today;
        },

        showAddModal: function () {
            if (!this.isTeacherMode) {
                this.showError('Только преподаватели могут добавлять занятия');
                return;
            }

            document.getElementById('modalTitle').textContent = this.config.messages.ADD_LESSON;
            document.getElementById('scheduleForm').reset();
            document.getElementById('slotId').value = '';

            this.setFormEditable(true);
            document.getElementById('saveBtn').style.display = 'inline-block';
            document.getElementById('cancelBtn').textContent = this.config.messages.CANCEL;
            document.getElementById('scheduleModal').style.display = 'block';
        },

        showAddModalForSlot: function (day, hour) {
            this.showAddModal();

            const dateElement = document.querySelector(`.day-column[data-day="${this.getDayName(day)}"] .date`);
            const date = dateElement?.getAttribute('data-date');

            if (date) {
                document.getElementById('slotDate').value = date;
                document.getElementById('startTime').value = hour.toString().padStart(2, '0') + ':00';
                document.getElementById('endTime').value = (parseInt(hour) + 1).toString().padStart(2, '0') + ':00';
            }
        },

        showEditModal: function (slotData) {
            if (!this.isTeacherMode) {
                this.showError('Только преподаватели могут редактировать занятия');
                return;
            }

            this.setupModal(slotData, false);
        },

        setupModal: function (slotData, isView = false) {
            document.getElementById('modalTitle').textContent = isView ?
                (this.config.messages.VIEW_LESSON || 'Просмотр занятия') :
                (this.config.messages.EDIT_LESSON || 'Редактирование занятия');

            document.getElementById('slotId').value = slotData.ID;

            let startTime, endTime;

            try {
                startTime = this.parseBitrixDateTime(slotData.UF_START_TIME);
                endTime = this.parseBitrixDateTime(slotData.UF_END_TIME);
            } catch (e) {
                console.error('Date parsing error:', e);
                startTime = new Date();
                endTime = new Date();
                endTime.setHours(endTime.getHours() + 1);
            }

            // Заполняем форму
            if (startTime) {
                // Используем конвертированную дату если есть
                const displayDate = slotData.DISPLAY_DATE || slotData.UF_DATE;
                const dateForInput = this.formatDateForInput(this.parseDateString(displayDate) || startTime);

                document.getElementById('slotDate').value = dateForInput;
                document.getElementById('startTime').value = this.formatTimeForInput(startTime);
                document.getElementById('endTime').value = this.formatTimeForInput(endTime);
            }

            // Показываем информацию об ученике если есть
            if (slotData.UF_STUDENT_ID && slotData.UF_STUDENT_ID > 0) {
                this.loadStudentInfo(slotData.UF_STUDENT_ID).then(studentName => {
                    if (this.isTeacherMode) {
                        // Для преподавателя - создаем ссылку
                        const studentLink = document.getElementById('studentLink');
                        if (studentLink) {
                            studentLink.href = '/personal/ucheniki/uchenik/?student_id=' + slotData.UF_STUDENT_ID;
                            studentLink.innerHTML = studentName;
                        }
                    } else {
                        // Для ученика - обычное поле
                        document.getElementById('studentName').value = studentName;
                    }
                    document.getElementById('studentId').value = slotData.UF_STUDENT_ID;
                    document.getElementById('studentField').style.display = 'block';
                });
            } else {
                document.getElementById('studentField').style.display = 'none';
            }

            const displayDate = slotData.DISPLAY_DATE || slotData.UF_DATE;
            const isPast = this.isPastDate(displayDate);

            if (isView || isPast) {
                this.setFormEditable(false);
                document.getElementById('saveBtn').style.display = 'none';
                document.getElementById('cancelBtn').textContent = this.config.messages.CLOSE || 'Закрыть';
                if (isPast) {
                    document.getElementById('modalTitle').textContent += ' (прошедшее занятие)';
                }
            } else {
                this.setFormEditable(true);
                document.getElementById('saveBtn').style.display = 'inline-block';
                document.getElementById('cancelBtn').textContent = this.config.messages.CANCEL;
            }

            document.getElementById('scheduleModal').style.display = 'block';
        },

        saveSlot: async function () {
            if (!this.isTeacherMode) {
                this.showError('Только преподаватели могут сохранять занятия');
                return;
            }

            try {
                const form = document.getElementById('scheduleForm');
                const formData = {
                    slot_date: form.slot_date.value,
                    start_time: form.start_time.value,
                    end_time: form.end_time.value,
                    subject: form.subject.value,
                    timezone: this.timezone
                };

                const slotId = form.slot_id.value;
                if (slotId) {
                    formData.slot_id = slotId;
                }

                const saveBtn = document.getElementById('saveBtn');
                const originalText = saveBtn.textContent;
                saveBtn.disabled = true;
                saveBtn.textContent = 'Сохранение...';

                const response = await BX.ajax.runComponentAction('xillix:schedule', 'saveSlot', {
                    mode: 'class',
                    data: {slotData: formData}
                });

                saveBtn.disabled = false;
                saveBtn.textContent = originalText;

                if (response.data?.success) {
                    this.hideModal();
                    await this.loadSchedule();
                    this.showMessage(this.config.messages.SAVE_SUCCESS || 'Данные сохранены успешно');
                } else {
                    const errorMsg = response.data?.errors?.join(', ') || response.data?.error || 'Неизвестная ошибка';
                    this.showError('Ошибка сохранения: ' + errorMsg);
                }
            } catch (error) {
                const saveBtn = document.getElementById('saveBtn');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Сохранить';

                console.error('Save error:', error);
                this.showError('Ошибка сохранения');
            }
        },

        deleteSlot: async function (slotId) {
            if (!this.isTeacherMode) {
                this.showError('Только преподаватели могут удалять занятия');
                return;
            }

            if (!confirm('Вы уверены, что хотите удалить это занятие?')) {
                return;
            }

            try {
                const response = await BX.ajax.runComponentAction('xillix:schedule', 'deleteSlot', {
                    mode: 'class',
                    data: {slotId: slotId}
                });

                if (response.data?.success) {
                    this.hideModal();
                    await this.loadSchedule();
                    this.showMessage(this.config.messages.DELETE_SUCCESS || 'Занятие удалено');
                } else {
                    const errorMsg = response.data?.error || 'Неизвестная ошибка';
                    this.showError('Ошибка удаления: ' + errorMsg);
                }
            } catch (error) {
                console.error('Delete error:', error);
                this.showError('Ошибка удаления');
            }
        },

        changeTimezone: async function (newTimezone) {
            try {
                this.timezone = newTimezone;

                const response = await BX.ajax.runComponentAction('xillix:schedule', 'saveTimezone', {
                    mode: 'class',
                    data: {timezone: newTimezone}
                });

                if (response.data?.success) {
                    await this.loadSchedule();
                    this.showMessage('Часовой пояс обновлен');
                } else {
                    const errorMsg = response.data?.error || 'Unknown error';
                    this.showError('Ошибка сохранения: ' + errorMsg);
                }
            } catch (error) {
                console.error('Timezone save error:', error);
                this.showError('Ошибка сохранения часового пояса');
            }
        },

        prevWeek: async function () {
            this.currentWeek.setDate(this.currentWeek.getDate() - 7);
            this.renderWeek();
            await this.loadSchedule();
        },

        nextWeek: async function () {
            this.currentWeek.setDate(this.currentWeek.getDate() + 7);
            this.renderWeek();
            await this.loadSchedule();
        },

        // Вспомогательные методы
        formatTimeForInput: function (date) {
            if (!(date instanceof Date) || isNaN(date.getTime())) {
                date = new Date();
            }
            return date.getHours().toString().padStart(2, '0') + ':' +
                date.getMinutes().toString().padStart(2, '0');
        },

        formatDateForInput: function (date) {
            if (!(date instanceof Date) || isNaN(date.getTime())) {
                date = new Date();
            }
            return date.toISOString().split('T')[0];
        },

        parseBitrixDateTime: function (dateTimeString) {
            if (!dateTimeString) return new Date();

            // Если строка уже в ISO формате с временной зоной
            if (dateTimeString.includes('T') && dateTimeString.includes('+')) {
                // Убираем временную зону для корректного парсинга
                const withoutTimezone = dateTimeString.split('+')[0];
                const date = new Date(withoutTimezone + 'Z'); // Добавляем Z для UTC
                if (!isNaN(date.getTime())) {
                    // Корректируем на локальную временную зону
                    const timezoneOffset = date.getTimezoneOffset() * 60000;
                    return new Date(date.getTime() + timezoneOffset);
                }
            }

            // Остальные форматы...
            let date;

            // Формат "d.m.Y H:i:s" (20.10.2025 14:00:00)
            date = Date.parse(dateTimeString.replace(/(\d{2})\.(\d{2})\.(\d{4})/, '$3-$2-$1'));
            if (!isNaN(date)) {
                return new Date(date);
            }

            // Формат "Y-m-d H:i:s" (2025-10-20 14:00:00)
            date = Date.parse(dateTimeString);
            if (!isNaN(date)) {
                return new Date(date);
            }

            return new Date();
        },

        loadStudentInfo: function (studentId) {
            return new Promise((resolve) => {
                BX.ajax.runComponentAction('xillix:schedule', 'getUserInfo', {
                    mode: 'class',
                    data: {userId: studentId}
                }).then(response => {
                    if (response.data?.success) {
                        resolve(response.data.userName);
                    } else {
                        resolve('Ученик #' + studentId);
                    }
                }).catch(() => {
                    resolve('Ученик #' + studentId);
                });
            });
        },

        setFormEditable: function (editable) {
            document.getElementById('slotDate').readOnly = !editable;
            document.getElementById('startTime').readOnly = !editable;
            document.getElementById('endTime').readOnly = !editable;
        },

        getWeekStart: function () {
            try {
                const weekStart = new Date(this.currentWeek);
                const day = weekStart.getDay();
                // Понедельник = 1, Воскресенье = 0
                const diff = weekStart.getDate() - day + (day === 0 ? -6 : 1);
                weekStart.setDate(diff);
                weekStart.setHours(0, 0, 0, 0);
                return weekStart;
            } catch (e) {
                console.error('Error calculating week start:', e);
                // Fallback: текущий понедельник
                const today = new Date();
                const day = today.getDay();
                const diff = today.getDate() - day + (day === 0 ? -6 : 1);
                const monday = new Date(today.setDate(diff));
                monday.setHours(0, 0, 0, 0);
                return monday;
            }
        },

        formatDate: function (date) {
            return date.toLocaleDateString('ru-RU');
        },

        getDayName: function (dayNumber) {
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            return days[dayNumber - 1];
        },

        showMessage: function (message) {
            this.showNotification(message, 'success');
        },

        showError: function (message) {
            this.showNotification(message, 'error');
        },

        showNotification: function (message, type) {
            if (typeof BX.UI !== 'undefined' && typeof BX.UI.Notification !== 'undefined') {
                BX.UI.Notification.Center.notify({
                    content: message,
                    autoHideDelay: type === 'error' ? 5000 : 3000,
                    type: type
                });
            } else {
                this.showCustomNotification(message, type);
            }
        },

        showCustomNotification: function (message, type) {
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

            setTimeout(() => {
                notification.style.animation = 'slideOutRight var(--time-trans) ease forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, type === 'error' ? 5000 : 3000);
        },

        delay: function (ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    };

})();