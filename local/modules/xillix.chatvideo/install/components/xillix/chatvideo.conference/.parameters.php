<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
    "PARAMETERS" => [
        "MAX_PARTICIPANTS" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("XILLIX_CHATVIDEO_MAX_PARTICIPANTS"),
            "TYPE" => "STRING",
            "DEFAULT" => "10",
        ],
        "SHOW_ROOM_CREATION" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("XILLIX_CHATVIDEO_SHOW_ROOM_CREATION"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        "CACHE_TIME" => [
            "DEFAULT" => 3600,
        ],
    ],
];