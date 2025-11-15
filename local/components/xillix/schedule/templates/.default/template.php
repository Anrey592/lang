<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */

\CJSCore::Init(['ajax', 'date', 'ui.notification']);

// Подключаем CSS и JS
$this->addExternalCSS($this->GetFolder() . '/style.css');
$this->addExternalJS($this->GetFolder() . '/script.js');

?>
<div id="xillixScheduleComponent" data-mode="<?= $arResult['MODE'] ?>">
    <div class="xillix-schedule">
        <div class="schedule-header">
            <h2>
                <?php if ($arResult['IS_STUDENT_MODE']): ?>
                    <?= GetMessage('XILLIX_SCHEDULE_MY_SCHEDULE') ?>
                <?php else: ?>
                    <?= GetMessage('XILLIX_SCHEDULE_MY_SCHEDULE') ?>
                <?php endif; ?>
            </h2>

            <div class="schedule-controls">
                <div class="timezone-selector">
                    <label for="teacher-timezone"><?= GetMessage('XILLIX_SCHEDULE_TIMEZONE') ?>:</label>
                    <select id="teacher-timezone" class="timezone-select">
                        <?php foreach ($arResult['TIMEZONES'] as $timezone): ?>
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
                        <?= GetMessage('XILLIX_SCHEDULE_DAY_ONLY') ?>
                    </label>
                </div>
            </div>

            <div class="week-navigation">
                <button class="btn btn-prev-week" id="prevWeek">
                    ← <?= GetMessage('XILLIX_SCHEDULE_PREV_WEEK') ?></button>
                <span class="current-week" id="currentWeek"></span>
                <button class="btn btn-next-week" id="nextWeek"><?= GetMessage('XILLIX_SCHEDULE_NEXT_WEEK') ?>→
                </button>
            </div>

            <?php if ($arResult['IS_TEACHER_MODE']): ?>
                <button class="btn btn-add-slot" id="addSlot">+ <?= GetMessage('XILLIX_SCHEDULE_ADD_SLOT') ?></button>
            <?php endif; ?>
        </div>

        <div class="schedule-table-container">
            <table class="schedule-table" id="scheduleTable">
                <thead>
                <tr>
                    <th class="time-column"><?= GetMessage('XILLIX_SCHEDULE_TIME') ?></th>
                    <th class="day-column" data-day="monday"><?= GetMessage('XILLIX_SCHEDULE_MONDAY') ?><br><span
                                class="date" data-date=""></span></th>
                    <th class="day-column" data-day="tuesday"><?= GetMessage('XILLIX_SCHEDULE_TUESDAY') ?><br><span
                                class="date" data-date=""></span></th>
                    <th class="day-column" data-day="wednesday"><?= GetMessage('XILLIX_SCHEDULE_WEDNESDAY') ?><br><span
                                class="date" data-date=""></span></th>
                    <th class="day-column" data-day="thursday"><?= GetMessage('XILLIX_SCHEDULE_THURSDAY') ?><br><span
                                class="date" data-date=""></span></th>
                    <th class="day-column" data-day="friday"><?= GetMessage('XILLIX_SCHEDULE_FRIDAY') ?><br><span
                                class="date" data-date=""></span></th>
                    <th class="day-column" data-day="saturday"><?= GetMessage('XILLIX_SCHEDULE_SATURDAY') ?><br><span
                                class="date" data-date=""></span></th>
                    <th class="day-column" data-day="sunday"><?= GetMessage('XILLIX_SCHEDULE_SUNDAY') ?><br><span
                                class="date" data-date=""></span></th>
                </tr>
                </thead>
                <tbody id="scheduleBody">
                <!-- Время будет генерироваться через JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования занятий (только для преподавателей) -->
<?php if ($arResult['IS_TEACHER_MODE']): ?>
    <div class="modal" id="scheduleModal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h3 id="modalTitle"><?= GetMessage('XILLIX_SCHEDULE_ADD_LESSON') ?></h3>

            <form id="scheduleForm">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="ajax_action" value="save_slot">
                <input type="hidden" id="slotId" name="slot_id">

                <div class="form-group">
                    <label for="slotDate"><?= GetMessage('XILLIX_SCHEDULE_DATE') ?>:</label>
                    <input type="date" id="slotDate" name="slot_date" required>
                </div>

                <div class="form-group">
                    <label for="startTime"><?= GetMessage('XILLIX_SCHEDULE_START_TIME') ?>:</label>
                    <input type="time" id="startTime" name="start_time" required>
                </div>

                <div class="form-group">
                    <label for="endTime"><?= GetMessage('XILLIX_SCHEDULE_END_TIME') ?>:</label>
                    <input type="time" id="endTime" name="end_time" required>
                </div>

                <div class="form-group" id="studentField" style="display: none;">
                    <label for="studentName">Ученик:</label>
                    <?php if ($arResult['IS_TEACHER_MODE']): ?>
                        <a href="/personal/ucheniki/uchenik/?student_id=" id="studentLink" target="_blank">
                            <span id="studentName"></span>
                        </a>
                    <?php else: ?>
                        <input type="text" id="studentName" name="student_name" readonly class="readonly-field">
                    <?php endif; ?>
                    <input type="hidden" id="studentId" name="student_id">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-cancel"
                            id="cancelBtn"><?= GetMessage('XILLIX_SCHEDULE_CANCEL') ?></button>
                    <button type="submit" class="btn btn-save"
                            id="saveBtn"><?= GetMessage('XILLIX_SCHEDULE_SAVE') ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Модальное окно для просмотра информации о занятии -->
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
                <strong>Статус:</strong>
                <span id="lessonStatus"></span>
            </div>
            <div class="detail-row" id="lessonLinkRow" style="display: none;">
                <strong>Ссылка на урок:</strong>
                <a href="#" id="lessonLink" target="_blank" rel="noopener">Перейти к уроку</a>
            </div>
            <div class="detail-row">
                <strong><?= $arResult['IS_STUDENT_MODE'] ? 'Преподаватель' : 'Ученик' ?>:</strong>
                <span id="personContainer">
                </span>
            </div>
            <?php if ($arResult['IS_TEACHER_MODE']): ?>
                <div class="detail-row" id="timezoneInfo" style="display: none;">
                    <strong>Часовой пояс:</strong>
                    <span id="timezoneNote">время конвертировано</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-cancel" id="closeStudentBtn">Закрыть</button>
        </div>
    </div>
</div>

<div class="modal" id="bookStudentModal">
    <div class="modal-content">
        <span class="close" id="closeBookStudentModal">&times;</span>
        <h3>Записать ученика на занятие</h3>

        <div class="booking-details">
            <p><strong>Дата:</strong> <span id="bookStudentDate"></span></p>
            <p><strong>Время:</strong> <span id="bookStudentTime"></span></p>
        </div>

        <form id="bookStudentForm">
            <?= bitrix_sessid_post() ?>
            <input type="hidden" id="bookStudentSlotId" name="slot_id">

            <div class="form-group">
                <label for="studentSelect">Выберите учеников (можно несколько):</label>
                <select id="studentSelect" name="student_ids[]" multiple required>
                    <option value="">-- Выберите учеников --</option>
                    <!-- Список учеников будет загружен через AJAX -->
                </select>
                <small class="form-text">Для выбора нескольких учеников удерживайте Ctrl</small>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-cancel" id="cancelBookStudentBtn">Отмена</button>
                <button type="submit" class="btn btn-confirm" id="confirmBookStudentBtn">Записать</button>
            </div>
        </form>
    </div>
</div>

<script>
    BX.ready(function () {
        try {
            new BX.Xillix.Schedule({
                componentId: 'xillixScheduleComponent',
                mode: '<?= $arResult['MODE'] ?>',
                isTeacherMode: <?= $arResult['IS_TEACHER_MODE'] ? 'true' : 'false' ?>,
                isStudentMode: <?= $arResult['IS_STUDENT_MODE'] ? 'true' : 'false' ?>,
                defaultDayOnly: <?= $arResult['DEFAULT_DAY_ONLY'] ? 'true' : 'false' ?>,
                currentTimezone: '<?= $arResult['CURRENT_TIMEZONE'] ?>',
                signedParams: '<?= $arResult['SIGNED_PARAMS'] ?>',
                messages: {
                    ADD_LESSON: '<?= GetMessage('XILLIX_SCHEDULE_ADD_LESSON') ?>',
                    EDIT_LESSON: '<?= GetMessage('XILLIX_SCHEDULE_EDIT_LESSON') ?>',
                    VIEW_LESSON: '<?= GetMessage('XILLIX_SCHEDULE_VIEW_LESSON') ?>',
                    SAVE_SUCCESS: '<?= GetMessage('XILLIX_SCHEDULE_SAVE_SUCCESS') ?>',
                    DELETE_SUCCESS: '<?= GetMessage('XILLIX_SCHEDULE_DELETE_SUCCESS') ?>',
                    CANCEL: '<?= GetMessage('XILLIX_SCHEDULE_CANCEL') ?>',
                    CLOSE: '<?= GetMessage('XILLIX_SCHEDULE_CLOSE') ?>'
                }
            });
        } catch (e) {
            console.error('Xillix Schedule init error:', e);
        }
    });
</script>