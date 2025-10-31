<?php

namespace Xillix\Telegram;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

class StateTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'b_xillix_telegram_states';
    }

    public static function getMap()
    {
        return [
            'CHAT_ID' => [
                'data_type' => 'integer',
                'primary' => true,
            ],
            'STATE' => [
                'data_type' => 'string',
                'validation' => function() {
                    return [
                        new Entity\Validator\Length(null, 50),
                    ];
                },
            ],
            'TIMESTAMP_X' => [
                'data_type' => 'datetime',
                'default_value' => new DateTime(),
            ],
        ];
    }
}