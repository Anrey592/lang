<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class UserProfileComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['FIELDS'] = $arParams['FIELDS'] ?? [];
        $arParams['ALLOW_EDIT'] = $arParams['ALLOW_EDIT'] ?? 'Y';
        $arParams['SHOW_AVATAR'] = $arParams['SHOW_AVATAR'] ?? 'Y';
        $arParams['CACHE_TIME'] = $arParams['CACHE_TIME'] ?? 3600;

        return $arParams;
    }

    public function executeComponent()
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            $this->arResult['ERROR'] = Loc::getMessage('XILLIX_USER_PROFILE_NOT_AUTHORIZED');
            $this->includeComponentTemplate();
            return;
        }

        Loader::includeModule('main');

        $this->arResult['USER_ID'] = $USER->GetID();
        $this->arResult['ALLOW_EDIT'] = $this->arParams['ALLOW_EDIT'] === 'Y';
        $this->arResult['SHOW_AVATAR'] = $this->arParams['SHOW_AVATAR'] === 'Y';
        $this->arResult['IS_EDIT_MODE'] = false;

        $this->prepareFields();
        $this->processForm();
        $this->prepareUserData();

        $this->includeComponentTemplate();
    }

    private function prepareFields()
    {
        // Загружаем языковые файлы из шаблона
        Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . __DIR__ . '/lang/' . LANGUAGE_ID . '/template.php');

        $defaultFields = [
            'PERSONAL_PHOTO' => Loc::getMessage('XILLIX_USER_PROFILE_PERSONAL_PHOTO'),
            'NAME' => Loc::getMessage('XILLIX_USER_PROFILE_NAME'),
            'LAST_NAME' => Loc::getMessage('XILLIX_USER_PROFILE_LAST_NAME'),
            'SECOND_NAME' => Loc::getMessage('XILLIX_USER_PROFILE_SECOND_NAME'),
            'EMAIL' => Loc::getMessage('XILLIX_USER_PROFILE_EMAIL'),
            'PERSONAL_PHONE' => Loc::getMessage('XILLIX_USER_PROFILE_PERSONAL_PHONE'),
            'PERSONAL_MOBILE' => Loc::getMessage('XILLIX_USER_PROFILE_PERSONAL_MOBILE'),
            'PERSONAL_BIRTHDAY' => Loc::getMessage('XILLIX_USER_PROFILE_PERSONAL_BIRTHDAY'),
            'PERSONAL_GENDER' => Loc::getMessage('XILLIX_USER_PROFILE_PERSONAL_GENDER'),
            'PERSONAL_COUNTRY' => Loc::getMessage('XILLIX_USER_PROFILE_PERSONAL_COUNTRY'),
            'PERSONAL_CITY' => Loc::getMessage('XILLIX_USER_PROFILE_PERSONAL_CITY'),
            'WORK_COMPANY' => Loc::getMessage('XILLIX_USER_PROFILE_WORK_COMPANY'),
            'WORK_POSITION' => Loc::getMessage('XILLIX_USER_PROFILE_WORK_POSITION'),
        ];

        // Если поля не указаны, используем все по умолчанию
        if (empty($this->arParams['FIELDS'])) {
            $this->arResult['FIELDS'] = $defaultFields;
        } else {
            $this->arResult['FIELDS'] = [];
            foreach ($this->arParams['FIELDS'] as $fieldCode) {
                if (isset($defaultFields[$fieldCode])) {
                    $this->arResult['FIELDS'][$fieldCode] = $defaultFields[$fieldCode];
                }
            }
        }

        // Дополнительные поля (UF_*)
        $this->arResult['UF_FIELDS'] = [];
        if (!empty($this->arParams['UF_FIELDS'])) {
            $userFieldEntity = \CUserTypeEntity::GetList(
                [],
                ['ENTITY_ID' => 'USER', 'FIELD_NAME' => $this->arParams['UF_FIELDS']]
            );

            while ($userField = $userFieldEntity->Fetch()) {
                $this->arResult['UF_FIELDS'][$userField['FIELD_NAME']] = [
                    'NAME' => $userField['EDIT_FORM_LABEL'][LANGUAGE_ID] ?: $userField['FIELD_NAME'],
                    'TYPE' => $userField['USER_TYPE_ID'],
                    'SETTINGS' => $userField['SETTINGS'],
                ];
            }
        }
    }

    private function prepareUserData()
    {
        global $USER;

        $userData = \CUser::GetByID($USER->GetID())->Fetch();

        if ($userData) {
            $this->arResult['USER_DATA'] = $userData;

            // Обработка фотографии
            if ($userData['PERSONAL_PHOTO'] > 0) {
                $file = \CFile::GetFileArray($userData['PERSONAL_PHOTO']);
                if ($file) {
                    $this->arResult['USER_DATA']['PERSONAL_PHOTO_SRC'] = $file['SRC'];
                }
            }

            // Обработка даты рождения
            if ($userData['PERSONAL_BIRTHDAY'] && $userData['PERSONAL_BIRTHDAY'] != '0000-00-00') {
                $this->arResult['USER_DATA']['PERSONAL_BIRTHDAY_FORMATTED'] = FormatDate('d.m.Y', MakeTimeStamp($userData['PERSONAL_BIRTHDAY']));
            }

            // Обработка пола
            $genderList = [
                'M' => Loc::getMessage('XILLIX_USER_PROFILE_GENDER_M'),
                'F' => Loc::getMessage('XILLIX_USER_PROFILE_GENDER_F'),
            ];
            if (isset($genderList[$userData['PERSONAL_GENDER']])) {
                $this->arResult['USER_DATA']['PERSONAL_GENDER_TEXT'] = $genderList[$userData['PERSONAL_GENDER']];
            }
        }
    }

    private function processForm()
    {
        global $USER;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->arResult['ALLOW_EDIT']) {
            return;
        }

        if (!check_bitrix_sessid()) {
            $this->arResult['ERROR'] = Loc::getMessage('XILLIX_USER_PROFILE_SESSION_ERROR');
            $this->arResult['IS_EDIT_MODE'] = true;
            return;
        }

        $user = new \CUser;
        $fields = [];

        // Основные поля
        foreach ($this->arResult['FIELDS'] as $fieldCode => $fieldName) {
            if ($fieldCode === 'PERSONAL_PHOTO') {
                if (!empty($_FILES[$fieldCode]['name'])) {
                    $fields[$fieldCode] = $_FILES[$fieldCode];
                }
            } elseif (isset($_POST[$fieldCode])) {
                $fields[$fieldCode] = $_POST[$fieldCode];
            }
        }

        // Дополнительные поля
        foreach ($this->arResult['UF_FIELDS'] as $fieldCode => $fieldInfo) {
            if (isset($_POST[$fieldCode])) {
                $fields[$fieldCode] = $_POST[$fieldCode];
            }
        }

        if (!empty($fields)) {
            if ($user->Update($USER->GetID(), $fields)) {
                $this->arResult['SUCCESS'] = Loc::getMessage('XILLIX_USER_PROFILE_UPDATE_SUCCESS');
                // Обновляем данные пользователя
                $this->prepareUserData();
            } else {
                $this->arResult['ERROR'] = $user->LAST_ERROR;
                $this->arResult['IS_EDIT_MODE'] = true;
            }
        }
    }
}