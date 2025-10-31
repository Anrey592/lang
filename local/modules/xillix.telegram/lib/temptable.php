<?php

namespace Xillix\Telegram;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

class TempTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'b_xillix_telegram_temp';
    }

    public static function getMap()
    {
        return [
            'CHAT_ID' => [
                'data_type' => 'integer',
                'primary' => true,
            ],
            'DATA_KEY' => [
                'data_type' => 'string',
                'primary' => true,
                'validation' => function () {
                    return [
                        new Entity\Validator\Length(null, 50),
                    ];
                },
            ],
            'DATA_VALUE' => [
                'data_type' => 'text',
            ],
            'TIMESTAMP_X' => [
                'data_type' => 'datetime',
                'default_value' => new DateTime(),
            ],
        ];
    }

    public static function deleteByChatId($chatId)
    {
        // Получаем все записи для данного chat_id
        $items = static::getList([
            'filter' => ['CHAT_ID' => $chatId],
            'select' => ['CHAT_ID', 'DATA_KEY']
        ]);

        while ($item = $items->fetch()) {
            static::delete([
                'CHAT_ID' => $item['CHAT_ID'],
                'DATA_KEY' => $item['DATA_KEY']
            ]);
        }
    }
}