<?php
// Подключаем ядро Bitrix (для авторизации и доступа к настройкам)
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

// Только авторизованные пользователи (опционально)
if (!$USER->IsAuthorized()) {
    http_response_code(403);
    die('Access denied');
}

// Получаем ID записи
$recordingId = (int)$_GET['id'];
if (!$recordingId) {
    http_response_code(400);
    die('Invalid recording ID');
}

// Получаем данные модуля xillix.videoconf
if (!Loader::includeModule('xillix.videoconf')) {
    http_response_code(500);
    die('Module xillix.videoconf not installed');
}

$trueconfDomain = Option::get('xillix.videoconf', 'server_domain');
if (!$trueconfDomain) {
    http_response_code(500);
    die('TrueConf domain not configured');
}

// Формируем URL
$downloadUrl = 'https://' . $trueconfDomain . '/api/v3.11/logs/recordings/' . $recordingId . '/download';

// Получаем access_token через TrueConfManager
try {
    $tc = new \Xillix\Videoconf\TrueConfManager();
    $accessToken = $tc->getAccessToken();
} catch (\Exception $e) {
    http_response_code(500);
    die('Failed to get access token');
}

// Выполняем запрос к TrueConf
$ch = curl_init($downloadUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Accept: */*'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Не буферизовать
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);

// Устанавливаем правильные заголовки для видео
header('Content-Type: video/webm');
header('Accept-Ranges: bytes');
header('Cache-Control: private');

curl_exec($ch);
curl_close($ch);
exit;