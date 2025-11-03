<?php

namespace Xillix\ChatVideo\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

class ParticipantTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'b_hlbd_xillix_chatvideo_participants';
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            new Entity\IntegerField('UF_ROOM_ID', [
                'required' => true
            ]),
            new Entity\IntegerField('UF_USER_ID', [
                'required' => true
            ]),
            new Entity\StringField('UF_SESSION_ID', [
                'required' => true
            ]),
            new Entity\DatetimeField('UF_JOINED_AT', [
                'required' => true,
                'default_value' => function () {
                    return new \Bitrix\Main\Type\DateTime();
                }
            ]),
            new Entity\DatetimeField('UF_LEFT_AT'),
            new Entity\BooleanField('UF_IS_ACTIVE', [
                'values' => ['N', 'Y'],
                'default_value' => 'Y'
            ])
        ];
    }
}