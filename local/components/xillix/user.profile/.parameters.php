<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'FIELDS' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('XILLIX_USER_PROFILE_PARAM_FIELDS'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'VALUES' => [
                'NAME' => GetMessage('XILLIX_USER_PROFILE_FIELD_NAME'),
                'LAST_NAME' => GetMessage('XILLIX_USER_PROFILE_FIELD_LAST_NAME'),
                'SECOND_NAME' => GetMessage('XILLIX_USER_PROFILE_FIELD_SECOND_NAME'),
                'EMAIL' => GetMessage('XILLIX_USER_PROFILE_FIELD_EMAIL'),
                'PERSONAL_PHONE' => GetMessage('XILLIX_USER_PROFILE_FIELD_PERSONAL_PHONE'),
                'PERSONAL_MOBILE' => GetMessage('XILLIX_USER_PROFILE_FIELD_PERSONAL_MOBILE'),
                'PERSONAL_BIRTHDAY' => GetMessage('XILLIX_USER_PROFILE_FIELD_PERSONAL_BIRTHDAY'),
                'PERSONAL_GENDER' => GetMessage('XILLIX_USER_PROFILE_FIELD_PERSONAL_GENDER'),
                'PERSONAL_COUNTRY' => GetMessage('XILLIX_USER_PROFILE_FIELD_PERSONAL_COUNTRY'),
                'PERSONAL_CITY' => GetMessage('XILLIX_USER_PROFILE_FIELD_PERSONAL_CITY'),
                'WORK_COMPANY' => GetMessage('XILLIX_USER_PROFILE_FIELD_WORK_COMPANY'),
                'WORK_POSITION' => GetMessage('XILLIX_USER_PROFILE_FIELD_WORK_POSITION'),
                'PERSONAL_PHOTO' => GetMessage('XILLIX_USER_PROFILE_FIELD_PERSONAL_PHOTO'),
            ],
            'DEFAULT' => ['NAME', 'LAST_NAME', 'EMAIL'],
            'ADDITIONAL_VALUES' => 'N',
        ],
        'UF_FIELDS' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('XILLIX_USER_PROFILE_PARAM_UF_FIELDS'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'ALLOW_EDIT' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('XILLIX_USER_PROFILE_PARAM_ALLOW_EDIT'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
        'SHOW_AVATAR' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('XILLIX_USER_PROFILE_PARAM_SHOW_AVATAR'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];