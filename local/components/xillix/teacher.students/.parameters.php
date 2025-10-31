<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    "PARAMETERS" => [
        "TEACHER_ID" => [
            "NAME" => "ID преподавателя",
            "TYPE" => "STRING",
            "DEFAULT" => "={{\$USER->GetID()}}",
            "PARENT" => "BASE",
        ],
        "DETAIL_URL" => [
            "NAME" => "URL детальной страницы",
            "TYPE" => "STRING",
            "DEFAULT" => "",
            "PARENT" => "URL_TEMPLATES",
        ],
        "SET_TITLE" => [
            "NAME" => "Устанавливать заголовок",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "PARENT" => "ADDITIONAL_SETTINGS",
        ],
        "CACHE_TIME" => [
            "DEFAULT" => 36000000,
        ],
    ],
];