<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

$module_id = 'xillix.videoconf';

if ($REQUEST_METHOD === 'POST' && check_bitrix_sessid() && $GLOBALS['APPLICATION']->GetGroupRight($module_id) >= 'W') {
    Option::set($module_id, 'server_domain', trim($_POST['server_domain']));
    Option::set($module_id, 'client_id', trim($_POST['client_id']));
    Option::set($module_id, 'client_secret', trim($_POST['client_secret']));
    Option::set($module_id, 'default_owner', trim($_POST['default_owner']));

    CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('XILLIX_VIDEOCONF_SETTINGS_SAVED'),
        'TYPE' => 'OK'
    ]);
}

// Получаем текущие значения
$server_domain = Option::get($module_id, 'server_domain', '');
$client_id = Option::get($module_id, 'client_id', '');
$client_secret = Option::get($module_id, 'client_secret', '');
$default_owner = Option::get($module_id, 'default_owner', 'tcadmin');

// Подключаем стандартный интерфейс настроек
$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('XILLIX_VIDEOCONF_TAB_MAIN'),
        'ICON' => 'main_user_edit',
        'TITLE' => Loc::getMessage('XILLIX_VIDEOCONF_TAB_MAIN_TITLE')
    ]
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

$tabControl->Begin();
?>
<form method="post" action="<?= $_SERVER['REQUEST_URI'] ?>" enctype="multipart/form-data">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td>Server ID:</td>
        <td>
            <input type="text" name="server_id" value="<?=htmlspecialcharsbx(Option::get($module_id, 'server_id', 'ru4skl'))?>" size="30">
            <br><small><?= Loc::getMessage('XILLIX_VIDEOCONF_SERVER_ID') ?></small>
        </td>
    </tr>
    <tr>
        <td width="40%"><?= Loc::getMessage('XILLIX_VIDEOCONF_SERVER_DOMAIN') ?>:</td>
        <td width="60%">
            <input type="text" name="server_domain" value="<?= htmlspecialcharsbx($server_domain) ?>" size="50">
            <br><small><?= Loc::getMessage('XILLIX_VIDEOCONF_SERVER_DOMAIN_HINT') ?></small>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_VIDEOCONF_CLIENT_ID') ?>:</td>
        <td><input type="text" name="client_id" value="<?= htmlspecialcharsbx($client_id) ?>" size="50"></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_VIDEOCONF_CLIENT_SECRET') ?>:</td>
        <td><input type="text" name="client_secret" value="<?= htmlspecialcharsbx($client_secret) ?>" size="50"></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_VIDEOCONF_DEFAULT_OWNER') ?>:</td>
        <td>
            <input type="text" name="default_owner" value="<?= htmlspecialcharsbx($default_owner) ?>" size="30">
            <br><small><?= Loc::getMessage('XILLIX_VIDEOCONF_DEFAULT_OWNER_HINT') ?></small>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="<?= Loc::getMessage('MAIN_SAVE') ?>" class="adm-btn-save">
    <?php $tabControl->End(); ?>
</form>