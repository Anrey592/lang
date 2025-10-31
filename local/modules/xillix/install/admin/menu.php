<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$menu = [
    [
        'parent_menu' => 'global_menu_settings',
        'sort' => 1000,
        'text' => Loc::getMessage('XILLIX_MENU_TITLE'),
        'title' => Loc::getMessage('XILLIX_MENU_TITLE'),
        'url' => 'xillix_settings.php?lang=' . LANGUAGE_ID,
        'items_id' => 'menu_xillix_settings',
        'items' => [
            [
                'text' => Loc::getMessage('XILLIX_MENU_SETTINGS'),
                'title' => Loc::getMessage('XILLIX_MENU_SETTINGS'),
                'url' => 'xillix_settings.php?lang=' . LANGUAGE_ID,
                'more_url' => [],
            ],
            [
                'text' => Loc::getMessage('XILLIX_MENU_SCHEDULE'),
                'title' => Loc::getMessage('XILLIX_MENU_SCHEDULE'),
                'url' => 'xillix_schedule.php?lang=' . LANGUAGE_ID,
                'more_url' => [],
            ],
        ]
    ]
];

return $menu;