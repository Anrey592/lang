<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule("xillix.telegram")) {
    die("Module not installed");
}

$input = file_get_contents("php://input");
$update = json_decode($input, true);

if ($update) {
    $bot = new Xillix\Telegram\Bot();
    $bot->processUpdate($update);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
?>