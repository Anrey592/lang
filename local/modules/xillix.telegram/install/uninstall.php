<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<div class="adm-info-message-wrap">
    <div class="adm-info-message">
        <p><strong><?= Loc::getMessage('XILLIX_TELEGRAM_UNINSTALL_TITLE') ?></strong></p>
        <p><?= Loc::getMessage('XILLIX_TELEGRAM_UNINSTALL_WARNING') ?></p>

        <h3><?= Loc::getMessage('XILLIX_TELEGRAM_UNINSTALL_QUESTIONS') ?>:</h3>

        <div style="margin: 15px 0;">
            <label>
                <input type="checkbox" name="savedata" id="savedata" value="Y" checked>
                <?= Loc::getMessage('XILLIX_TELEGRAM_UNINSTALL_SAVE_DATA') ?>
            </label>
            <br><small>Сохранит пользовательские данные и таблицы модуля</small>
        </div>

        <div style="margin: 15px 0;">
            <label>
                <input type="checkbox" name="savesettings" id="savesettings" value="Y">
                <?= Loc::getMessage('XILLIX_TELEGRAM_UNINSTALL_SAVE_SETTINGS') ?>
            </label>
            <br><small>Сохранит настройки модуля (токен бота)</small>
        </div>
    </div>
</div>

<script>
    function confirmUninstall() {
        if (!confirm('<?= Loc::getMessage('XILLIX_TELEGRAM_UNINSTALL_CONFIRM') ?>')) {
            return false;
        }
        return true;
    }
</script>

<form action="<?= $APPLICATION->GetCurPage() ?>" onsubmit="return confirmUninstall()">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="hidden" name="id" value="xillix.telegram">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="savedata" id="savedata_hidden" value="Y">
    <input type="hidden" name="savesettings" id="savesettings_hidden" value="N">

    <input type="submit" name="" value="<?= Loc::getMessage('XILLIX_TELEGRAM_UNINSTALL_CONFIRM_BUTTON') ?>">
    <input type="button" onclick="window.location.href='<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>'"
           value="<?= Loc::getMessage('XILLIX_TELEGRAM_UNINSTALL_CANCEL') ?>">
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const saveDataCheckbox = document.getElementById('savedata');
        const saveSettingsCheckbox = document.getElementById('savesettings');
        const saveDataHidden = document.getElementById('savedata_hidden');
        const saveSettingsHidden = document.getElementById('savesettings_hidden');

        saveDataCheckbox.addEventListener('change', function () {
            saveDataHidden.value = this.checked ? 'Y' : 'N';
        });

        saveSettingsCheckbox.addEventListener('change', function () {
            saveSettingsHidden.value = this.checked ? 'Y' : 'N';
        });
    });
</script>