<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$module_id = 'xillix';

// Обработка сохранения настроек
if ($_REQUEST['save'] && check_bitrix_sessid()) {
    Option::set($module_id, 'default_timezone', $_POST['default_timezone']);
    Option::set($module_id, 'day_only_default', $_POST['day_only_default'] ? 'Y' : 'N');
    Option::set($module_id, 'workday_start', $_POST['workday_start']);
    Option::set($module_id, 'workday_end', $_POST['workday_end']);
    Option::set($module_id, 'lesson_duration', intval($_POST['lesson_duration']));
    Option::set($module_id, 'max_lessons_per_day', intval($_POST['max_lessons_per_day']));

    CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('XILLIX_SETTINGS_SAVED'),
        'TYPE' => 'OK',
    ]);
}

// Получение текущих настроек
$default_timezone = Option::get($module_id, 'default_timezone', 'Europe/Moscow');
$day_only_default = Option::get($module_id, 'day_only_default', 'Y') === 'Y';
$workday_start = Option::get($module_id, 'workday_start', '08:00');
$workday_end = Option::get($module_id, 'workday_end', '22:00');
$lesson_duration = Option::get($module_id, 'lesson_duration', '60');
$max_lessons_per_day = Option::get($module_id, 'max_lessons_per_day', '8');

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('XILLIX_TAB_MAIN'),
        'TITLE' => Loc::getMessage('XILLIX_TAB_MAIN_TITLE'),
        'OPTIONS' => [
            [
                'default_timezone',
                Loc::getMessage('XILLIX_SETTINGS_DEFAULT_TIMEZONE'),
                $default_timezone,
                ['selectbox', \DateTimeZone::listIdentifiers()]
            ],
            [
                'day_only_default',
                Loc::getMessage('XILLIX_SETTINGS_DAY_ONLY_DEFAULT'),
                $day_only_default ? 'Y' : 'N',
                ['checkbox']
            ],
            Loc::getMessage('XILLIX_SETTINGS_WORKDAY'),
            [
                'workday_start',
                Loc::getMessage('XILLIX_SETTINGS_WORKDAY_START'),
                $workday_start,
                ['text', 5]
            ],
            [
                'workday_end',
                Loc::getMessage('XILLIX_SETTINGS_WORKDAY_END'),
                $workday_end,
                ['text', 5]
            ],
            Loc::getMessage('XILLIX_SETTINGS_LESSONS'),
            [
                'lesson_duration',
                Loc::getMessage('XILLIX_SETTINGS_LESSON_DURATION'),
                $lesson_duration,
                ['text', 5]
            ],
            [
                'max_lessons_per_day',
                Loc::getMessage('XILLIX_SETTINGS_MAX_LESSONS_PER_DAY'),
                $max_lessons_per_day,
                ['text', 5]
            ],
        ]
    ],
];

// Отображение формы
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<?php
$tabControl->Begin();
?>

    <form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
        <?= bitrix_sessid_post() ?>

        <?php
        foreach ($aTabs as $aTab) {
            if ($aTab['OPTIONS']) {
                $tabControl->BeginNextTab();
                __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
            }
        }

        $tabControl->Buttons(); ?>

        <input type="submit" name="save" value="<?= Loc::getMessage('XILLIX_SETTINGS_SAVE') ?>" class="adm-btn-save">
        <input type="submit" name="apply" value="<?= Loc::getMessage('XILLIX_SETTINGS_APPLY') ?>" >

    </form>

<?php
$tabControl->End();
