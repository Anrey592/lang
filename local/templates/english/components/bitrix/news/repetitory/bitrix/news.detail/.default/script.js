(function () {
    'use strict';

    class TeacherProfile {
        constructor() {
            this.scheduleVisible = false;
            this.init();
        }

        init() {
            this.bindEvents();
            this.detectTimezone();
        }

        bindEvents() {
            // Кнопка записи на урок
            const bookBtn = document.getElementById('bookLessonBtn');
            if (bookBtn) {
                bookBtn.addEventListener('click', () => {
                    this.toggleSchedule();
                });
            }

            // Кнопка закрытия расписания
            const closeBtn = document.getElementById('closeScheduleBtn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.hideSchedule();
                });
            }
        }

        toggleSchedule() {
            const container = document.getElementById('scheduleContainer');
            if (!container) return;

            if (this.scheduleVisible) {
                this.hideSchedule();
            } else {
                this.showSchedule();
            }
        }

        showSchedule() {
            const container = document.getElementById('scheduleContainer');
            const bookBtn = document.getElementById('bookLessonBtn');

            if (container && bookBtn) {
                container.style.display = 'block';
                bookBtn.textContent = 'Скрыть расписание';
                bookBtn.style.background = '#6c757d';
                this.scheduleVisible = true;

                // Прокрутка к расписанию
                container.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        }

        hideSchedule() {
            const container = document.getElementById('scheduleContainer');
            const bookBtn = document.getElementById('bookLessonBtn');

            if (container && bookBtn) {
                container.style.display = 'none';
                bookBtn.textContent = 'Записаться на урок';
                bookBtn.style.background = '#28a745';
                this.scheduleVisible = false;
            }
        }

        detectTimezone() {
            // Автоматическое определение часового пояса браузера
            try {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

                // Можно сохранить в cookie или передать в компонент
                this.setTimezoneCookie(timezone);
            } catch (error) {
                console.warn('Не удалось определить часовой пояс:', error);
            }
        }

        setTimezoneCookie(timezone) {
            // Устанавливаем cookie с часовым поясом
            document.cookie = `user_timezone=${timezone}; path=/; max-age=${60 * 60 * 24 * 30}`; // 30 дней
        }
    }

    // Инициализация при загрузке DOM
    document.addEventListener('DOMContentLoaded', function () {
        new TeacherProfile();
    });

})();