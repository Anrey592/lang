<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => GetMessage('XILLIX_USER_PROFILE_COMPONENT_NAME'),
    'DESCRIPTION' => GetMessage('XILLIX_USER_PROFILE_COMPONENT_DESCRIPTION'),
    'PATH' => [
        'ID' => 'xillix',
        'NAME' => GetMessage('XILLIX_USER_PROFILE_COMPONENT_PATH_NAME'),
    ],
    'CACHE_PATH' => 'Y',
];