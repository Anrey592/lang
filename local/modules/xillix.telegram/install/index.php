<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class xillix_telegram extends CModule
{
    public $MODULE_ID = "xillix.telegram";
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
        $this->MODULE_NAME = Loc::getMessage("XILLIX_TELEGRAM_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("XILLIX_TELEGRAM_MODULE_DESC");
        $this->PARTNER_NAME = Loc::getMessage("XILLIX_COMPANY");
        $this->PARTNER_URI = 'https://xillix.ru/';
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if (CheckVersion(ModuleManager::getVersion('main'), '14.00.00')) {
            ModuleManager::registerModule($this->MODULE_ID);
            if (Loader::includeModule($this->MODULE_ID)) {
                $this->InstallDB();
                $this->InstallFiles();

                $toolsDir = Application::getDocumentRoot() . "/bitrix/tools/" . $this->MODULE_ID;
                if (!is_dir($toolsDir)) {
                    mkdir($toolsDir, 0755, true);
                }
            } else {
                throw new SystemException(Loc::getMessage("VASOFT_LIKEIT_MODULE_REGISTER_ERROR"));
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

        $this->UnInstallDB(['savedata' => $saveData]);
        $this->UnInstallFiles();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->includeAdminFile(
            Loc::getMessage('XILLIX_UNINSTALL_TITLE') . ' «' . Loc::getMessage('XILLIX_NAME') . '»',
            __DIR__ . '/uninstall.php'
        );
    }

    public function InstallDB()
    {
        // Добавляем пользовательское поле для chat_id
        $userTypeEntity = new CUserTypeEntity();

        $field = $userTypeEntity->GetList([], [
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_TELEGRAM_CHAT_ID'
        ])->Fetch();

        if (!$field) {
            $userTypeEntity->Add([
                'ENTITY_ID' => 'USER',
                'FIELD_NAME' => 'UF_TELEGRAM_CHAT_ID',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'TELEGRAM_CHAT_ID',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'I',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' => array('ru' => 'ID чата Telegram', 'en' => 'Telegram Chat ID'),
            ]);
        }

        if (Loader::includeModule($this->MODULE_ID)) {
            $connection = \Bitrix\Main\Application::getConnection();

            $this->createOrUpdateStateTable($connection);

            // Создаем или обновляем таблицу временных данных
            $this->createOrUpdateTempTable($connection);

            Option::set($this->MODULE_ID, 'TELEGRAM_BOT_TOKEN', '8453534744:AAFbz8szQTNuN5h9nPTTTWC1FruOxGrEYw4');

            if (Loader::includeModule($this->MODULE_ID)) {
                $bot = new \Xillix\Telegram\Bot();
                $bot->setMyCommands();
            }

        } else {
            throw new SystemException(Loc::getMessage("VASOFT_LIKEIT_MODULE_REGISTER_ERROR"));
        }
        return true;
    }

    private function createOrUpdateStateTable($connection)
    {
        $tableName = \Xillix\Telegram\StateTable::getTableName();

        if (!$connection->isTableExists($tableName)) {
            \Xillix\Telegram\StateTable::getEntity()->createDbTable();
            $connection->queryExecute("
            ALTER TABLE {$tableName} 
            MODIFY CHAT_ID BIGINT NOT NULL
        ");
        } else {
            $connection->queryExecute("
            ALTER TABLE {$tableName} 
            MODIFY CHAT_ID BIGINT NOT NULL
        ");
        }
    }

    private function createOrUpdateTempTable($connection)
    {
        $tableName = \Xillix\Telegram\TempTable::getTableName();

        if (!$connection->isTableExists($tableName)) {
            \Xillix\Telegram\TempTable::getEntity()->createDbTable();
            $connection->queryExecute("
            ALTER TABLE {$tableName} 
            MODIFY CHAT_ID BIGINT NOT NULL
        ");
        } else {
            $connection->queryExecute("
            ALTER TABLE {$tableName} 
            MODIFY CHAT_ID BIGINT NOT NULL
        ");
        }
    }

    public function UnInstallDB()
    {
        $saveData = $arParams['savedata'] ?? false;

        if (!$saveData) {
            $userTypeEntity = new CUserTypeEntity();
            $field = $userTypeEntity->GetList([], ['FIELD_NAME' => 'UF_TELEGRAM_CHAT_ID'])->Fetch();

            if ($field) {
                $userTypeEntity->Delete($field['ID']);
            }

            $connection = \Bitrix\Main\Application::getConnection();

            if ($connection->isTableExists(\Xillix\Telegram\StateTable::getTableName())) {
                $connection->queryExecute('DROP TABLE ' . \Xillix\Telegram\StateTable::getTableName());
            }

            if ($connection->isTableExists(\Xillix\Telegram\TempTable::getTableName())) {
                $connection->queryExecute('DROP TABLE ' . \Xillix\Telegram\TempTable::getTableName());
            }
        }

        Option::delete($this->MODULE_ID);

        return true;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . "/admin",
            Application::getDocumentRoot() . "/bitrix/admin/",
            true, true
        );

        CopyDirFiles(
            __DIR__ . "/options.php",
            Application::getDocumentRoot() . "/local/modules/" . $this->MODULE_ID . "/",
            true, true
        );

        $toolsDir = Application::getDocumentRoot() . "/bitrix/tools/" . $this->MODULE_ID;
        if (!is_dir($toolsDir)) {
            mkdir($toolsDir, 0755, true);
        }

        CopyDirFiles(
            __DIR__ . "/tools",
            Application::getDocumentRoot() . "/bitrix/tools/" . $this->MODULE_ID . "/",
            true, true
        );

        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/admin/xillix_telegram_settings.php");

        DeleteDirFilesEx("/bitrix/tools/" . $this->MODULE_ID);

        return true;
    }
}