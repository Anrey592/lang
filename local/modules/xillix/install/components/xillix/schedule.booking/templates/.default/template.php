<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */

// Обязательно инициализируем Bitrix API
\Bitrix\Main\UI\Extension::load("ui.notification");
\CJSCore::Init(['ajax', 'date']);

$this->addExternalCSS($this->GetFolder() . '/style.css');
$this->addExternalJS($this->GetFolder() . '/script.js');

// Получаем имя преподавателя для отображения в модальном окне
$teacherName = $arParams['TEACHER_NAME'] ?? 'Преподаватель';
?>
<div id="xillixScheduleBookingComponent" data-teacher-id="<?= $arResult['TEACHER_ID'] ?>">
    <div class="xillix-schedule-booking">
        <div class="schedule-header">
            <div class="schedule-controls">
                <div class="timezone-selector">
                    <label for="student-timezone">Ваш часовой пояс:</label>
                    <select id="student-timezone" class="timezone-select">
                        <?php foreach ($arResult['TIMEZONES_SORTED'] as $timezone): ?>
                            <option value="<?= htmlspecialcharsbx($timezone) ?>"
                                <?= $timezone == $arResult['CURRENT_TIMEZONE'] ? 'selected' : '' ?>>
                                <?= Xillix\TeacherScheduleManager::getTimezoneWithOffset($timezone) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="day-only-toggle">
                    <label class="checkbox-label">
                        <input type="checkbox" id="dayOnlyToggle" <?= $arResult['DEFAULT_DAY_ONLY'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Только день (8:00-22:00)
                    </label>
                </div>
            </div>
        </div>

        <div class="week-navigation">
            <button class="btn btn-prev-week" id="prevWeek">← Предыдущая неделя</button>
            <span class="current-week" id="currentWeek"></span>
            <button class="btn btn-next-week" id="nextWeek">Следующая неделя →</button>
        </div>

        <div class="schedule-table-container">
            <table class="schedule-table" id="scheduleTable">
                <thead>
                <tr>
                    <th class="time-column">Время</th>
                    <th class="day-column" data-day="1">Понедельник<br><span class="date" data-date=""></span></th>
                    <th class="day-column" data-day="2">Вторник<br><span class="date" data-date=""></span></th>
                    <th class="day-column" data-day="3">Среда<br><span class="date" data-date=""></span></th>
                    <th class="day-column" data-day="4">Четверг<br><span class="date" data-date=""></span></th>
                    <th class="day-column" data-day="5">Пятница<br><span class="date" data-date=""></span></th>
                    <th class="day-column" data-day="6">Суббота<br><span class="date" data-date=""></span></th>
                    <th class="day-column" data-day="7">Воскресенье<br><span class="date" data-date=""></span></th>
                </tr>
                </thead>
                <tbody id="scheduleBody">
                <!-- Время будет генерироваться через JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Подтверждение записи</h3>

        <div class="booking-details">
            <p><strong>Преподаватель:</strong> <span id="bookingTeacher"><?= htmlspecialcharsbx($teacherName) ?></span>
            </p>
            <p><strong>Дата:</strong> <span id="bookingDate"></span></p>
            <p><strong>Время:</strong> <span id="bookingTime"></span></p>
        </div>

        <div class="booking-link">
            <a href="/blog/chto-vzyat-na-urok/" target="_blank">Что взять на урок?</a>
        </div>

        <div class="booking-actions">
            <button type="button" class="btn btn-cancel" id="cancelBookingBtn">Отмена</button>
            <button type="button" class="btn btn-confirm" id="confirmBookingBtn">Подтвердить запись</button>
        </div>
    </div>
</div>

<div class="modal" id="studentLessonModal">
    <div class="modal-content">
        <span class="close" id="closeStudentModal">&times;</span>
        <h3>Информация о занятии</h3>

        <div class="lesson-details">
            <div class="detail-row">
                <strong>Дата:</strong>
                <span id="lessonDate"></span>
            </div>
            <div class="detail-row">
                <strong>Время:</strong>
                <span id="lessonTime"></span>
            </div>
            <div class="detail-row">
                <strong>Преподаватель:</strong>
                <span id="teacherContainer"></span>
            </div>
            <div class="detail-row">
                <strong>Статус:</strong>
                <span id="lessonStatus"></span>
            </div>
            <div class="detail-row" id="lessonLinkRow" style="display: none;">
                <strong>Ссылка на урок:</strong>
                <a href="/personal/raspisanie/" id="lessonLink" target="_blank" rel="noopener">Перейти в личный кабинет</a>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-cancel" id="closeStudentBtn">Закрыть</button>
        </div>
    </div>
</div>

<script>
    // Ожидаем полной загрузки страницы и Bitrix API
    document.addEventListener('DOMContentLoaded', function () {
        // Дополнительная проверка на наличие BX
        if (typeof BX === 'undefined') {
            console.error('BX is not defined. Waiting for Bitrix API...');
            // Пытаемся подождать загрузку Bitrix
            setTimeout(function () {
                if (typeof BX !== 'undefined') {
                    initializeComponent();
                } else {
                    console.error('Bitrix API still not loaded. Component cannot be initialized.');
                    // Альтернативная инициализация без BX
                    initializeWithoutBX();
                }
            }, 1000);
        } else {
            initializeComponent();
        }

        function initializeComponent() {
            try {
                new BX.Xillix.ScheduleBooking({
                    componentId: 'xillixScheduleBookingComponent',
                    teacherId: <?= $arResult['TEACHER_ID'] ?>,
                    teacherName: '<?= $teacherName ?>',
                    isAuthorized: <?= $arResult['IS_AUTHORIZED'] ? 'true' : 'false' ?>,
                    defaultDayOnly: <?= $arResult['DEFAULT_DAY_ONLY'] ? 'true' : 'false' ?>,
                    currentTimezone: '<?= $arResult['CURRENT_TIMEZONE'] ?>',
                    signedParams: '<?= $arResult['SIGNED_PARAMS'] ?>'
                });
            } catch (e) {
                console.error('Xillix Schedule Booking init error:', e);
            }
        }

        function initializeWithoutBX() {
            console.warn('Initializing without BX. Some features may not work.');
            // Базовая инициализация без Bitrix API
            const component = document.getElementById('xillixScheduleBookingComponent');
            if (component) {
                console.log('Component found, but BX is not available');
                // Можно добавить базовый функционал без BX
            }
        }
    });
</script>