<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<div class="adm-info-message-wrap">
    <div class="adm-info-message">
        <p><strong><?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_TITLE') ?></strong></p>
        <p><?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_DESC') ?></p>

        <h3><?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_STEPS') ?>:</h3>
        <ol>
            <li><?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_STEP1') ?></li>
            <li><?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_STEP2') ?></li>
            <li><?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_STEP3') ?></li>
            <li><?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_STEP4') ?></li>
        </ol>

        <p><strong><?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_NEXT') ?></strong></p>
    </div>
</div>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="submit" name="" value="<?= Loc::getMessage('XILLIX_TELEGRAM_INSTALL_BACK') ?>">
</form>