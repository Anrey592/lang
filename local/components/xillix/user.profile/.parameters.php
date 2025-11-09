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
        'READONLY_FIELDS' => array(
            'PARENT' => 'BASE',
            'NAME' => 'Поля только для чтения',
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'VALUES' => array(
                'PERSONAL_PHOTO' => 'Фотография',
                'NAME' => 'Имя',
                'LAST_NAME' => 'Фамилия',
                'SECOND_NAME' => 'Отчество',
                'EMAIL' => 'E-Mail',
                'PERSONAL_PHONE' => 'Телефон',
                'PERSONAL_MOBILE' => 'Мобильный',
                'PERSONAL_BIRTHDAY' => 'Дата рождения',
                'PERSONAL_GENDER' => 'Пол',
                'PERSONAL_COUNTRY' => 'Страна',
                'PERSONAL_CITY' => 'Город',
                'WORK_COMPANY' => 'Компания',
                'WORK_POSITION' => 'Должность',
            ),
            'DEFAULT' => array(),
        ),
        'UF_FIELDS' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('XILLIX_USER_PROFILE_PARAM_UF_FIELDS'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'VALUES' => array(),
            'DEFAULT' => array(),
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

$userTypeEntity = new CUserTypeEntity();
$rsData = $userTypeEntity->GetList(array(), array('ENTITY_ID' => 'USER'));
while ($arField = $rsData->Fetch()) {
    $arComponentParameters['PARAMETERS']['UF_FIELDS']['VALUES'][$arField['FIELD_NAME']] =
        '[' . $arField['FIELD_NAME'] . '] ' . ($arField['EDIT_FORM_LABEL'] ?: $arField['FIELD_NAME']);
}

// Динамическое получение UF-свойств для readonly
$userTypeEntity = new CUserTypeEntity();
$rsData = $userTypeEntity->GetList(array(), array('ENTITY_ID' => 'USER'));
while ($arField = $rsData->Fetch()) {
    $arComponentParameters['PARAMETERS']['READONLY_FIELDS']['VALUES'][$arField['FIELD_NAME']] =
        '[UF] ' . ($arField['EDIT_FORM_LABEL'] ?: $arField['FIELD_NAME']);
}