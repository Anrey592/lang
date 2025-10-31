<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Xillix\Telegram\Bot;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('xillix.telegram')) {
    CAdminMessage::ShowMessage([
        'MESSAGE' => 'Модуль xillix.telegram не загружен',
        'TYPE' => 'ERROR'
    ]);
}

if ($REQUEST_METHOD == "POST" && strlen($Update . $Apply . $RestoreDefaults) > 0 && check_bitrix_sessid()) {

    if (strlen($Update) > 0 || strlen($Apply) > 0) {
        Option::set("xillix.telegram", "TELEGRAM_BOT_TOKEN", $_POST["TELEGRAM_BOT_TOKEN"]);

        if (strlen($Apply) > 0) {
            LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($mid) . "&lang=" . urlencode(LANGUAGE_ID) . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"]));
        } else {
            LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($mid) . "&lang=" . urlencode(LANGUAGE_ID));
        }
    }

    if (strlen($RestoreDefaults) > 0) {
        Option::delete("xillix.telegram", array("name" => "TELEGRAM_BOT_TOKEN"));
    }
}

// Обработка действий с вебхуком
if ($_REQUEST['action'] && check_bitrix_sessid()) {
    if (Loader::includeModule('xillix.telegram')) {
        $bot = new Bot();

        switch ($_REQUEST['action']) {
            case 'set_webhook':
                $webhookUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/bitrix/tools/xillix.telegram/bot.php';
                $result = $bot->setWebhook($webhookUrl);
                if ($result['ok']) {
                    CAdminMessage::ShowMessage([
                        'MESSAGE' => 'Вебхук успешно установлен: ' . $webhookUrl,
                        'TYPE' => 'OK'
                    ]);
                } else {
                    CAdminMessage::ShowMessage([
                        'MESSAGE' => 'Ошибка установки вебхука: ' . $result['description'],
                        'TYPE' => 'ERROR'
                    ]);
                }
                break;

            case 'delete_webhook':
                $result = $bot->deleteWebhook();
                if ($result['ok']) {
                    CAdminMessage::ShowMessage([
                        'MESSAGE' => 'Вебхук успешно удален',
                        'TYPE' => 'OK'
                    ]);
                } else {
                    CAdminMessage::ShowMessage([
                        'MESSAGE' => 'Ошибка удаления вебхука: ' . $result['description'],
                        'TYPE' => 'ERROR'
                    ]);
                }
                break;

            case 'set_commands':
                $result = $bot->setMyCommands();
                if ($result['ok']) {
                    CAdminMessage::ShowMessage([
                        'MESSAGE' => 'Команды меню успешно установлены',
                        'TYPE' => 'OK'
                    ]);
                } else {
                    CAdminMessage::ShowMessage([
                        'MESSAGE' => 'Ошибка установки команд: ' . $result['description'],
                        'TYPE' => 'ERROR'
                    ]);
                }
                break;

            case 'delete_commands':
                $result = $bot->deleteMyCommands();
                if ($result['ok']) {
                    CAdminMessage::ShowMessage([
                        'MESSAGE' => 'Команды меню успешно удалены',
                        'TYPE' => 'OK'
                    ]);
                } else {
                    CAdminMessage::ShowMessage([
                        'MESSAGE' => 'Ошибка удаления команд: ' . $result['description'],
                        'TYPE' => 'ERROR'
                    ]);
                }
                break;
        }
    }
}

$TELEGRAM_BOT_TOKEN = Option::get("xillix.telegram", "TELEGRAM_BOT_TOKEN");

$aTabs = array(
    array(
        "DIV" => "edit1",
        "TAB" => GetMessage("MAIN_TAB_SET"),
        "TITLE" => GetMessage("MAIN_TAB_TITLE_SET"),
    ),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>

<?php $tabControl->Begin(); ?>

<form method="post"
      action="<?php echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%">
            <label for="TELEGRAM_BOT_TOKEN"><?= GetMessage("TELEGRAM_BOT_TOKEN") ?>:</label>
        </td>
        <td width="60%">
            <input type="text"
                   name="TELEGRAM_BOT_TOKEN"
                   id="TELEGRAM_BOT_TOKEN"
                   value="<?= htmlspecialcharsbx($TELEGRAM_BOT_TOKEN) ?>"
                   size="50"
                   placeholder="1234567890:AAFbz8szQTNuN5h9nPTTTWC1FruOxGrEYw4">
            <br><small>Токен бота, полученный от @BotFather</small>
        </td>
    </tr>

    <?php if ($TELEGRAM_BOT_TOKEN): ?>
        <tr>
            <td width="40%">
                Управление вебхуком:
            </td>
            <td width="60%">
                <?php
                try {
                    $bot = new Bot();
                    $webhookInfo = $bot->getWebhookInfo();
                    $webhookUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/bitrix/tools/xillix.telegram/bot.php';

                    if ($webhookInfo['ok']) {
                        if ($webhookInfo['result']['url']) {
                            echo '<div style="margin-bottom: 10px;">';
                            echo '<span style="color: green;">✓ Вебхук установлен</span><br>';
                            echo '<small>URL: ' . htmlspecialcharsbx($webhookInfo['result']['url']) . '</small>';
                            echo '</div>';
                        } else {
                            echo '<div style="margin-bottom: 10px;">';
                            echo '<span style="color: orange;">⚠ Вебхук не установлен</span>';
                            echo '</div>';
                        }
                    }

                    // Кнопки управления вебхуком
                    echo '<div style="margin-top: 10px; margin-bottom: 20px;">';
                    echo '<a href="?mid=' . htmlspecialcharsbx($mid) . '&lang=' . LANGUAGE_ID . '&action=set_webhook&' . bitrix_sessid_get() . '" class="adm-btn">Установить вебхук</a> ';
                    echo '<a href="?mid=' . htmlspecialcharsbx($mid) . '&lang=' . LANGUAGE_ID . '&action=delete_webhook&' . bitrix_sessid_get() . '" class="adm-btn" onclick="return confirm(\'Удалить вебхук?\')">Удалить вебхук</a>';
                    echo '</div>';

                } catch (Exception $e) {
                    echo '<span style="color: red;">✗ Ошибка: ' . htmlspecialcharsbx($e->getMessage()) . '</span>';
                }
                ?>
            </td>
        </tr>

        <tr>
            <td width="40%">
                Управление командами меню:
            </td>
            <td width="60%">
                <?php
                try {
                    $bot = new Bot();

                    echo '<div style="margin-top: 10px;">';
                    echo '<a href="?mid=' . htmlspecialcharsbx($mid) . '&lang=' . LANGUAGE_ID . '&action=set_commands&' . bitrix_sessid_get() . '" class="adm-btn">Установить команды меню</a> ';
                    echo '<a href="?mid=' . htmlspecialcharsbx($mid) . '&lang=' . LANGUAGE_ID . '&action=delete_commands&' . bitrix_sessid_get() . '" class="adm-btn" onclick="return confirm(\'Удалить команды меню?\')">Удалить команды</a>';
                    echo '</div>';

                    echo '<div style="margin-top: 10px; font-size: 12px; color: #666;">';
                    echo 'Доступные команды:<br>';
                    echo '/start - Запустить бота<br>';
                    echo '/register - Регистрация<br>';
                    echo '/resetpassword - Сбросить пароль<br>';
                    echo '/schedule - Мое расписание';
                    echo '</div>';

                } catch (Exception $e) {
                    echo '<span style="color: red;">✗ Ошибка: ' . htmlspecialcharsbx($e->getMessage()) . '</span>';
                }
                ?>
            </td>
        </tr>

        <tr>
            <td width="40%">
                URL вебхука:
            </td>
            <td width="60%">
                <code><?= htmlspecialcharsbx($webhookUrl) ?></code>
                <br><small>Скопируйте этот URL для ручной настройки вебхука</small>
            </td>
        </tr>
    <?php endif; ?>

    <?php $tabControl->Buttons(); ?>

    <input type="submit" name="Update" value="<?= GetMessage("MAIN_SAVE") ?>" class="adm-btn-save">
    <input type="submit" name="Apply" value="<?= GetMessage("MAIN_APPLY") ?>">
    <input type="reset" name="Reset" value="<?= GetMessage("MAIN_RESET") ?>">
    <input type="submit" name="RestoreDefaults" title="<?= GetMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
           OnClick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
           value="<?= GetMessage("MAIN_RESTORE_DEFAULTS") ?>">

    <?php $tabControl->End(); ?>
</form>