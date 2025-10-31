<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$module_id = 'xillix';
$APPLICATION->SetTitle(Loc::getMessage('XILLIX_SETTINGS_TITLE'));

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

// Перенаправляем на стандартную страницу настроек модуля
LocalRedirect('/bitrix/admin/settings.php?lang=' . LANGUAGE_ID . '&mid=' . $module_id);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');