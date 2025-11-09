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
        $arParams['READONLY_FIELDS'] = $arParams['READONLY_FIELDS'] ?? [];
        $arParams['UF_FIELDS'] = $arParams['UF_FIELDS'] ?? [];
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

        // Инициализируем параметры
        $this->arResult['READONLY_FIELDS'] = is_array($this->arParams['READONLY_FIELDS'])
            ? $this->arParams['READONLY_FIELDS']
            : [];

        $this->prepareFields();
        $this->processForm();
        $this->prepareUserData();

        $this->includeComponentTemplate();
    }

    private function prepareFields()
    {
        // Загружаем языковые файлы из шаблона
        Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . $this->__path . '/lang/' . LANGUAGE_ID . '/template.php');

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

        $this->arResult['UF_FIELDS'] = [];
        if (!empty($this->arParams['UF_FIELDS']) && is_array($this->arParams['UF_FIELDS'])) {
            $userFieldEntity = \CUserTypeEntity::GetList(
                [],
                [
                    'ENTITY_ID' => 'USER',
                    'FIELD_NAME' => $this->arParams['UF_FIELDS'],
                    'LANG' => LANGUAGE_ID
                ]
            );

            while ($userField = $userFieldEntity->Fetch()) {
                $fieldName = $userField['FIELD_NAME'];
                if (in_array($fieldName, $this->arParams['UF_FIELDS'])) {
                    $label = $userField['EDIT_FORM_LABEL'] ?: $fieldName;

                    $this->arResult['UF_FIELDS'][$fieldName] = [
                        'NAME' => $label,
                        'TYPE' => $userField['USER_TYPE_ID'],
                        'SETTINGS' => $userField['SETTINGS'],
                        'ENTITY' => $userField,
                    ];
                }
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

            // Получаем значения UF-полей для отображения
            foreach ($this->arResult['UF_FIELDS'] as $fieldCode => $fieldInfo) {
                if (isset($userData[$fieldCode])) {
                    $this->arResult['USER_DATA'][$fieldCode] = $userData[$fieldCode];
                }
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
        $this->arResult['FORM_ERRORS'] = [];

        // Основные поля (исключаем readonly)
        foreach ($this->arResult['FIELDS'] as $fieldCode => $fieldName) {
            // Пропускаем поля только для чтения
            if (in_array($fieldCode, $this->arResult['READONLY_FIELDS'])) {
                continue;
            }

            if ($fieldCode === 'PERSONAL_PHOTO') {
                if (!empty($_FILES[$fieldCode]['name'])) {
                    $fields[$fieldCode] = $_FILES[$fieldCode];
                }
            } elseif (isset($_POST[$fieldCode])) {
                $fields[$fieldCode] = trim($_POST[$fieldCode]);

                // Базовая валидация обязательных полей
                if (in_array($fieldCode, ['NAME', 'EMAIL']) && empty($fields[$fieldCode])) {
                    $this->arResult['FORM_ERRORS'][$fieldCode] = Loc::getMessage('XILLIX_USER_PROFILE_FIELD_REQUIRED');
                }
            }
        }

        // Дополнительные поля UF (исключаем readonly)
        foreach ($this->arResult['UF_FIELDS'] as $fieldCode => $fieldInfo) {
            // Пропускаем UF-поля только для чтения
            if (in_array($fieldCode, $this->arResult['READONLY_FIELDS'])) {
                continue;
            }

            if (isset($_POST[$fieldCode])) {
                $fields[$fieldCode] = $_POST[$fieldCode];
            }
        }

        // Если есть ошибки валидации
        if (!empty($this->arResult['FORM_ERRORS'])) {
            $this->arResult['ERROR'] = Loc::getMessage('XILLIX_USER_PROFILE_VALIDATION_ERROR');
            $this->arResult['IS_EDIT_MODE'] = true;
            return;
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

    /**
     * Вспомогательный метод для проверки является ли поле readonly
     */
    public function isFieldReadonly($fieldCode)
    {
        return in_array($fieldCode, $this->arResult['READONLY_FIELDS']);
    }

    /**
     * Получение отображаемого значения UF-поля
     */
    public function getUfFieldDisplayValue($fieldInfo, $value)
    {
        if (empty($value)) {
            return Loc::getMessage('XILLIX_USER_PROFILE_NOT_SPECIFIED');
        }

        $entity = $fieldInfo['ENTITY'] ?? [];
        $settings = [
            'bVarsFromForm' => false,
            'arUserField' => array_merge($entity, [
                'USER_TYPE_ID' => $fieldInfo['TYPE'],
                'SETTINGS' => $fieldInfo['SETTINGS'],
            ]),
            'arUserFieldValue' => $value,
        ];

        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'bitrix:system.field.view',
            $fieldInfo['TYPE'],
            $settings
        );
        return ob_get_clean() ?: $value;
    }

    /**
     * Получение HTML-кода для редактирования UF-поля
     */
    public function getUfFieldInput($fieldInfo, $value, $fieldCode)
    {
        $entity = $fieldInfo['ENTITY'] ?? [];
        $settings = [
            'bVarsFromForm' => false,
            'FORM_NAME' => 'user_profile_form',
            'arUserField' => array_merge($entity, [
                'USER_TYPE_ID' => $fieldInfo['TYPE'],
                'SETTINGS' => $fieldInfo['SETTINGS'],
                'FIELD_NAME' => $fieldCode,
                'VALUE' => $value,
            ]),
            'arUserFieldValue' => $value,
        ];

        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'bitrix:system.field.edit',
            $fieldInfo['TYPE'],
            $settings
        );
        return ob_get_clean();
    }
}
