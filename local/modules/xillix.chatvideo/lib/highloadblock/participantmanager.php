<?php

namespace Xillix\ChatVideo\HighloadBlock;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class ParticipantManager
{
    private static $hlblockId;
    private static $entity;

    public static function init()
    {
        if (!Loader::includeModule('highloadblock')) {
            return false;
        }

        $hlblock = HL\HighloadBlockTable::getList([
            'filter' => ['=NAME' => 'ChatVideoParticipants']
        ])->fetch();

        if (!$hlblock) {
            return false;
        }

        self::$hlblockId = $hlblock['ID'];
        self::$entity = HL\HighloadBlockTable::compileEntity($hlblock);

        return true;
    }

    /**
     * Присоединение пользователя к комнате
     */
    public static function joinRoom($roomHash, $userId, $sessionId)
    {
        if (!self::init()) {
            \Bitrix\Main\Diag\Debug::writeToFile('ParticipantManager init failed', 'joinRoom_error', 'xillix_chatvideo.log');
            return false;
        }

        // Получаем комнату по hash
        $room = RoomManager::getRoomByHash($roomHash);
        if (!$room) {
            \Bitrix\Main\Diag\Debug::writeToFile('Room not found by hash: ' . $roomHash, 'joinRoom_error', 'xillix_chatvideo.log');
            return false;
        }

        $entityDataClass = self::$entity->getDataClass();

        // Проверяем, не присоединен ли уже пользователь
        $existingParticipant = $entityDataClass::getList([
            'filter' => [
                'UF_ROOM_ID' => $room['ID'],
                'UF_USER_ID' => $userId,
                'UF_IS_ACTIVE' => 'Y'
            ],
            'limit' => 1
        ])->fetch();

        if ($existingParticipant) {
            // Обновляем сессию существующего участника
            $entityDataClass::update($existingParticipant['ID'], [
                'UF_SESSION_ID' => $sessionId,
                'UF_LEFT_AT' => null
            ]);
            \Bitrix\Main\Diag\Debug::writeToFile('Updated existing participant: ' . $existingParticipant['ID'], 'joinRoom_success', 'xillix_chatvideo.log');
            return $existingParticipant['ID'];
        }

        // Создаем нового участника
        $result = $entityDataClass::add([
            'UF_ROOM_ID' => $room['ID'],
            'UF_USER_ID' => $userId,
            'UF_SESSION_ID' => $sessionId,
            'UF_IS_ACTIVE' => 'Y',
            'UF_JOINED_AT' => new \Bitrix\Main\Type\DateTime()
        ]);

        if ($result->isSuccess()) {
            $participantId = $result->getId();
            \Bitrix\Main\Diag\Debug::writeToFile('Created new participant: ' . $participantId, 'joinRoom_success', 'xillix_chatvideo.log');
            return $participantId;
        } else {
            \Bitrix\Main\Diag\Debug::writeToFile('Failed to create participant: ' . implode(', ', $result->getErrorMessages()), 'joinRoom_error', 'xillix_chatvideo.log');
            return false;
        }
    }

    /**
     * Выход пользователя из комнаты
     */
    public static function leaveRoom($roomHash, $userId)
    {
        if (!self::init()) {
            return false;
        }

        // Получаем комнату по hash
        $room = RoomManager::getRoomByHash($roomHash);
        if (!$room) {
            return false;
        }

        $entityDataClass = self::$entity->getDataClass();

        // Находим активного участника
        $participant = $entityDataClass::getList([
            'filter' => [
                'UF_ROOM_ID' => $room['ID'],
                'UF_USER_ID' => $userId,
                'UF_IS_ACTIVE' => 'Y'
            ],
            'limit' => 1
        ])->fetch();

        if ($participant) {
            // Помечаем участника как неактивного
            $result = $entityDataClass::update($participant['ID'], [
                'UF_IS_ACTIVE' => 'N',
                'UF_LEFT_AT' => new \Bitrix\Main\Type\DateTime()
            ]);

            return $result->isSuccess();
        }

        return false;
    }

    /**
     * Получение количества активных участников в комнате
     */
    public static function getActiveParticipantsCount($roomId)
    {
        if (!self::init()) {
            return 0;
        }

        $entityDataClass = self::$entity->getDataClass();

        $count = $entityDataClass::getCount([
            'UF_ROOM_ID' => $roomId,
            'UF_IS_ACTIVE' => 'Y'
        ]);

        return $count;
    }

    /**
     * Получение списка участников комнаты
     */
    public static function getRoomParticipants($roomId, $onlyActive = true)
    {
        if (!self::init()) {
            return [];
        }

        $entityDataClass = self::$entity->getDataClass();

        $filter = ['UF_ROOM_ID' => $roomId];
        if ($onlyActive) {
            $filter['!=UF_IS_ACTIVE'] = false;
        }

        $participants = $entityDataClass::getList([
            'filter' => $filter,
            'order' => ['UF_JOINED_AT' => 'ASC']
        ])->fetchAll();

        // Добавьте логирование для отладки
        \Bitrix\Main\Diag\Debug::writeToFile([
            'roomId' => $roomId,
            'filter' => $filter,
            'participantsCount' => count($participants),
            'participants' => $participants
        ], 'getRoomParticipants', 'xillix_chatvideo.log');

        return $participants;
    }

    /**
     * Получение информации об участнике
     */
    public static function getParticipantInfo($participantId)
    {
        if (!self::init()) {
            return null;
        }

        $entityDataClass = self::$entity->getDataClass();

        return $entityDataClass::getList([
            'filter' => ['ID' => $participantId],
            'limit' => 1
        ])->fetch();
    }

    /**
     * Удаление всех участников комнаты (при удалении комнаты)
     */
    public static function removeAllRoomParticipants($roomId)
    {
        if (!self::init()) {
            return false;
        }

        $entityDataClass = self::$entity->getDataClass();

        $participants = $entityDataClass::getList([
            'filter' => ['UF_ROOM_ID' => $roomId],
            'select' => ['ID']
        ])->fetchAll();

        foreach ($participants as $participant) {
            $entityDataClass::delete($participant['ID']);
        }

        return true;
    }

    /**
     * Автоматическое помечание неактивных участников (по таймауту)
     */
    public static function cleanupInactiveParticipants($timeoutMinutes = 5)
    {
        if (!self::init()) {
            return false;
        }

        $entityDataClass = self::$entity->getDataClass();

        $timeout = new \Bitrix\Main\Type\DateTime();
        $timeout->add('-PT' . $timeoutMinutes . 'M');

        // Находим участников, которые неактивны дольше таймаута
        $inactiveParticipants = $entityDataClass::getList([
            'filter' => [
                'UF_IS_ACTIVE' => 'Y',
                '<=UF_JOINED_AT' => $timeout
            ],
            'select' => ['ID']
        ])->fetchAll();

        foreach ($inactiveParticipants as $participant) {
            $entityDataClass::update($participant['ID'], [
                'UF_IS_ACTIVE' => 'N',
                'UF_LEFT_AT' => new \Bitrix\Main\Type\DateTime()
            ]);
        }

        return count($inactiveParticipants);
    }
}