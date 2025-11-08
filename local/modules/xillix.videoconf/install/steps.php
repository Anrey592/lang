<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post">
    <?= bitrix_sessid_post() ?>
    <p><?= Loc::getMessage('XILLIX_VIDEOCONF_UNINSTALL_CONFIRM') ?></p>
    <label>
        <input type="checkbox" name="savedata" value="Y" checked>
        <?= Loc::getMessage('XILLIX_VIDEOCONF_UNINSTALL_SAVE_DATA') ?>
    </label>
    <input type="hidden" name="step" value="uninstall">
    <input type="submit" name="uninstall" value="<?= Loc::getMessage('MOD_UNINST_DEL') ?>">
</form>