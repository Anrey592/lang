<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */

\CJSCore::Init(['ajax', 'ui.notification']);

// Подключаем CSS и JS
$this->addExternalCSS($this->GetFolder() . '/style.css');
$this->addExternalJS($this->GetFolder() . '/script.js');
?>
<div id="xillixScheduleTemplateComponent" data-teacher-id="<?= $arResult['TEACHER_ID'] ?>">
    <div class="xillix-schedule-template">
        <div class="schedule-header">
            <h2><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_MY_SCHEDULE') ?></h2>

            <div class="schedule-controls">
                <div class="timezone-selector">
                    <label for="teacher-timezone-template"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_TIMEZONE') ?>:</label>
                    <select id="teacher-timezone-template" class="timezone-select">
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
                        <input type="checkbox" id="dayOnlyToggleSchedule" <?= $arResult['DEFAULT_DAY_ONLY'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        <?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_DAY_ONLY') ?>
                    </label>
                </div>

                <div class="template-actions">
                    <button class="btn btn-clear"
                            id="clearTemplate"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_CLEAR') ?></button>
                    <button class="btn btn-save"
                            id="saveTemplate"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_SAVE') ?></button>
                </div>
            </div>
        </div>

        <div class="schedule-table-container">
            <table class="schedule-template-table" id="scheduleTemplateTable">
                <thead>
                <tr>
                    <th class="time-column"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_TIME') ?></th>
                    <th class="day-column" data-day="1"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_MONDAY') ?></th>
                    <th class="day-column" data-day="2"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_TUESDAY') ?></th>
                    <th class="day-column" data-day="3"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_WEDNESDAY') ?></th>
                    <th class="day-column" data-day="4"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_THURSDAY') ?></th>
                    <th class="day-column" data-day="5"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_FRIDAY') ?></th>
                    <th class="day-column" data-day="6"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_SATURDAY') ?></th>
                    <th class="day-column" data-day="7"><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_SUNDAY') ?></th>
                </tr>
                </thead>
                <tbody id="scheduleTemplateBody">
                <!-- Время будет генерироваться через JavaScript -->
                </tbody>
            </table>
        </div>

        <div class="template-help">
            <p><?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_HELP') ?></p>
        </div>
    </div>
</div>

<script>
    BX.ready(function () {
        try {
            new BX.Xillix.ScheduleTemplate({
                componentId: 'xillixScheduleTemplateComponent',
                teacherId: <?= $arResult['TEACHER_ID'] ?>,
                defaultDayOnly: <?= $arResult['DEFAULT_DAY_ONLY'] ? 'true' : 'false' ?>,
                currentTimezone: '<?= $arResult['CURRENT_TIMEZONE'] ?>',
                signedParams: '<?= $arResult['SIGNED_PARAMS'] ?>',
                messages: {
                    SAVE_SUCCESS: '<?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_SAVE_SUCCESS') ?>',
                    CLEAR_SUCCESS: '<?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_CLEAR_SUCCESS') ?>',
                    ERROR: '<?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_ERROR') ?>',
                    CONFIRM_CLEAR: '<?= GetMessage('XILLIX_SCHEDULE_TEMPLATE_CONFIRM_CLEAR') ?>'
                }
            });
        } catch (e) {
            console.error('Xillix Schedule Template init error:', e);
        }
    });
</script>