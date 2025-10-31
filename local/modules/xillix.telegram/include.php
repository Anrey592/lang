<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('xillix.telegram', array(
    'Xillix\\Telegram\\Bot' => 'lib/bot.php',
    'Xillix\\Telegram\\UserManager' => 'lib/usermanager.php',
    'Xillix\\Telegram\\StateTable' => 'lib/statetable.php',
    'Xillix\\Telegram\\TempTable' => 'lib/temptable.php',
));
