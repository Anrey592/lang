<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$aMenu = array(
    array(
        'parent_menu' => 'global_menu_settings',
        'sort' => 400,
        'text' => Loc::getMessage('XILLIX_TELEGRAM_MENU_TITLE'),
        'title' => Loc::getMessage('XILLIX_TELEGRAM_MENU_TITLE'),
        'url' => 'xillix_telegram_settings.php?lang=' . LANGUAGE_ID,
        'icon' => 'update_menu_icon',
        'items_id' => 'menu_xillix_telegram',
    )
);

return $aMenu;