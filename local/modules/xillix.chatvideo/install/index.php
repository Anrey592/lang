<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

class xillix_chatvideo extends CModule
{
    public $MODULE_ID = "xillix.chatvideo";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("XILLIX_CHATVIDEO_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("XILLIX_CHATVIDEO_MODULE_DESC");
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if (CheckVersion(ModuleManager::getVersion('main'), '14.00.00')) {
            ModuleManager::registerModule($this->MODULE_ID);
            if (Loader::includeModule($this->MODULE_ID)) {
                $this->InstallDB();
                $this->InstallFiles();
                $this->InstallEvents();
            } else {
                throw new SystemException(Loc::getMessage("XILLIX_MODULE_REGISTER_ERROR"));
            }
        } else {
            CAdminMessage::showMessage(
                Loc::getMessage('XILLIX_INSTALL_ERROR')
            );
            return;
        }

        $APPLICATION->includeAdminFile(
            Loc::getMessage('XILLIX_INSTALL_TITLE') . ' «' . Loc::getMessage('XILLIX_NAME') . '»',
            __DIR__ . '/install.php'
        );
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $saveData = trim($request->get('savedata')) === 'Y';

        Loader::includeModule($this->MODULE_ID);

        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->UnInstallEvents();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallDB()
    {
        Loader::includeModule('highloadblock');

        // Создание Highload-блока для комнат
        $result = $this->createHighloadBlock('ChatVideoRooms', 'xillix_chatvideo_rooms');
        if (!$result) {
            return false;
        }

        // Создание пользовательских полей для комнат
        $this->createRoomFields($result);

        // Создание Highload-блока для участников
        $result = $this->createHighloadBlock('ChatVideoParticipants', 'xillix_chatvideo_participants');
        if (!$result) {
            return false;
        }

        // Создание пользовательских полей для участников
        $this->createParticipantFields($result);

        return true;
    }

    private function createHighloadBlock($name, $tableName)
    {
        $hlblock = new \Bitrix\Highloadblock\HighloadBlockTable();

        // Проверяем, существует ли уже блок
        $dbRes = $hlblock->getList([
            'filter' => ['=NAME' => $name]
        ]);

        if ($dbRes->fetch()) {
            return $dbRes->fetch(); // Возвращаем существующий блок
        }

        $result = $hlblock->add([
            'NAME' => $name,
            'TABLE_NAME' => $tableName,
        ]);

        if ($result->isSuccess()) {
            return $hlblock->getById($result->getId())->fetch();
        }

        return false;
    }

    private function createRoomFields($hlblock)
    {
        if (!$hlblock) return false;

        $userTypeEntity = new CUserTypeEntity();

        $fields = [
            [
                'FIELD_NAME' => 'UF_NAME',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'ROOM_NAME',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => ['ru' => 'Название комнаты', 'en' => 'Room name'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Название', 'en' => 'Name'],
                'LIST_FILTER_LABEL' => ['ru' => 'Название комнаты', 'en' => 'Room name'],
            ],
            [
                'FIELD_NAME' => 'UF_ROOM_ID',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'ROOM_ID',
                'SORT' => 200,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => ['ru' => 'ID комнаты', 'en' => 'Room ID'],
                'LIST_COLUMN_LABEL' => ['ru' => 'ID комнаты', 'en' => 'Room ID'],
                'LIST_FILTER_LABEL' => ['ru' => 'ID комнаты', 'en' => 'Room ID'],
            ],
            [
                'FIELD_NAME' => 'UF_HASH',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'ROOM_HASH',
                'SORT' => 300,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => ['ru' => 'Хеш комнаты', 'en' => 'Room hash'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Хеш', 'en' => 'Hash'],
                'LIST_FILTER_LABEL' => ['ru' => 'Хеш комнаты', 'en' => 'Room hash'],
            ],
            [
                'FIELD_NAME' => 'UF_CREATED_BY',
                'USER_TYPE_ID' => 'integer',
                'XML_ID' => 'CREATED_BY',
                'SORT' => 400,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Создатель', 'en' => 'Created by'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Создатель', 'en' => 'Created by'],
                'LIST_FILTER_LABEL' => ['ru' => 'Создатель', 'en' => 'Created by'],
            ],
            [
                'FIELD_NAME' => 'UF_CREATED_AT',
                'USER_TYPE_ID' => 'datetime',
                'XML_ID' => 'CREATED_AT',
                'SORT' => 500,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Дата создания', 'en' => 'Created at'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Создано', 'en' => 'Created'],
                'LIST_FILTER_LABEL' => ['ru' => 'Дата создания', 'en' => 'Created at'],
            ],
            [
                'FIELD_NAME' => 'UF_ACTIVE',
                'USER_TYPE_ID' => 'boolean',
                'XML_ID' => 'ACTIVE',
                'SORT' => 600,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Активна', 'en' => 'Active'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Активна', 'en' => 'Active'],
                'LIST_FILTER_LABEL' => ['ru' => 'Активна', 'en' => 'Active'],
                'SETTINGS' => [
                    'DEFAULT_VALUE' => 1,
                ],
            ],
            [
                'FIELD_NAME' => 'UF_MAX_PARTICIPANTS',
                'USER_TYPE_ID' => 'integer',
                'XML_ID' => 'MAX_PARTICIPANTS',
                'SORT' => 700,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Макс. участников', 'en' => 'Max participants'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Макс. участников', 'en' => 'Max participants'],
                'LIST_FILTER_LABEL' => ['ru' => 'Макс. участников', 'en' => 'Max participants'],
                'SETTINGS' => [
                    'DEFAULT_VALUE' => 10,
                ],
            ],
        ];

        foreach ($fields as $field) {
            $this->createUserField($userTypeEntity, $hlblock['ID'], $field);
        }

        return true;
    }

    private function createParticipantFields($hlblock)
    {
        if (!$hlblock) return false;

        $userTypeEntity = new CUserTypeEntity();

        $fields = [
            [
                'FIELD_NAME' => 'UF_ROOM_ID',
                'USER_TYPE_ID' => 'integer',
                'XML_ID' => 'PARTICIPANT_ROOM_ID',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'ID комнаты', 'en' => 'Room ID'],
                'LIST_COLUMN_LABEL' => ['ru' => 'ID комнаты', 'en' => 'Room ID'],
                'LIST_FILTER_LABEL' => ['ru' => 'ID комнаты', 'en' => 'Room ID'],
            ],
            [
                'FIELD_NAME' => 'UF_USER_ID',
                'USER_TYPE_ID' => 'integer',
                'XML_ID' => 'PARTICIPANT_USER_ID',
                'SORT' => 200,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'ID пользователя', 'en' => 'User ID'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Пользователь', 'en' => 'User'],
                'LIST_FILTER_LABEL' => ['ru' => 'ID пользователя', 'en' => 'User ID'],
            ],
            [
                'FIELD_NAME' => 'UF_SESSION_ID',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'SESSION_ID',
                'SORT' => 300,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => ['ru' => 'ID сессии', 'en' => 'Session ID'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Сессия', 'en' => 'Session'],
                'LIST_FILTER_LABEL' => ['ru' => 'ID сессии', 'en' => 'Session ID'],
            ],
            [
                'FIELD_NAME' => 'UF_JOINED_AT',
                'USER_TYPE_ID' => 'datetime',
                'XML_ID' => 'JOINED_AT',
                'SORT' => 400,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Присоединился', 'en' => 'Joined at'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Присоединился', 'en' => 'Joined'],
                'LIST_FILTER_LABEL' => ['ru' => 'Присоединился', 'en' => 'Joined at'],
            ],
            [
                'FIELD_NAME' => 'UF_LEFT_AT',
                'USER_TYPE_ID' => 'datetime',
                'XML_ID' => 'LEFT_AT',
                'SORT' => 500,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Покинул', 'en' => 'Left at'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Покинул', 'en' => 'Left'],
                'LIST_FILTER_LABEL' => ['ru' => 'Покинул', 'en' => 'Left at'],
            ],
            [
                'FIELD_NAME' => 'UF_IS_ACTIVE',
                'USER_TYPE_ID' => 'boolean',
                'XML_ID' => 'IS_ACTIVE',
                'SORT' => 600,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Активен', 'en' => 'Is active'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Активен', 'en' => 'Active'],
                'LIST_FILTER_LABEL' => ['ru' => 'Активен', 'en' => 'Is active'],
                'SETTINGS' => [
                    'DEFAULT_VALUE' => 1,
                ],
            ],
        ];

        foreach ($fields as $field) {
            $this->createUserField($userTypeEntity, $hlblock['ID'], $field);
        }

        return true;
    }

    private function createUserField($userTypeEntity, $hlblockId, $fieldData)
    {
        // Проверяем, существует ли уже поле
        $existingField = $userTypeEntity->GetList(
            [],
            [
                'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                'FIELD_NAME' => $fieldData['FIELD_NAME']
            ]
        )->Fetch();

        if ($existingField) {
            return $existingField['ID'];
        }

        $fieldData['ENTITY_ID'] = 'HLBLOCK_' . $hlblockId;

        $result = $userTypeEntity->Add($fieldData);

        return $result;
    }

    public function UnInstallDB($arParams = [])
    {
        $saveData = $arParams['savedata'] ?? false;

        if (!$saveData) {
            Loader::includeModule('highloadblock');

            // Удаляем Highload-блоки
            $hlblock = new \Bitrix\Highloadblock\HighloadBlockTable();

            // Удаляем блок комнат
            $roomsBlock = $hlblock->getList([
                'filter' => ['=NAME' => 'ChatVideoRooms']
            ])->fetch();

            if ($roomsBlock) {
                \Bitrix\Highloadblock\HighloadBlockTable::delete($roomsBlock['ID']);
            }

            // Удаляем блок участников
            $participantsBlock = $hlblock->getList([
                'filter' => ['=NAME' => 'ChatVideoParticipants']
            ])->fetch();

            if ($participantsBlock) {
                \Bitrix\Highloadblock\HighloadBlockTable::delete($participantsBlock['ID']);
            }

            $connection = Application::getConnection();

            // Удаляем таблицы если они существуют
            if ($connection->isTableExists('b_hlbd_xillix_chatvideo_rooms')) {
                $connection->dropTable('b_hlbd_xillix_chatvideo_rooms');
            }

            if ($connection->isTableExists('b_hlbd_xillix_chatvideo_participants')) {
                $connection->dropTable('b_hlbd_xillix_chatvideo_participants');
            }
        }

        Option::delete($this->MODULE_ID);
        return true;
    }

    public function InstallFiles()
    {
        // Копируем компонент в local/components/
        if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/local/modules/xillix.chatvideo/install/components")) {
            CopyDirFiles(
                $_SERVER["DOCUMENT_ROOT"] . "/local/modules/xillix.chatvideo/install/components",
                $_SERVER["DOCUMENT_ROOT"] . "/local/components",
                true, true
            );
        }
        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFilesEx("/local/components/xillix/chatvideo.conference");
        return true;
    }
}