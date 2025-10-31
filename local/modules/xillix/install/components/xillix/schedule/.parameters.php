<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    "PARAMETERS" => [
        "USER_ID" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("XILLIX_SCHEDULE_USER_ID"),
            "TYPE" => "STRING",
            "DEFAULT" => "={{\$USER->GetID()}}",
        ],
        "DEFAULT_DAY_ONLY" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("XILLIX_SCHEDULE_DEFAULT_DAY_ONLY"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        "CACHE_TIME" => [
            "DEFAULT" => 3600,
        ],
    ],
];
