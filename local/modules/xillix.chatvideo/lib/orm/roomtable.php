<?php

namespace Xillix\ChatVideo\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

class RoomTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'b_hlbd_xillix_chatvideo_rooms';
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            new Entity\StringField('UF_NAME', [
                'required' => true,
                'title' => Loc::getMessage('XILLIX_CHATVIDEO_ROOM_NAME')
            ]),
            new Entity\StringField('UF_ROOM_ID', [
                'required' => true,
                'title' => Loc::getMessage('XILLIX_CHATVIDEO_ROOM_ID')
            ]),
            new Entity\StringField('UF_HASH', [
                'required' => true,
                'title' => Loc::getMessage('XILLIX_CHATVIDEO_ROOM_HASH')
            ]),
            new Entity\IntegerField('UF_CREATED_BY', [
                'required' => true
            ]),
            new Entity\DatetimeField('UF_CREATED_AT', [
                'required' => true,
                'default_value' => function () {
                    return new \Bitrix\Main\Type\DateTime();
                }
            ]),
            new Entity\BooleanField('UF_ACTIVE', [
                'values' => ['N', 'Y'],
                'default_value' => 'Y'
            ]),
            new Entity\IntegerField('UF_MAX_PARTICIPANTS', [
                'default_value' => 10
            ])
        ];
    }
}