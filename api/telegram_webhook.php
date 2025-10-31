<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('xillix.telegram')) {
    die('Module not installed');
}

session_start();

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if ($update) {
    $telegramBot = new Xillix\Telegram\Bot();

    if (isset($update['message'])) {
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        // Проверяем состояние пользователя
        $state = $_SESSION['telegram_states'][$chatId] ?? null;

        if ($state === 'awaiting_phone') {
            $telegramBot->handlePhoneInput($chatId, $text);
        } else {
            $telegramBot->processUpdate($update);
        }
    } else {
        $telegramBot->processUpdate($update);
    }
}

echo 'OK';
