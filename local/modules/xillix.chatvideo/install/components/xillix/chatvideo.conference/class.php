<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Xillix\ChatVideo\HighloadBlock\RoomManager;
use Xillix\ChatVideo\HighloadBlock\ParticipantManager;

class ChatVideoConferenceComponent extends CBitrixComponent implements Controllerable
{
    public function configureActions()
    {
        return [
            'createRoom' => ['prefilters' => []],
            'joinRoom' => ['prefilters' => []],
            'leaveRoom' => ['prefilters' => []],
            'getRoomInfo' => ['prefilters' => []],
        ];
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['MAX_PARTICIPANTS'] = isset($arParams['MAX_PARTICIPANTS']) ? (int)$arParams['MAX_PARTICIPANTS'] : 10;
        $arParams['SHOW_ROOM_CREATION'] = $arParams['SHOW_ROOM_CREATION'] ?? 'Y';

        return $arParams;
    }

    public function executeComponent()
    {
        global $USER;

        if (!Loader::includeModule('xillix.chatvideo')) {
            ShowError(Loc::getMessage('XILLIX_CHATVIDEO_MODULE_NOT_INSTALLED'));
            return;
        }

        $this->arResult['USER_ID'] = $USER->GetID();
        $this->arResult['IS_AUTHORIZED'] = $USER->IsAuthorized();
        $this->arResult['MAX_PARTICIPANTS'] = $this->arParams['MAX_PARTICIPANTS'];
        $this->arResult['SHOW_ROOM_CREATION'] = $this->arParams['SHOW_ROOM_CREATION'];

        // Получаем параметры из настроек модуля через Module класс
        $this->arResult['VOXIMPLANT_ACCOUNT_ID'] = \Xillix\ChatVideo\Module::getOption('voximplant_account_id');
        $this->arResult['VOXIMPLANT_APP_ID'] = \Xillix\ChatVideo\Module::getOption('voximplant_app_id');

        // Получаем signed parameters для AJAX
        $this->arResult['SIGNED_PARAMETERS'] = $this->getSignedParameters();

        // Проверяем наличие комнаты в URL
        $this->arResult['ROOM_HASH'] = $this->getRoomHashFromRequest();

        $this->includeComponentTemplate();
    }

    private function getRoomHashFromRequest()
    {
        return $_REQUEST['room'] ?? '';
    }

    public function createRoomAction($roomName, $maxParticipants = 10)
    {
        if (!Loader::includeModule('xillix.chatvideo')) {
            ShowError(Loc::getMessage('XILLIX_CHATVIDEO_MODULE_NOT_INSTALLED'));
            return;
        }

        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => Loc::getMessage('XILLIX_CHATVIDEO_NOT_AUTHORIZED')];
        }

        $result = RoomManager::createRoom($roomName, $USER->GetID(), $maxParticipants);

        if ($result) {
            return [
                'success' => true,
                'room' => $result
            ];
        }

        return ['success' => false, 'error' => Loc::getMessage('XILLIX_CHATVIDEO_ROOM_CREATION_FAILED')];
    }

    public function joinRoomAction($roomHash)
    {
        if (!Loader::includeModule('xillix.chatvideo')) {
            ShowError(Loc::getMessage('XILLIX_CHATVIDEO_MODULE_NOT_INSTALLED'));
            return;
        }

        global $USER;

        \Bitrix\Main\Diag\Debug::writeToFile([
            'action' => 'joinRoom',
            'roomHash' => $roomHash,
            'userId' => $USER->GetID(),
            'userAuthorized' => $USER->IsAuthorized()
        ], 'joinRoom_start', 'xillix_chatvideo.log');

        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => Loc::getMessage('XILLIX_CHATVIDEO_NOT_AUTHORIZED')];
        }

        $room = RoomManager::getRoomByHash($roomHash);
        if (!$room) {
            \Bitrix\Main\Diag\Debug::writeToFile('Room not found', 'joinRoom_error', 'xillix_chatvideo.log');
            return ['success' => false, 'error' => Loc::getMessage('XILLIX_CHATVIDEO_ROOM_NOT_FOUND')];
        }

        // Проверяем количество участников
        $participantsCount = ParticipantManager::getActiveParticipantsCount($room['ID']);
        if ($participantsCount >= $room['UF_MAX_PARTICIPANTS']) {
            return ['success' => false, 'error' => Loc::getMessage('XILLIX_CHATVIDEO_ROOM_FULL')];
        }

        $sessionId = session_id();

        // ✅ ИСПРАВЛЕНИЕ: Передаем roomHash вместо roomId
        $result = ParticipantManager::joinRoom($roomHash, $USER->GetID(), $sessionId);

        \Bitrix\Main\Diag\Debug::writeToFile([
            'participantResult' => $result,
            'roomId' => $room['ID'],
            'participantsCount' => $participantsCount
        ], 'joinRoom_result', 'xillix_chatvideo.log');

        if ($result) {
            return [
                'success' => true,
                'room' => $room,
                'participant' => ['UF_USER_ID' => $USER->GetID()] // ✅ Возвращаем ID участника
            ];
        }

        return ['success' => false, 'error' => Loc::getMessage('XILLIX_CHATVIDEO_JOIN_ROOM_FAILED')];
    }

    public function leaveRoomAction($roomHash)
    {
        if (!Loader::includeModule('xillix.chatvideo')) {
            ShowError(Loc::getMessage('XILLIX_CHATVIDEO_MODULE_NOT_INSTALLED'));
            return;
        }

        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => Loc::getMessage('XILLIX_CHATVIDEO_NOT_AUTHORIZED')];
        }

        $result = ParticipantManager::leaveRoom($roomHash, $USER->GetID());

        return ['success' => $result];
    }

    public function getRoomInfoAction($roomHash)
    {
        if (!Loader::includeModule('xillix.chatvideo')) {
            ShowError(Loc::getMessage('XILLIX_CHATVIDEO_MODULE_NOT_INSTALLED'));
            return;
        }

        $room = RoomManager::getRoomByHash($roomHash);
        if (!$room) {
            return ['success' => false, 'error' => Loc::getMessage('XILLIX_CHATVIDEO_ROOM_NOT_FOUND')];
        }

        $participants = ParticipantManager::getRoomParticipants($room['ID']);

        return [
            'success' => true,
            'room' => $room,
            'participants' => $participants
        ];
    }
}