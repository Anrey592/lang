<?php

namespace Xillix\ChatVideo\HighloadBlock;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class RoomManager
{
    private static $hlblockId;
    private static $entity;

    public static function init()
    {
        if (!Loader::includeModule('highloadblock')) {
            return false;
        }

        $hlblock = HL\HighloadBlockTable::getList([
            'filter' => ['=NAME' => 'ChatVideoRooms']
        ])->fetch();

        if (!$hlblock) {
            return false;
        }

        self::$hlblockId = $hlblock['ID'];
        self::$entity = HL\HighloadBlockTable::compileEntity($hlblock);

        return true;
    }

    public static function createRoom($name, $createdBy, $maxParticipants = 10)
    {
        if (!self::init()) {
            return false;
        }

        $roomId = uniqid('room_');
        $hash = self::generateHash();

        $entityDataClass = self::$entity->getDataClass();

        $result = $entityDataClass::add([
            'UF_NAME' => $name,
            'UF_ROOM_ID' => $roomId,
            'UF_HASH' => $hash,
            'UF_CREATED_BY' => $createdBy,
            'UF_MAX_PARTICIPANTS' => $maxParticipants,
            'UF_ACTIVE' => true,
            'UF_CREATED_AT' => new \Bitrix\Main\Type\DateTime()
        ]);

        if ($result->isSuccess()) {
            return [
                'id' => $result->getId(),
                'room_id' => $roomId,
                'hash' => $hash
            ];
        }

        return false;
    }

    public static function getRoomByHash($hash)
    {
        if (!self::init()) {
            return false;
        }

        $entityDataClass = self::$entity->getDataClass();

        return $entityDataClass::getList([
            'filter' => ['=UF_HASH' => $hash, '=UF_ACTIVE' => true],
            'limit' => 1
        ])->fetch();
    }

    public static function getRoomById($roomId)
    {
        if (!self::init()) {
            return false;
        }

        $entityDataClass = self::$entity->getDataClass();

        return $entityDataClass::getList([
            'filter' => ['=ID' => $roomId, '=UF_ACTIVE' => true],
            'limit' => 1
        ])->fetch();
    }

    public static function getUserRooms($userId)
    {
        if (!self::init()) {
            return [];
        }

        $entityDataClass = self::$entity->getDataClass();

        return $entityDataClass::getList([
            'filter' => ['=UF_CREATED_BY' => $userId, '=UF_ACTIVE' => true],
            'order' => ['UF_CREATED_AT' => 'DESC']
        ])->fetchAll();
    }

    public static function deactivateRoom($roomHash)
    {
        if (!self::init()) {
            return false;
        }

        $room = self::getRoomByHash($roomHash);
        if (!$room) {
            return false;
        }

        $entityDataClass = self::$entity->getDataClass();

        $result = $entityDataClass::update($room['ID'], [
            'UF_ACTIVE' => false
        ]);

        return $result->isSuccess();
    }

    public static function updateRoom($roomHash, $fields)
    {
        if (!self::init()) {
            return false;
        }

        $room = self::getRoomByHash($roomHash);
        if (!$room) {
            return false;
        }

        $entityDataClass = self::$entity->getDataClass();

        $allowedFields = ['UF_NAME', 'UF_MAX_PARTICIPANTS'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($fields[$field])) {
                $updateData[$field] = $fields[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        $result = $entityDataClass::update($room['ID'], $updateData);
        return $result->isSuccess();
    }

    private static function generateHash($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
}