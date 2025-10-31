<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$step = intval($_REQUEST['step'] ?? 1);
?>

<div style="padding: 20px; max-width: 800px; margin: 0 auto;">
    <h2><?= Loc::getMessage('XILLIX_UNINSTALL_TITLE') ?></h2>

    <?php if ($step == 1): ?>

        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ffeaa7;">
            <h4 style="margin-top: 0; color: #856404;">Внимание!</h4>
            <p>При удалении модуля будут удалены все данные расписания. Это действие нельзя отменить.</p>

            <label style="display: flex; align-items: center; margin-top: 15px; cursor: pointer;">
                <input type="checkbox" id="confirmDelete" name="confirm_delete" style="margin-right: 10px;">
                <span>Я понимаю, что все данные будут удалены без возможности восстановления</span>
            </label>
        </div>

        <form action="<?= $APPLICATION->GetCurPage() ?>" method="post" id="uninstallForm" style="margin-top: 30px;">
            <?= bitrix_sessid_post() ?>
            <input type="hidden" name="lang" value="<?= LANG ?>">
            <input type="hidden" name="id" value="xillix">
            <input type="hidden" name="uninstall" value="Y">
            <input type="hidden" name="step" value="2">

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <input type="submit" name="uninst" value="<?= Loc::getMessage('MOD_UNINSTALL_BUTTON') ?>" class="adm-btn-delete" disabled id="uninstallBtn">
                <input type="button" name="cancel" value="<?= Loc::getMessage('MOD_CANCEL_BUTTON') ?>" onclick="window.location='<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>'" class="adm-btn">
            </div>
        </form>

        <script>
            document.getElementById('confirmDelete').addEventListener('change', function() {
                document.getElementById('uninstallBtn').disabled = !this.checked;
            });
        </script>

    <?php elseif ($step == 2): ?>

        <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #c3e6cb;">
            <h4 style="margin-top: 0; color: #155724;"><?= Loc::getMessage('XILLIX_UNINSTALL_SUCCESS') ?></h4>
            <p>Модуль Xillix успешно удален.</p>
        </div>

        <div style="margin-top: 30px;">
            <a href="<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>" class="adm-btn">
                <?= Loc::getMessage('XILLIX_INSTALL_BACK') ?>
            </a>
        </div>

    <?php endif; ?>
</div>