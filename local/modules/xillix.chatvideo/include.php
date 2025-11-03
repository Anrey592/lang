<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// Подключаем файл с функциями модуля
require_once __DIR__ . '/lib/functions.php';

// Регистрируем автозагрузку классов модуля
Loader::registerAutoLoadClasses(
    'xillix.chatvideo',
    [
        'Xillix\\ChatVideo\\HighloadBlock\\RoomManager' => 'lib/highloadblock/roommanager.php',
        'Xillix\\ChatVideo\\HighloadBlock\\ParticipantManager' => 'lib/highloadblock/participantmanager.php',
        'Xillix\\ChatVideo\\Orm\\RoomTable' => 'lib/orm/roomtable.php',
        'Xillix\\ChatVideo\\Orm\\ParticipantTable' => 'lib/orm/participanttable.php',
    ]
);

// Подключаем языковые файлы для админки
if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
    Loc::loadMessages(__FILE__);
}