<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\UserTable;
use Xillix\TeacherScheduleManager;

class XillixScheduleComponent extends CBitrixComponent implements Controllerable
{
    /**
     * Конфигурация AJAX действий
     */
    public function configureActions()
    {
        return [
            'getSchedule' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'saveSlot' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new Bitrix\Main\Engine\ActionFilter\Authentication(),
                ],
            ],
            'deleteSlot' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new Bitrix\Main\Engine\ActionFilter\Authentication(),
                ],
            ],
            'saveTimezone' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'getTeacherInfo' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'getUserInfo' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'saveSlotNew' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new Bitrix\Main\Engine\ActionFilter\Authentication(),
                ],
            ],
        ];
    }

    /**
     * Получить расписание пользователя (универсальный метод)
     */
    public function getScheduleAction($weekStart = null, $timezone = 'Europe/Moscow')
    {
        global $USER;

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        $userId = $USER->GetID();
        if (!$userId) {
            return ['success' => false, 'error' => 'User not authorized'];
        }

        // Получаем часовой пояс пользователя
        $userTimezone = $this->getUserTimezone();

        // Определяем режим: преподаватель или ученик
        $mode = $this->getUserMode($userId);

        if ($mode === 'teacher') {
            // Режим преподавателя - показываем его расписание
            $schedule = $this->getTeacherSchedule($userId, $weekStart, $userTimezone);
        } else {
            // Режим ученика - показываем его занятия
            $schedule = $this->getStudentSchedule($userId, $weekStart, $userTimezone);
        }

        return [
            'success' => true,
            'schedule' => $schedule,
            'mode' => $mode,
            'timezone_info' => [
                'user_timezone' => $userTimezone,
                'requested_timezone' => $timezone
            ]
        ];
    }

    /**
     * Получить расписание преподавателя с конвертацией времени
     */
    private function getTeacherSchedule($teacherId, $weekStart, $userTimezone)
    {
        return TeacherScheduleManager::getTeacherSchedule($teacherId, $weekStart, $userTimezone);
    }

    /**
     * Получить расписание ученика с конвертацией времени
     */
    private function getStudentSchedule($studentId, $weekStart, $userTimezone)
    {
        return TeacherScheduleManager::getStudentSchedule($studentId, $weekStart, $userTimezone);
    }

    /**
     * Конвертировать дату в часовой пояс пользователя
     */
    private function convertDateToUserTimezone($dateString, $fromTimezone, $toTimezone)
    {
        if ($fromTimezone === $toTimezone) {
            return $dateString;
        }

        try {
            // Формат даты в БД: d.m.Y
            $date = \DateTime::createFromFormat('d.m.Y', $dateString, new \DateTimeZone($fromTimezone));
            if ($date) {
                $date->setTimezone(new \DateTimeZone($toTimezone));
                return $date->format('d.m.Y');
            }
        } catch (\Exception $e) {
            error_log('Date conversion error: ' . $e->getMessage());
        }

        return $dateString;
    }

    /**
     * Определить режим пользователя (teacher/student)
     */
    private function getUserMode($userId)
    {
        global $USER;

        // Проверяем группы пользователя
        $teacherGroupId = $this->getGroupIdByStringId('repetitory');
        $studentGroupId = $this->getGroupIdByStringId('students');

        $isTeacher = $teacherGroupId && $this->isUserInGroup($teacherGroupId, $userId);
        $isStudent = $studentGroupId && $this->isUserInGroup($studentGroupId, $userId);

        // Приоритет у преподавателя
        if ($isTeacher) {
            return 'teacher';
        } elseif ($isStudent) {
            return 'student';
        } elseif ($USER->IsAdmin()) {
            return 'teacher'; // Админы по умолчанию преподаватели
        } else {
            // Если пользователь не в группах, проверяем есть ли у него занятия как у ученика
            $hasLessons = $this->userHasLessons($userId);
            return $hasLessons ? 'student' : 'teacher';
        }
    }

    /**
     * Проверить есть ли у пользователя занятия
     */
    private function userHasLessons($userId)
    {
        if (!Loader::includeModule('xillix')) {
            return false;
        }

        $entity = TeacherScheduleManager::getEntity();
        if (!$entity) {
            return false;
        }

        try {
            $schedule = $entity::getList([
                'filter' => [
                    '=UF_STUDENT_ID' => (int)$userId
                ],
                'limit' => 1
            ])->fetch();

            return (bool)$schedule;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Сохранить слот расписания (только для преподавателей)
     */
    public function saveSlotAction($slotData)
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized'];
        }

        $userId = $USER->GetID();
        $mode = $this->getUserMode($userId);

        if ($mode !== 'teacher') {
            return ['success' => false, 'error' => 'Только преподаватели могут редактировать расписание'];
        }

        // Валидация
        if (empty($slotData['slot_date']) || empty($slotData['start_time']) || empty($slotData['end_time'])) {
            return ['success' => false, 'error' => 'Fill all required fields'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        $slotId = $slotData['slot_id'] ?? null;
        $date = $slotData['slot_date'];
        $startTime = $slotData['start_time'];
        $endTime = $slotData['end_time'];
        $subject = $slotData['subject'] ?? 'Английский язык';
        $timezone = $slotData['timezone'] ?? $this->getUserTimezone();

        // Проверяем что дата не в прошлом (для новых записей)
        if (empty($slotId)) {
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $slotDate = DateTime::createFromFormat('Y-m-d', $date);

            if ($slotDate && $slotDate < $today) {
                return ['success' => false, 'error' => 'Cannot add slots in the past'];
            }
        }

        if ($slotId) {
            $result = TeacherScheduleManager::updateScheduleSlot($slotId, [
                'UF_DATE' => $date,
                'UF_START_TIME' => $startTime,
                'UF_END_TIME' => $endTime,
                'UF_SUBJECT' => $subject
            ], $timezone);
        } else {
            $result = TeacherScheduleManager::addScheduleSlot(
                $userId, // teacherId = текущий пользователь
                $date,
                $startTime,
                $endTime,
                $subject,
                $timezone
            );
        }

        if ($result && $result->isSuccess()) {
            return ['success' => true, 'message' => 'Data saved successfully'];
        } else {
            $errors = $result ? $result->getErrorMessages() : ['Unknown error'];
            return ['success' => false, 'errors' => $errors];
        }
    }

    /**
     * Удалить слот расписания (только для преподавателей)
     */
    public function deleteSlotAction($slotId)
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized'];
        }

        $userId = $USER->GetID();
        $mode = $this->getUserMode($userId);

        if ($mode !== 'teacher') {
            return ['success' => false, 'error' => 'Только преподаватели могут удалять занятия'];
        }

        if (empty($slotId)) {
            return ['success' => false, 'error' => 'Slot ID is required'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        $result = TeacherScheduleManager::deleteScheduleSlot($slotId);

        if ($result->isSuccess()) {
            return ['success' => true, 'message' => 'Slot deleted successfully'];
        } else {
            return ['success' => false, 'error' => 'Delete failed'];
        }
    }

    /**
     * Сохранить часовой пояс
     */
    public function saveTimezoneAction($timezone)
    {
        global $USER;

        if (empty($timezone)) {
            return ['success' => false, 'error' => 'Timezone is required'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        // Сохраняем в cookie для всех пользователей
        setcookie('user_timezone', $timezone, time() + 60 * 60 * 24 * 30, '/');

        // Если пользователь авторизован, сохраняем в его профиль
        if ($USER->IsAuthorized()) {
            $result = TeacherScheduleManager::setTeacherTimezone($USER->GetID(), $timezone);
        } else {
            $result = true;
        }

        return ['success' => (bool)$result];
    }

    /**
     * Получить часовой пояс пользователя
     */
    private function getUserTimezone()
    {
        global $USER;

        // Если пользователь авторизован, пытаемся получить его часовой пояс
        if ($USER->IsAuthorized()) {
            $user = UserTable::getList([
                'filter' => ['ID' => $USER->GetID()],
                'select' => ['ID', 'UF_TIMEZONE']
            ])->fetch();

            if ($user && !empty($user['UF_TIMEZONE'])) {
                return $user['UF_TIMEZONE'];
            }
        }

        // Пытаемся получить из cookie
        if (isset($_COOKIE['user_timezone'])) {
            $timezone = $_COOKIE['user_timezone'];
            if (in_array($timezone, \DateTimeZone::listIdentifiers())) {
                return $timezone;
            }
        }

        // По умолчанию
        return 'Europe/Moscow';
    }

    /**
     * Проверить принадлежность пользователя к группе
     */
    private function isUserInGroup($groupId, $userId = null)
    {
        global $USER;
        $userId = $userId ?: $USER->GetID();

        $groups = CUser::GetUserGroup($userId);
        return in_array($groupId, $groups);
    }

    /**
     * Получить ID группы по строковому идентификатору
     */
    private function getGroupIdByStringId($stringId)
    {
        $group = CGroup::GetList('id', 'asc', ['STRING_ID' => $stringId])->Fetch();
        return $group ? $group['ID'] : null;
    }

    /**
     * Найти элемент преподавателя в инфоблоке по PROFILE_ID
     */
    private function findTeacherElementByProfileId($profileId)
    {
        if (!Loader::includeModule('iblock')) {
            return null;
        }

        // Получаем ID инфоблока по символьному коду
        $iblock = \CIBlock::GetList([], ['CODE' => 'repetitory', 'ACTIVE' => 'Y'])->Fetch();
        if (!$iblock) {
            error_log('Infoblock "repetitory" not found');
            return null;
        }

        $iblockId = $iblock['ID'];

        // Ищем элемент по свойству PROFILE_ID
        $elements = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'PROPERTY_PROFILE_ID' => $profileId
            ],
            false,
            false,
            ['ID', 'NAME', 'DETAIL_PAGE_URL']
        );

        if ($element = $elements->Fetch()) {
            // Получаем полный URL
            $element['DETAIL_PAGE_URL'] = \CIBlock::ReplaceDetailUrl($element['DETAIL_PAGE_URL'], $element, false, 'E');
            return $element;
        }

        return null;
    }

    /**
     * Получить информацию о преподавателе
     */
    public function getTeacherInfoAction($teacherId)
    {
        if (!$teacherId) {
            return ['success' => false, 'error' => 'Invalid teacher ID'];
        }

        // Сначала получаем базовую информацию о пользователе
        $user = \CUser::GetByID($teacherId)->Fetch();
        if (!$user) {
            return ['success' => false, 'error' => 'Teacher not found'];
        }

        $userName = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
        if (empty($userName)) {
            $userName = $user['LOGIN'];
        }

        // Ищем элемент в инфоблоке "repetitory" по свойству PROFILE_ID
        $teacherElement = $this->findTeacherElementByProfileId($teacherId);

        if ($teacherElement) {
            return [
                'success' => true,
                'teacherName' => $userName,
                'teacherUrl' => $teacherElement['DETAIL_PAGE_URL']
            ];
        } else {
            return [
                'success' => true,
                'teacherName' => $userName,
                'teacherUrl' => '' // или URL по умолчанию
            ];
        }
    }

    /**
     * Получить информацию о пользователе
     */
    public function getUserInfoAction($userId)
    {
        if (!$userId) {
            return ['success' => false, 'error' => 'Invalid user ID'];
        }

        $user = \CUser::GetByID($userId)->Fetch();
        if ($user) {
            $userName = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
            if (empty($userName)) {
                $userName = $user['LOGIN'];
            }
            return ['success' => true, 'userName' => $userName];
        }

        return ['success' => false, 'error' => 'User not found'];
    }

    /**
     * Получить список учеников преподавателя для выпадающего списка
     */
    public function getTeacherStudentsAction()
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized'];
        }

        $teacherId = $USER->GetID();
        $mode = $this->getUserMode($teacherId);

        if ($mode !== 'teacher') {
            return ['success' => false, 'error' => 'Только преподаватели могут просматривать учеников'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        try {
            $students = TeacherScheduleManager::getTeacherStudentsDetailed($teacherId);

            $studentList = [];
            foreach ($students as $student) {
                $studentList[] = [
                    'id' => $student['STUDENT_ID'],
                    'name' => $student['STUDENT_NAME'],
                    'notes' => $student['NOTES']
                ];
            }

            return [
                'success' => true,
                'students' => $studentList
            ];

        } catch (\Exception $e) {
            error_log('Get teacher students error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Ошибка загрузки учеников'];
        }
    }

    /**
     * Записать ученика на занятие (для преподавателя)
     */
    public function bookStudentForSlotAction($slotId, $studentId)
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized'];
        }

        $teacherId = $USER->GetID();
        $mode = $this->getUserMode($teacherId);

        if ($mode !== 'teacher') {
            return ['success' => false, 'error' => 'Только преподаватели могут записывать учеников'];
        }

        if (empty($slotId) || empty($studentId)) {
            return ['success' => false, 'error' => 'Slot ID and Student ID are required'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        // Проверяем, что слот принадлежит преподавателю
        $entity = TeacherScheduleManager::getEntity();
        if (!$entity) {
            return ['success' => false, 'error' => 'Schedule entity not found'];
        }

        try {
            $slot = $entity::getById($slotId)->fetch();
            if (!$slot || $slot['UF_TEACHER_ID'] != $teacherId) {
                return ['success' => false, 'error' => 'Slot not found or access denied'];
            }

            // Проверяем, что ученик связан с преподавателем
            $isStudentRelated = TeacherScheduleManager::checkTeacherStudentRelation($teacherId, $studentId);
            if (!$isStudentRelated) {
                return ['success' => false, 'error' => 'Ученик не связан с вами'];
            }

            // Проверяем, что слот свободен
            $freeStatusId = TeacherScheduleManager::getStatusIdByXmlId('free');
            if ($slot['UF_STATUS'] != $freeStatusId) {
                return ['success' => false, 'error' => 'Слот уже занят'];
            }

            // Записываем ученика
            $result = TeacherScheduleManager::bookSlotForStudent($slotId, $studentId);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Ученик успешно записан на занятие'
                ];
            } else {
                return ['success' => false, 'error' => 'Ошибка записи ученика'];
            }

        } catch (\Exception $e) {
            error_log('Book student for slot error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Ошибка записи'];
        }
    }

    /**
     * Освободить слот (отменить запись ученика)
     */
    public function freeSlotAction($slotId)
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized'];
        }

        $teacherId = $USER->GetID();
        $mode = $this->getUserMode($teacherId);

        if ($mode !== 'teacher') {
            return ['success' => false, 'error' => 'Только преподаватели могут освобождать слоты'];
        }

        if (empty($slotId)) {
            return ['success' => false, 'error' => 'Slot ID is required'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        // Проверяем, что слот принадлежит преподавателю
        $entity = TeacherScheduleManager::getEntity();
        if (!$entity) {
            return ['success' => false, 'error' => 'Schedule entity not found'];
        }

        try {
            $slot = $entity::getById($slotId)->fetch();
            if (!$slot || $slot['UF_TEACHER_ID'] != $teacherId) {
                return ['success' => false, 'error' => 'Slot not found or access denied'];
            }

            // Освобождаем слот
            $result = $entity::update($slotId, [
                'UF_STUDENT_ID' => null,
                'UF_STATUS' => TeacherScheduleManager::getStatusIdByXmlId('free')
            ]);

            if ($result->isSuccess()) {
                return [
                    'success' => true,
                    'message' => 'Слот успешно освобожден'
                ];
            } else {
                return ['success' => false, 'error' => 'Ошибка освобождения слота'];
            }

        } catch (\Exception $e) {
            error_log('Free slot error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Ошибка освобождения слота'];
        }
    }

    /**
     * Найти преподавателя, у которого есть занятия с учеником
     */
    private function findTeacherWithStudentLessons($studentId)
    {
        // Получаем активных преподавателей ученика из новой таблицы связей
        $teachers = TeacherScheduleManager::getStudentTeachers($studentId);

        if (!empty($teachers)) {
            return $teachers[0]; // первого преподавателя
        }

        // Fallback: ищем в расписании (старая логика)
        $entity = TeacherScheduleManager::getEntity();
        if (!$entity) {
            return 0;
        }

        try {
            // Ищем ближайшее занятие ученика
            $today = new DateTime();
            $todayFormatted = $today->format('d.m.Y');

            $schedule = $entity::getList([
                'filter' => [
                    '=UF_STUDENT_ID' => (int)$studentId,
                    '>=UF_DATE' => $todayFormatted
                ],
                'select' => ['UF_TEACHER_ID', 'UF_DATE', 'UF_START_TIME'],
                'order' => ['UF_DATE' => 'ASC', 'UF_START_TIME' => 'ASC'],
                'limit' => 1
            ])->fetch();

            if ($schedule && $schedule['UF_TEACHER_ID'] > 0) {
                // Создаем связь при нахождении занятия
                TeacherScheduleManager::addTeacherStudentRelation($schedule['UF_TEACHER_ID'], $studentId);
                return $schedule['UF_TEACHER_ID'];
            }

            return 0;

        } catch (\Exception $e) {
            error_log('Find teacher for student error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Записать учеников на новый слот (для преподавателей)
     */
    public function saveSlotNewAction($slotData)
    {
        global $USER;

        // Проверяем авторизацию
        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized', 'message' => 'Для записи необходимо авторизоваться'];
        }

        $teacherId = $USER->GetID(); // Преподаватель - текущий пользователь

        // Проверяем, что пользователь - преподаватель
        $mode = $this->getUserMode($teacherId);
        if ($mode !== 'teacher') {
            return ['success' => false, 'error' => 'Только преподаватели могут записывать учеников'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        // Валидация
        if (empty($slotData['slot_id'])) {
            return ['success' => false, 'error' => 'Slot ID is required'];
        }

        if (empty($slotData['student_ids']) || !is_array($slotData['student_ids'])) {
            return ['success' => false, 'error' => 'Необходимо выбрать хотя бы одного ученика'];
        }

        $slotId = $slotData['slot_id'];
        $studentIds = $slotData['student_ids'];
        $teacherTimezone = TeacherScheduleManager::getTeacherTimezone($teacherId);

        // Обрабатываем только пустые слоты
        if (strpos($slotId, 'empty_') === 0) {
            $parts = explode('_', $slotId);
            if (count($parts) === 3) {
                $teacherDate = $parts[1]; // дата уже в поясе преподавателя
                $teacherHour = $parts[2]; // час уже в поясе преподавателя

                // Проверяем, что время в будущем (со следующего часа после текущего)
                if (!$this->isFutureTime($teacherDate, $teacherHour, $teacherTimezone)) {
                    return ['success' => false, 'error' => 'Можно записываться только на будущее время (со следующего часа)'];
                }

                // Проверяем, не занят ли этот слот в поясе преподавателя
//                if (!$this->checkTemplateSlotAvailability($teacherDate, $teacherHour, $teacherId, $teacherTimezone)) {
//                    return ['success' => false, 'error' => 'Это время уже занято'];
//                }

                // Проверяем, что все ученики связаны с преподавателем
                foreach ($studentIds as $studentId) {
                    if (!TeacherScheduleManager::checkTeacherStudentRelation($teacherId, $studentId)) {
                        return ['success' => false, 'error' => 'Ученик #' . $studentId . ' не связан с вами'];
                    }
                }

                // Создаем слот в расписании преподавателя
                $startTime = $teacherHour . ':00';
                $endTime = ($teacherHour + 1) . ':00';

                $result = TeacherScheduleManager::addScheduleSlot(
                    $teacherId,
                    $teacherDate,
                    $startTime,
                    $endTime,
                    'Английский язык',
                    $teacherTimezone
                );

                if ($result && $result->isSuccess()) {
                    $newSlotId = $result->getId();

                    // Записываем первого ученика на занятие
                    $firstStudentId = $studentIds[0];
                    $bookResult = TeacherScheduleManager::bookSlotForStudent($newSlotId, $firstStudentId, $teacherTimezone);

                    if ($bookResult) {
                        // Создаем связи для всех учеников
                        foreach ($studentIds as $studentId) {
                            TeacherScheduleManager::addTeacherStudentRelation($teacherId, $studentId);
                        }

                        // Если учеников несколько, добавляем заметку об этом
                        if (count($studentIds) > 1) {
                            $notes = 'Групповое занятие. Ученики: ' . implode(', ', $studentIds);
                            TeacherScheduleManager::updateScheduleSlot($newSlotId, [
                                'UF_NOTES' => $notes
                            ], $teacherTimezone);
                        }

                        return [
                            'success' => true,
                            'message' => 'Ученики успешно записаны на урок',
                            'slot_id' => $newSlotId,
                            'student_count' => count($studentIds)
                        ];
                    }
                }

                return ['success' => false, 'error' => 'Ошибка создания занятия'];
            }
        } else {
            return ['success' => false, 'error' => 'Invalid slot type'];
        }
    }

    /**
     * Проверить, что время в будущем (со следующего часа после текущего)
     */
    private function isFutureTime($date, $hour, $timezone)
    {
        try {
            // Текущее время в указанном часовом поясе
            $now = new DateTime('now', new DateTimeZone($timezone));

            // Время слота
            $slotTime = new DateTime($date . ' ' . $hour . ':00:00', new DateTimeZone($timezone));

            // Добавляем 1 час к текущему времени для проверки "со следующего часа"
            $nextHour = clone $now;
            $nextHour->modify('+1 hour');
            $nextHour->setTime($nextHour->format('H'), 0, 0); // Округляем до начала часа

            return $slotTime >= $nextHour;

        } catch (Exception $e) {
            error_log('Future time check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Стандартный метод выполнения компонента
     */
    public function executeComponent()
    {
        if (!Loader::includeModule('xillix')) {
            ShowError('Module xillix not installed');
            return;
        }

        global $USER;

        // Определяем режим пользователя
        $userId = $USER->GetID();
        $mode = $userId ? $this->getUserMode($userId) : 'guest';

        // Получаем часовой пояс пользователя
        $currentTimezone = $this->getUserTimezone();

        $this->arResult = [
            'USER_ID' => $userId,
            'MODE' => $mode,
            'IS_TEACHER_MODE' => $mode === 'teacher',
            'IS_STUDENT_MODE' => $mode === 'student',
            'DEFAULT_DAY_ONLY' => $this->arParams['DEFAULT_DAY_ONLY'] !== 'N',
            'TIMEZONES' => TeacherScheduleManager::getTimezonesSorted(),
            'CURRENT_TIMEZONE' => $currentTimezone,
            'SIGNED_PARAMS' => $this->getSignedParameters(),
        ];

        $this->IncludeComponentTemplate();
    }
}