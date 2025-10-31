<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$APPLICATION->SetTitle(Loc::getMessage('XILLIX_SCHEDULE_ADMIN_TITLE'));

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
?>

    <div style="padding: 20px;">
        <h1><?= Loc::getMessage('XILLIX_SCHEDULE_ADMIN_TITLE') ?></h1>
        <p>Административная страница для просмотра расписания всех преподавателей.</p>
        <p>Здесь можно добавить функционал для администрирования расписания.</p>
    </div>

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
