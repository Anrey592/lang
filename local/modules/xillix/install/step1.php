<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<div style="padding: 20px; max-width: 800px; margin: 0 auto;">
    <h2><?= Loc::getMessage('XILLIX_INSTALL_TITLE') ?></h2>

    <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #2d6a4f;"><?= Loc::getMessage('XILLIX_MODULE_NAME') ?></h3>
        <p><?= Loc::getMessage('XILLIX_MODULE_DESC') ?></p>

        <h4>Что будет установлено:</h4>
        <ul>
            <li>Highload-блок для хранения расписания</li>
            <li>Пользовательское поле "Часовой пояс преподавателя"</li>
            <li>Компонент "xillix:schedule" для отображения расписания</li>
            <li>API для работы с расписанием</li>
        </ul>
    </div>

    <form action="<?= $APPLICATION->GetCurPage() ?>" method="post" style="margin-top: 30px;">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="lang" value="<?= LANG ?>">
        <input type="hidden" name="id" value="xillix">
        <input type="hidden" name="install" value="Y">
        <input type="hidden" name="step" value="2">

        <div style="display: flex; gap: 15px; margin-top: 20px;">
            <input type="submit" name="inst" value="<?= Loc::getMessage('MOD_INSTALL_BUTTON') ?>" class="adm-btn-save">
            <input type="button" name="cancel" value="<?= Loc::getMessage('MOD_CANCEL_BUTTON') ?>" onclick="window.location='<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>'" class="adm-btn">
        </div>
    </form>
</div>