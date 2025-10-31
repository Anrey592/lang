<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arEvents = [
    [
        'FROM_MODULE_ID' => 'xillix',
        'MESSAGE_ID' => 'XILLIX_SCHEDULE_ADD',
        'LID' => 'ru',
        'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
        'EMAIL_TO' => '#EMAIL#',
        'SUBJECT' => 'Добавлено новое занятие',
        'MESSAGE' => 'Уважаемый преподаватель,#BR#
Было добавлено новое занятие:#BR#
Дата: #DATE##BR#
Время: #TIME##BR#
Предмет: #SUBJECT##BR##BR#
С уважением, администрация сайта',
        'BODY_TYPE' => 'text'
    ],
    [
        'FROM_MODULE_ID' => 'xillix',
        'MESSAGE_ID' => 'XILLIX_SCHEDULE_UPDATE',
        'LID' => 'ru',
        'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
        'EMAIL_TO' => '#EMAIL#',
        'SUBJECT' => 'Изменено занятие',
        'MESSAGE' => 'Уважаемый преподаватель,#BR#
Было изменено занятие:#BR#
Новая дата: #DATE##BR#
Новое время: #TIME##BR#
Предмет: #SUBJECT##BR#
Старая дата: #OLD_DATE##BR#
Старое время: #OLD_TIME##BR##BR#
С уважением, администрация сайта',
        'BODY_TYPE' => 'text'
    ],
];

return $arEvents;