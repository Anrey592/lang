<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arDefaultOptions = [
    'voximplant_account_id' => '',
    'voximplant_app_id' => '',
    'voximplant_api_key' => '',
    'max_participants_default' => '10',
    'allow_guest_access' => 'N',
    'auto_create_room' => 'Y',
    'default_room_duration' => '60', // minutes
    'enable_recording' => 'N',
    'enable_screen_sharing' => 'Y',
    'enable_chat' => 'Y',
];

