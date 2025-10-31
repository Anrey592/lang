<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::registerAutoLoadClasses('xillix', [
    'Xillix\\TeacherScheduleManager' => 'lib/TeacherScheduleManager.php',
    'Xillix\\TimezoneHelper' => 'lib/TimezoneHelper.php',
    'Xillix\\NotificationManager' => 'lib/NotificationManager.php',
    'Xillix\\ReminderAgent' => 'lib/ReminderAgent.php',
]);
