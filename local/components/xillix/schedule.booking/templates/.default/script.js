if (typeof BX !== 'undefined') {
    (function () {
        'use strict';

        if (typeof BX.Xillix === 'undefined') {
            BX.Xillix = {};
        }

        BX.Xillix.ScheduleBooking = function (config) {
            this.config = config || {};
            this.teacherId = config.teacherId;
            this.isAuthorized = config.isAuthorized;
            this.hideNavigation = config.hideNavigation;
            this.currentWeek = new Date();
            this.dayOnlyMode = config.defaultDayOnly !== false;
            this.scheduleData = [];
            this.selectedSlot = null;

            this.init();
        };

        BX.Xillix.ScheduleBooking.prototype = {
            init: function () {
                this.timezone = this.config.currentTimezone || 'Europe/Moscow';
                this.currentWeek = new Date();
                this.currentWeek.setHours(0, 0, 0, 0);

                this.currentUserId = this.config.currentUserId || null;

                this.dayOnlyMode = this.config.defaultDayOnly !== false;
                this.scheduleData = [];
                this.selectedSlot = null;

                this.bindEvents();
                this.generateTimeSlots();
                this.renderWeek();
                this.loadSchedule();
            },

            bindEvents: function () {
                // Checkbox "Только день"
                BX.bind(document.getElementById('dayOnlyToggle'), 'change', BX.proxy(async (e) => {
                    this.dayOnlyMode = e.target.checked;
                    this.generateTimeSlots();
                    await this.loadSchedule();
                }, this));

                // Часовой пояс
                BX.bind(document.getElementById('student-timezone'), 'change', BX.proxy(async (e) => {
                    await this.changeTimezone(e.target.value);
                }, this));

                // Навигация по неделям
                BX.bind(document.getElementById('prevWeek'), 'click', BX.proxy(async () => {
                    await this.prevWeek();
                }, this));

                BX.bind(document.getElementById('nextWeek'), 'click', BX.proxy(async () => {
                    await this.nextWeek();
                }, this));

                // Модальные окна
                BX.bind(document.querySelector('#bookingModal .close'), 'click', BX.proxy(() => {
                    this.hideModal('bookingModal');
                }, this));

                BX.bind(document.getElementById('cancelBookingBtn'), 'click', BX.proxy(() => {
                    this.hideModal('bookingModal');
                }, this));

                BX.bind(document.getElementById('confirmBookingBtn'), 'click', BX.proxy(async () => {
                    await this.confirmBooking();
                }, this));

                BX.bind(window, 'click', BX.proxy((e) => {
                    if (e.target.id === 'bookingModal') {
                        this.hideModal('bookingModal');
                    }
                }, this));

                BX.bind(document.getElementById('closeStudentModal'), 'click', BX.proxy(() => {
                    this.hideModal('studentLessonModal');
                }, this));

                BX.bind(document.getElementById('closeStudentBtn'), 'click', BX.proxy(() => {
                    this.hideModal('studentLessonModal');
                }, this));

                BX.bind(document.getElementById('studentLessonModal'), 'click', BX.proxy((e) => {
                    if (e.target.id === 'studentLessonModal') {
                        this.hideModal('studentLessonModal');
                    }
                }, this));
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

            loadSchedule: async function () {
                try {
                    this.showLoaderSchedule();

                    const weekStart = this.getWeekStart();
                    const weekStartStr = weekStart ? weekStart.toISOString().split('T')[0] : null;

                    const response = await BX.ajax.runComponentAction('xillix:schedule.booking', 'getSchedule', {
                        mode: 'class',
                        data: {
                            teacherId: this.teacherId,
                            weekStart: weekStartStr, // Передаем начало недели
                            timezone: this.timezone
                        }
                    });

                    this.hideLoaderSchedule();

                    if (response.data?.success) {
                        this.scheduleData = response.data.schedule || [];
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
                    slots[i].innerHTML = ''; // Убираем slot-content
                    slots[i].className = 'time-slot';
                    slots[i].setAttribute('data-slot-id', '');
                    slots[i].removeAttribute('title');
                }

                // Ждем обновления DOM
                await this.delay(10);

                // Заполняем расписание данными как в schedule компоненте
                for (const slot of this.scheduleData) {
                    // Форматируем слот для совместимости
                    const formattedSlot = this.formatSlotData(slot);

                    // Получаем день недели и час из даты
                    const slotDate = new Date(slot.date + 'T00:00:00');
                    const dayOfWeek = slotDate.getDay() || 7; // 0-воскр -> 7-воскр, 1-пон -> 1-пон

                    const startHour = parseInt(slot.hour);

                    // Проверяем, находится ли время в текущем диапазоне
                    const currentStartHour = this.dayOnlyMode ? 8 : 0;
                    const currentEndHour = this.dayOnlyMode ? 22 : 24;

                    if (startHour >= currentStartHour && startHour <= currentEndHour) {
                        const cell = document.querySelector(`.time-slot[data-day="${dayOfWeek}"][data-hour="${startHour}"]`);

                        if (cell) {
                            await this.renderBookingSlot(cell, slot);
                        }
                    }
                }

                await this.markPastEmptyCells();
            },

            renderBookingSlot: async function (cell, slot) {
                let statusClass = '';
                let title = '';
                let isClickable = false;

                if (slot.is_booked_by_current) {
                    statusClass = 'booked';
                    title = 'Ваше занятие - можно отменить';
                    isClickable = true;
                } else if (slot.is_booked_by_other) {
                    statusClass = 'unavailable';
                    title = 'Занято другим студентом';
                    isClickable = false;
                } else if (slot.is_template && slot.is_available) {
                    const isPastDateTime = this.isPastDateTimeSimple(slot.date, slot.hour);
                    if (isPastDateTime) {
                        statusClass = 'past-date';
                        title = 'Прошедшее время';
                        isClickable = false;
                    } else {
                        statusClass = 'available';
                        title = 'Свободно - доступно для записи';
                        isClickable = true;
                    }
                } else {
                    statusClass = 'unavailable';
                    title = 'Недоступно';
                    isClickable = false;
                }

                cell.className = `time-slot ${statusClass}`;

                // Формируем data-slot-id на основе конвертированного времени
                let slotId = '';
                if (isClickable) {
                    if (slot.is_booked_by_current) {
                        // Для своих занятий используем оригинальный ID
                        slotId = slot.id;
                    } else if (slot.is_template && slot.is_available) {
                        // Для шаблонных слотов создаем ID на основе конвертированного времени
                        slotId = 'template_' + slot.date + '_' + slot.hour;
                    }
                    cell.setAttribute('data-slot-id', slotId);
                    cell.style.cursor = 'pointer';
                } else {
                    cell.setAttribute('data-slot-id', '');
                    cell.style.cursor = 'not-allowed';
                }

                cell.setAttribute('data-slot-date', slot.date);
                cell.setAttribute('data-slot-hour', slot.hour);
                cell.title = title;

                // Добавляем контент с датой и временем
                const content = document.createElement('div');
                content.className = 'slot-content';

                // Форматируем дату из Y-m-d в d.m.Y
                const dateParts = slot.date.split('-');
                const formattedDate = `${dateParts[2]}.${dateParts[1]}.${dateParts[0]}`;

                // Форматируем время (slot.hour уже содержит час в поясе пользователя)
                const startTime = `${slot.hour.toString().padStart(2, '0')}:00`;
                const endTime = `${(parseInt(slot.hour) + 1).toString().padStart(2, '0')}:00`;

                if (slot.is_booked_by_current) {
                    // Форматируем время (slot.hour уже содержит час)
                    const startTime = `${slot.hour.toString().padStart(2, '0')}:00`;
                    const endTime = `${(parseInt(slot.hour) + 1).toString().padStart(2, '0')}:00`;

                    content.innerHTML = `Занятие<br><span class="date">${startTime} - ${endTime}</span>`;
                }

                cell.appendChild(content);
            },

            isPastDateTime: function (dateString, hour) {
                const now = new Date();
                const today = new Date();
                today.setHours(0, 0, 0, 0); // Начало текущего дня

                const slotDate = new Date(dateString + 'T00:00:00');
                const slotDateTime = new Date(dateString + 'T' + hour.toString().padStart(2, '0') + ':00:00');

                // Если дата слота раньше сегодняшнего дня - блокируем
                if (slotDate < today) {
                    return true;
                }

                // Если дата слота сегодня - блокируем только прошедшие часы
                if (slotDate.getTime() === today.getTime()) {
                    return slotDateTime < now;
                }

                // Если дата слота в будущем - не блокируем
                return false;
            },

            // Альтернативный более простой метод
            isPastDateTimeSimple: function (dateString, hour) {
                const now = new Date();
                const slotDateTime = new Date(dateString + 'T' + hour.toString().padStart(2, '0') + ':00:00');
                return slotDateTime < now;
            },

            formatSlotData: function (slot) {
                return {
                    ID: slot.id,
                    UF_DATE: slot.date, // Y-m-d -> d.m.Y
                    UF_START_TIME: slot.start_time + ':00',
                    UF_END_TIME: slot.end_time + ':00',
                    UF_SUBJECT: 'Английский язык',
                    UF_STATUS: slot.status,
                    UF_STUDENT_ID: slot.student_id,
                    UF_TEACHER_ID: this.teacherId,
                    is_booked: slot.is_booked,
                    is_available: slot.is_available,
                    is_booked_by_current: slot.is_booked_by_current,
                    is_booked_by_other: slot.is_booked_by_other,
                    is_template: slot.is_template
                };
            },

            renderSlotCell: async function (cell, slot) {
                let statusClass = '';
                let title = '';
                let isClickable = false;

                // Используем новые ключи из getBookingSchedule
                const isCurrentUserSlot = slot.is_booked_by_current;
                const isBookedByOther = slot.is_booked_by_other;

                if (slot.is_booked) {
                    if (isCurrentUserSlot) {
                        statusClass = 'booked';
                        title = 'Ваше занятие - нажмите для просмотра';
                        isClickable = true;
                    } else if (isBookedByOther) {
                        statusClass = 'unavailable';
                        title = 'Занято другим студентом';
                        isClickable = true;
                    } else {
                        statusClass = 'unavailable';
                        title = 'Занято';
                        isClickable = false;
                    }
                } else if (slot.is_available && slot.is_template) {
                    statusClass = 'available';
                    title = 'Свободно - доступно для записи';
                    isClickable = true;
                } else {
                    statusClass = 'unavailable';
                    title = 'Недоступно';
                    isClickable = false;
                }

                // Проверяем прошедшее время
                const isPastDateTime = this.isPastDateTimeSimple(slot.date, slot.hour);
                if (isPastDateTime) {
                    statusClass = 'past-date';
                    title = 'Прошедшее время';
                    isClickable = false;
                }

                cell.className = `time-slot ${statusClass}`;

                // Всегда устанавливаем data-slot-id для кликабельных ячеек
                if (isClickable && slot.id) {
                    cell.setAttribute('data-slot-id', slot.id);
                    cell.style.cursor = 'pointer';
                } else {
                    cell.setAttribute('data-slot-id', '');
                    cell.style.cursor = 'not-allowed';
                }

                cell.setAttribute('data-slot-date', slot.date);
                cell.setAttribute('data-slot-hour', slot.hour);
                cell.title = title;

                // Добавляем контент в ячейку
                const content = document.createElement('div');
                content.className = 'slot-content';

                if (isCurrentUserSlot) {
                    content.innerHTML = '✅ Ваше занятие';
                }
                // else if (slot.is_booked) {
                //     content.innerHTML = '❌ Занято';
                // } else if (slot.is_available) {
                //     content.innerHTML = '➕ Свободно';
                // }

                cell.appendChild(content);
            },

            showStudentLessonModal: function (slot) {
                // Заполняем данные о занятии
                document.getElementById('lessonDate').textContent = this.formatDisplayDate(slot.date);
                document.getElementById('lessonTime').textContent =
                    slot.start_time + ' - ' + slot.end_time;
                document.getElementById('lessonStatus').textContent = 'Забронировано';

                // Для шаблонных слотов преподаватель - это teacherId из компонента
                const teacherId = this.teacherId;
                this.loadTeacherInfo(teacherId).then(teacherInfo => {
                    const teacherContainer = document.getElementById('teacherContainer');
                    if (teacherInfo.teacherUrl) {
                        teacherContainer.innerHTML = `<a href="${teacherInfo.teacherUrl}" target="_blank">${teacherInfo.teacherName}</a>`;
                    } else {
                        teacherContainer.textContent = teacherInfo.teacherName;
                    }
                }).catch(error => {
                    document.getElementById('teacherContainer').textContent = 'Преподаватель';
                });

                document.getElementById('studentLessonModal').style.display = 'block';
            },

            showOtherStudentLessonModal: function (slot) {
                // Заполняем данные о занятии
                document.getElementById('lessonDate').textContent = this.formatDisplayDate(slot.date);
                document.getElementById('lessonTime').textContent =
                    slot.start_time + ' - ' + slot.end_time;
                document.getElementById('lessonStatus').textContent = 'Занято другим студентом';

                // Для шаблонных слотов преподаватель - это teacherId из компонента
                const teacherId = this.teacherId;
                this.loadTeacherInfo(teacherId).then(teacherInfo => {
                    const teacherContainer = document.getElementById('teacherContainer');
                    if (teacherInfo.teacherUrl) {
                        teacherContainer.innerHTML = `<a href="${teacherInfo.teacherUrl}" target="_blank">${teacherInfo.teacherName}</a>`;
                    } else {
                        teacherContainer.textContent = teacherInfo.teacherName;
                    }
                }).catch(error => {
                    document.getElementById('teacherContainer').textContent = 'Преподаватель';
                });

                document.getElementById('studentLessonModal').style.display = 'block';
            },

            formatDisplayDate: function (dateString) {
                // Конвертируем из формата Y-m-d в читаемый вид
                const date = new Date(dateString + 'T00:00:00');
                return date.toLocaleDateString('ru-RU', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            },

            formatDateString: function (dateString, includeTime = false) {
                let dateObj;

                if (dateString.includes('.')) {
                    const [datePart, timePart] = dateString.split(' ');
                    const dateParts = datePart.split('.');

                    const day = parseInt(dateParts[0], 10);
                    const month = parseInt(dateParts[1], 10);
                    const year = parseInt(dateParts[2], 10);

                    if (timePart) {
                        const timeParts = timePart.split(':');
                        const hours = parseInt(timeParts[0], 10);
                        const minutes = parseInt(timeParts[1], 10);
                        const seconds = parseInt(timeParts[2], 10);
                        dateObj = new Date(year, month - 1, day, hours, minutes, seconds);
                    } else {
                        dateObj = new Date(year, month - 1, day);
                    }
                } else {
                    dateObj = new Date(dateString);
                }

                if (!(dateObj instanceof Date) || isNaN(dateObj.getTime())) {
                    throw new Error('Invalid date string');
                }

                const y = dateObj.getFullYear();
                const m = String(dateObj.getMonth() + 1).padStart(2, '0');
                const d = String(dateObj.getDate()).padStart(2, '0');

                if (includeTime) {
                    const h = String(dateObj.getHours()).padStart(2, '0');
                    const min = String(dateObj.getMinutes()).padStart(2, '0');
                    const sec = String(dateObj.getSeconds()).padStart(2, '0');
                    return `${y}-${m}-${d} ${h}:${min}:${sec}`;
                } else {
                    return `${y}-${m}-${d}`;
                }
            },

            loadTeacherInfo: async function (teacherId) {
                try {
                    const response = await BX.ajax.runComponentAction('xillix:schedule', 'getTeacherInfo', {
                        mode: 'class',
                        data: {
                            teacherId: teacherId
                        }
                    });

                    if (response.data?.success) {
                        return response.data;
                    } else {
                        throw new Error(response.data?.error || 'Unknown error');
                    }
                } catch (error) {
                    console.error('Load teacher info error:', error);
                    return {teacherName: 'Преподаватель', teacherUrl: ''};
                }
            },

            markPastEmptyCells: async function () {
                const slots = document.querySelectorAll('.time-slot:not([data-slot-id])');

                for (const slot of slots) {
                    const day = slot.getAttribute('data-day');
                    const hour = slot.getAttribute('data-hour');
                    const dateElement = document.querySelector(`.day-column[data-day="${day}"] .date`);
                    const cellDate = dateElement?.getAttribute('data-date');

                    // Блокируем только прошедшие слоты
                    if (cellDate && this.isPastDateTimeSimple(cellDate, hour)) {
                        slot.classList.add('past-date');
                        slot.title = 'Прошедшее время';
                        slot.style.cursor = 'not-allowed';
                    } else if (!slot.classList.contains('available') && !slot.classList.contains('booked')) {
                        // Слоты без данных (не в шаблоне) отмечаем как недоступные
                        slot.classList.add('unavailable');
                        slot.title = 'Недоступно';
                        slot.style.cursor = 'not-allowed';
                    }
                }
            },


            handleSlotClick: function (e) {
                const slot = e.currentTarget;
                const slotId = slot.getAttribute('data-slot-id');
                const slotDate = slot.getAttribute('data-slot-date');
                const slotHour = slot.getAttribute('data-slot-hour');

                // Проверяем, не прошедшее ли время
                if (this.isPastDateTime(slotDate, slotHour)) {
                    this.showError('Нельзя записаться на прошедшее время');
                    return;
                }

                // Если слот забронирован - показываем информацию
                if (slot.classList.contains('booked') || slot.classList.contains('unavailable')) {
                    const slotData = this.findSlotById(slotId);
                    if (slotData) {
                        if (slot.classList.contains('booked')) {
                            this.showStudentLessonModal(slotData);
                        } else {
                            this.showOtherStudentLessonModal(slotData);
                        }
                    }
                    return;
                }

                // Если слот свободен - проверяем авторизацию и показываем окно записи
                if (!slotId || !slot.classList.contains('available')) {
                    this.showError('Это время недоступно для записи');
                    return;
                }

                // Только теперь проверяем авторизацию для записи
                if (!this.isAuthorized) {
                    this.showAuthModal();
                    return;
                }

                // Показываем модальное окно подтверждения записи
                this.showBookingModal(slotId, slotDate, slotHour);
            },

            findSlotById: function (slotId) {
                return this.scheduleData.find(slot => {
                    return slot.id.toString() === slotId.toString();
                });
            },

            showBookingModal: function (slotId, date, hour) {
                this.selectedSlot = {
                    id: slotId,
                    date: date,  // Дата в поясе студента
                    hour: hour   // Час в поясе студента
                };

                // Форматируем дату для отображения (уже в поясе студента)
                const displayDate = new Date(date + 'T00:00:00');
                const formattedDate = displayDate.toLocaleDateString('ru-RU', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    weekday: 'long'
                });

                document.getElementById('bookingDate').textContent = formattedDate;
                document.getElementById('bookingTime').textContent =
                    hour + ':00 - ' + (parseInt(hour) + 1) + ':00';

                // Получаем имя преподавателя из данных компонента
                const teacherName = this.config.teacherName || 'Преподаватель';
                document.getElementById('bookingTeacher').textContent = teacherName;

                document.getElementById('bookingModal').style.display = 'block';
            },

            showAuthModal: function () {
                const authLinks = document.querySelectorAll(['a.personal'].join(','));

                if (authLinks.length > 0) {
                    authLinks[0].click();
                    return;
                }
            },

            confirmBooking: async function () {
                if (!this.selectedSlot) return;

                // Проверяем авторизацию при подтверждении записи
                if (!this.isAuthorized) {
                    this.hideModal('bookingModal');
                    this.showAuthModal();
                    return;
                }

                try {
                    const response = await BX.ajax.runComponentAction('xillix:schedule.booking', 'bookSlot', {
                        mode: 'class',
                        data: {
                            slotData: {
                                teacherId: this.teacherId,
                                slot_id: this.selectedSlot.id,
                                timezone: this.timezone // передаем часовой пояс пользователя
                            }
                        }
                    });

                    if (response.data?.success) {
                        this.showMessage(response.data.message || 'Вы успешно записались на урок');
                        this.hideModal('bookingModal');
                        await this.loadSchedule();
                    } else {
                        const errorMsg = response.data?.message || response.data?.error || 'Ошибка записи';
                        this.showError(errorMsg);
                    }
                } catch (error) {
                    console.error('Booking error:', error);
                    this.showError('Ошибка при записи на урок');
                }
            },

            isPastDate: function (dateString) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const cellDate = new Date(dateString + 'T00:00:00');
                return cellDate < today;
            },

            showViewModal: function (slotId) {
                const slot = this.scheduleData.find(s => s.ID == slotId);
                if (slot) {
                    this.setupModal(slot, true);
                }
            },

            showAddModal: function () {
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

            showEditModal: function (slotId) {
                const slot = this.scheduleData.find(s => s.ID == slotId);
                if (slot) {
                    this.setupModal(slot, false);
                }
            },

            setupModal: function (slot, isView = false) {
                document.getElementById('modalTitle').textContent = isView ?
                    (this.config.messages.VIEW_LESSON || 'Просмотр занятия') :
                    (this.config.messages.EDIT_LESSON || 'Редактирование занятия');

                document.getElementById('slotId').value = slot.ID;
                document.getElementById('slotDate').value = slot.UF_DATE;

                const startTime = new Date(slot.UF_START_TIME);
                const endTime = new Date(slot.UF_END_TIME);

                document.getElementById('startTime').value =
                    startTime.getHours().toString().padStart(2, '0') + ':' +
                    startTime.getMinutes().toString().padStart(2, '0');
                document.getElementById('endTime').value =
                    endTime.getHours().toString().padStart(2, '0') + ':' +
                    endTime.getMinutes().toString().padStart(2, '0');
                document.getElementById('subject').value = slot.UF_SUBJECT;

                if (isView || this.isPastDate(slot.UF_DATE)) {
                    this.setFormEditable(false);
                    document.getElementById('saveBtn').style.display = 'none';
                    document.getElementById('cancelBtn').textContent = this.config.messages.CLOSE || 'Закрыть';
                    if (this.isPastDate(slot.UF_DATE)) {
                        document.getElementById('modalTitle').textContent += ' (только просмотр)';
                    }
                } else {
                    this.setFormEditable(true);
                    document.getElementById('saveBtn').style.display = 'inline-block';
                    document.getElementById('cancelBtn').textContent = this.config.messages.CANCEL;
                }

                document.getElementById('scheduleModal').style.display = 'block';
            },

            setFormEditable: function (editable) {
                document.getElementById('slotDate').readOnly = !editable;
                document.getElementById('startTime').readOnly = !editable;
                document.getElementById('endTime').readOnly = !editable;
                document.getElementById('subject').disabled = !editable;
            },

            hideModal: function (modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                }
            },

            saveSlot: async function () {
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
                        this.showMessage('Данные сохранены успешно');
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

            changeTimezone: async function (newTimezone) {
                try {
                    // Сначала сохраняем часовой пояс
                    const response = await BX.ajax.runComponentAction('xillix:schedule.booking', 'saveTimezone', {
                        mode: 'class',
                        data: {
                            timezone: newTimezone
                        }
                    });

                    if (response.data?.success) {
                        // Затем обновляем часовой пояс и перезагружаем расписание
                        this.timezone = newTimezone;
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

            deleteSlot: async function (slotId) {
                if (!confirm('Вы уверены, что хотите удалить это занятие?')) {
                    return;
                }

                try {
                    const response = await BX.ajax.runComponentAction('xillix:schedule', 'deleteSlot', {
                        mode: 'class',
                        data: {slotId: slotId}
                    });

                    if (response.data?.success) {
                        await this.loadSchedule();
                        this.showMessage('Занятие удалено');
                    } else {
                        const errorMsg = response.data?.error || 'Неизвестная ошибка';
                        this.showError('Ошибка удаления: ' + errorMsg);
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    this.showError('Ошибка удаления');
                }
            },

            prevWeek: async function () {
                const prevWeek = new Date(this.currentWeek);
                prevWeek.setDate(prevWeek.getDate() - 7);

                // Всегда разрешаем переход на предыдущую неделю
                this.currentWeek = prevWeek;
                this.renderWeek();
                await this.loadSchedule();
            },

            nextWeek: async function () {
                this.currentWeek.setDate(this.currentWeek.getDate() + 7);
                this.renderWeek();
                await this.loadSchedule();
            },

            getWeekStartForDate: function (date) {
                const weekStart = new Date(date);
                const day = weekStart.getDay();
                const diff = weekStart.getDate() - day + (day === 0 ? -6 : 1);
                weekStart.setDate(diff);
                weekStart.setHours(0, 0, 0, 0);
                return weekStart;
            },

            renderWeek: function () {
                const weekStart = this.getWeekStart();
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekEnd.getDate() + 6);

                document.getElementById('currentWeek').textContent =
                    'Неделя ' + this.formatDate(weekStart) + ' - ' + this.formatDate(weekEnd);

                // Обновляем даты в заголовках
                for (let day = 1; day <= 7; day++) {
                    const date = new Date(weekStart);
                    date.setDate(date.getDate() + day - 1);

                    const dateElement = document.querySelector('.day-column[data-day="' + day + '"] .date');
                    if (dateElement) {
                        dateElement.textContent = this.formatDate(date);
                        dateElement.setAttribute('data-date', date.toISOString().split('T')[0]);
                    }
                }
            },

            updatePrevWeekButton: function () {
                const prevWeekBtn = document.getElementById('prevWeek');
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                const prevWeek = new Date(this.currentWeek);
                prevWeek.setDate(prevWeek.getDate() - 7);
                const prevWeekStart = this.getWeekStartForDate(prevWeek);

                // Отключаем кнопку, если предыдущая неделя раньше текущей
                if (prevWeekStart < today) {
                    prevWeekBtn.disabled = true;
                    prevWeekBtn.style.opacity = '0.5';
                    prevWeekBtn.style.cursor = 'not-allowed';
                } else {
                    prevWeekBtn.disabled = false;
                    prevWeekBtn.style.opacity = '1';
                    prevWeekBtn.style.cursor = 'pointer';
                }
            },


            // Вспомогательные методы
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
                return date.toLocaleDateString('ru-RU', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            },

            getDayName: function (dayNumber) {
                const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                return days[dayNumber - 1];
            },

            getStatusClass: function (status) {
                const classes = {
                    'free': 'free',
                    'blocked': 'blocked',
                    'canceled': 'canceled'
                };
                return classes[status] || 'free';
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

            delay: function (ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }
        };

    })();
} else {
    console.warn('BX is not defined. Xillix Schedule Booking component will not be available.');
}