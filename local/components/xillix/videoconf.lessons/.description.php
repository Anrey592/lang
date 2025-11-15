<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_NAME'),
    'DESCRIPTION' => Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_DESC'),
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'xillix',
        'NAME' => 'Xillix'
    ]
];
