<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UserFieldTable;

Loc::loadMessages(__FILE__);

class xillix_videoconf extends CModule
{
    public $MODULE_ID = 'xillix.videoconf';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage("XILLIX_VIDEOCONF_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("XILLIX_VIDEOCONF_MODULE_DESC");
        $this->PARTNER_NAME = Loc::getMessage("XILLIX_VIDEOCONF_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("XILLIX_VIDEOCONF_PARTNER_URI");
    }

    public function doInstall()
    {
        if (CheckVersion(ModuleManager::getVersion('main'), '14.00.00')) {
            ModuleManager::registerModule($this->MODULE_ID);
            if (Loader::includeModule($this->MODULE_ID)) {
                $this->installUserFields();
            } else {
                throw new SystemException(Loc::getMessage("XILLIX_MODULE_REGISTER_ERROR"));
            }
        } else {
            CAdminMessage::showMessage(
                Loc::getMessage('XILLIX_INSTALL_ERROR')
            );
            return;
        }
    }

    public function doUninstall()
    {
        global $APPLICATION;
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if ($request['step'] !== 'uninstall') {
            $APPLICATION->IncludeAdminFile("Удаление модуля", __DIR__ . '/steps.php');
        } else {
            if ($request['savedata'] !== 'Y') {
                $this->uninstallUserFields();
                COption::RemoveOption($this->MODULE_ID);
            }
            ModuleManager::unRegisterModule($this->MODULE_ID);
        }
    }

    private function installUserFields()
    {
        $uf = new CUserTypeEntity();
        $uf->Add([
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_TRUECONF_LOGIN',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => 'UF_TRUECONF_LOGIN',
            'SORT' => 500,
            'EDIT_FORM_LABEL' => ['ru' => 'Логин TrueConf'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Логин TrueConf'],
            'SETTINGS' => ['SIZE' => 30],
        ]);

        $uf->Add([
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_TRUECONF_PASSWORD',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => 'UF_TRUECONF_PASSWORD',
            'SORT' => 510,
            'EDIT_FORM_LABEL' => ['ru' => 'Пароль TrueConf'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Пароль TrueConf'],
            'SETTINGS' => ['SIZE' => 30],
        ]);
    }

    private function uninstallUserFields()
    {
        CUserTypeEntity::DeleteByXMLID('UF_TRUECONF_LOGIN');
        CUserTypeEntity::DeleteByXMLID('UF_TRUECONF_PASSWORD');
    }
}