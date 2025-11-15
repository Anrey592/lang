<?php

use Bitrix\Main\Loader;

if (!Loader::includeModule('main')) {
    return;
}

Loader::registerAutoLoadClasses('xillix.videoconf', [
    'Xillix\\Videoconf\\TrueConfManager' => 'lib/TrueConfManager.php',
    'Xillix\\Videoconf\\Agent' => 'lib/Agent.php',
    'Xillix\\Videoconf\\CleanupAgent' => 'lib/CleanupAgent.php',
]);