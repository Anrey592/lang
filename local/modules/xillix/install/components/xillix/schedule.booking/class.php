<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\UserTable;
use Xillix\TeacherScheduleManager;

class XillixScheduleBookingComponent extends CBitrixComponent implements Controllerable
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
            'bookSlot' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    /**
     * Получить расписание для записи с конвертацией в пояс пользователя
     */
    public function getScheduleAction($teacherId = null, $weekStart = null, $timezone = 'Europe/Moscow')
    {
        if (!$teacherId) {
            $teacherId = $this->arParams['TEACHER_ID'];
        }

        if (!$teacherId) {
            return ['success' => false, 'error' => 'Invalid teacher ID'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        // Получаем часовой пояс преподавателя
        $teacherTimezone = TeacherScheduleManager::getTeacherTimezone($teacherId);

        // Получаем часовой пояс текущего пользователя
        $userTimezone = $this->getUserTimezone();

        // Получаем объединенное расписание в поясе преподавателя
        $schedule = TeacherScheduleManager::getBookingSchedule(
            $teacherId,
            $weekStart,
            $teacherTimezone, // расписание в поясе преподавателя
            $this->getCurrentStudentId()
        );


        // Конвертируем расписание в часовой пояс пользователя
        $convertedSchedule = TeacherScheduleManager::convertScheduleToUserTimezone(
            $schedule,
            $teacherId,
            $userTimezone
        );

        return [
            'success' => true,
            'schedule' => $convertedSchedule,
            'timezone_info' => [
                'teacher_timezone' => $teacherTimezone,
                'user_timezone' => $userTimezone,
                'converted' => $teacherTimezone !== $userTimezone
            ]
        ];
    }

    /**
     * Забронировать слот
     */
    public function bookSlotAction($slotData)
    {
        global $USER;

        // Проверяем авторизацию
        if (!$USER->IsAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized', 'message' => 'Для записи необходимо авторизоваться'];
        }

        $teacherId = $slotData['teacherId'] ?? $this->arParams['TEACHER_ID'];

        if (!$teacherId) {
            return ['success' => false, 'error' => 'Invalid teacher ID'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        // Валидация
        if (empty($slotData['slot_id'])) {
            return ['success' => false, 'error' => 'Slot ID is required'];
        }

        $slotId = $slotData['slot_id'];
        $studentId = $USER->GetID();

        // Получаем часовой пояс преподавателя и пользователя
        $teacherTimezone = TeacherScheduleManager::getTeacherTimezone($teacherId);
        $userTimezone = $slotData['timezone'] ?? $this->getUserTimezone();

        // Для шаблонных слотов нужно конвертировать время из пояса пользователя в пояс преподавателя
        if (strpos($slotId, 'template_') === 0) {
            $parts = explode('_', $slotId);
            if (count($parts) === 3) {
                $userDate = $parts[1]; // дата в поясе пользователя
                $userHour = $parts[2]; // час в поясе пользователя

                // Конвертируем время пользователя в время преподавателя
                $teacherDateTime = $this->convertUserTimeToTeacherTime(
                    $userDate,
                    $userHour,
                    $userTimezone,
                    $teacherTimezone
                );

                if (!$teacherDateTime) {
                    return ['success' => false, 'error' => 'Ошибка конвертации времени'];
                }

                $teacherDate = $teacherDateTime['date'];
                $teacherHour = $teacherDateTime['hour'];

                // Проверяем, не занят ли этот слот в поясе преподавателя
                if (!$this->checkTemplateSlotAvailability($teacherDate, $teacherHour, $teacherId, $teacherTimezone)) {
                    return ['success' => false, 'error' => 'Это время уже занято'];
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
                    $bookResult = TeacherScheduleManager::bookSlotForStudent($newSlotId, $studentId, $teacherTimezone);

                    if ($bookResult) {
                        TeacherScheduleManager::addTeacherStudentRelation($teacherId, $studentId);
                        return ['success' => true, 'message' => 'Вы успешно записались на урок'];
                    }
                }

                return ['success' => false, 'error' => 'Ошибка создания занятия'];
            }
        } else {
            // Обычный слот - логика остается прежней
            $slot = $this->checkSlotAvailability($slotId, $teacherId);
            if (!$slot) {
                return ['success' => false, 'error' => 'Slot not available'];
            }

            $result = TeacherScheduleManager::bookSlotForStudent($slotId, $studentId, $teacherTimezone);

            if ($result) {
                return ['success' => true, 'message' => 'Вы успешно записались на урок'];
            } else {
                return ['success' => false, 'error' => 'Ошибка записи на урок'];
            }
        }
    }

    /**
     * Проверить доступность шаблонного слота в поясе преподавателя
     */
    private function checkTemplateSlotAvailability($teacherDate, $teacherHour, $teacherId, $teacherTimezone)
    {
        // Получаем актуальное расписание в поясе преподавателя
        $schedule = TeacherScheduleManager::getBookingSchedule($teacherId, $teacherDate, $teacherTimezone);

        // Ищем наш слот
        foreach ($schedule as $slot) {
            if ($slot['date'] == $teacherDate && (int)$slot['hour'] == (int)$teacherHour) {
                // Слот доступен если не забронирован и доступен для записи
                return !$slot['is_booked'] && $slot['is_available'];
            }
        }

        // Если слота нет в расписании, значит он доступен (из шаблона)
        return true;
    }

    /**
     * Конвертировать время пользователя в время преподавателя
     */
    private function convertUserTimeToTeacherTime($userDate, $userHour, $userTimezone, $teacherTimezone)
    {
        try {
            // Создаем DateTime в поясе пользователя
            $userTime = new DateTime(
                $userDate . ' ' . $userHour . ':00:00',
                new DateTimeZone($userTimezone)
            );

            // Конвертируем в часовой пояс преподавателя
            $userTime->setTimezone(new DateTimeZone($teacherTimezone));

            return [
                'date' => $userTime->format('Y-m-d'),
                'hour' => (int)$userTime->format('H')
            ];

        } catch (Exception $e) {
            error_log('Time conversion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверить доступность обычного слота
     */
    private function checkSlotAvailability($slotId, $teacherId)
    {
        $entity = TeacherScheduleManager::getEntity();
        if (!$entity) {
            return false;
        }

        try {
            $slot = $entity::getById($slotId)->fetch();

            if (!$slot || $slot['UF_TEACHER_ID'] != $teacherId) {
                return false;
            }

            // Проверяем, что слот свободен
            $statusId = TeacherScheduleManager::getStatusIdByXmlId('free');
            if ($slot['UF_STATUS'] != $statusId) {
                return false;
            }

            return $slot;
        } catch (\Exception $e) {
            error_log('Check slot availability error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить часовой пояс пользователя
     */
    private function getUserTimezone()
    {
        global $USER;

        // Если пользователь авторизован, пытаемся получить его часовой пояс
        if ($USER->IsAuthorized()) {
            $user = UserTable::getList(
                [
                    'filter' => [
                        'ID' => $USER->GetID()
                    ],
                    'select' => [
                        'ID',
                        'UF_TIMEZONE'
                    ]
                ]
            )->fetch();
            return $user['UF_TIMEZONE'] ?: 'Europe/Moscow';
        }

        // Пытаемся получить из cookie
        if (isset($_COOKIE['user_timezone'])) {
            $timezone = $_COOKIE['user_timezone'];
            if (in_array($timezone, \DateTimeZone::listIdentifiers())) {
                return $timezone;
            }
        }

        // Определяем по IP или используем по умолчанию
        return $this->getTimezoneByIP();
    }

    /**
     * Получить ID текущего студента (если авторизован)
     */
    private function getCurrentStudentId()
    {
        global $USER;
        return $USER->IsAuthorized() ? $USER->GetID() : null;
    }

    /**
     * Определить часовой пояс по IP
     */
    private function getTimezoneByIP()
    {
        // Упрощенная логика - в реальном проекте использовать сервис определения по IP
        // Для примера возвращаем московское время
        return 'Europe/Moscow';
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
        setcookie('user_timezone', $timezone, time() + 60 * 60 * 24 * 30, '/'); // 30 дней

        // Если пользователь авторизован, сохраняем в его профиль
        if ($USER->IsAuthorized()) {
            $result = TeacherScheduleManager::setTeacherTimezone($USER->GetID(), $timezone);
        } else {
            $result = true;
        }

        return ['success' => (bool)$result];
    }

    /**
     * Стандартный метод выполнения компонента
     */
    public function executeComponent()
    {
        // Подключаем модуль
        if (!Loader::includeModule('xillix')) {
            ShowError('Module xillix not installed');
            return;
        }

        // Инициализация параметров
        $this->arParams['TEACHER_ID'] = (int)$this->arParams['TEACHER_ID'];
        $this->arParams['DEFAULT_DAY_ONLY'] = $this->arParams['DEFAULT_DAY_ONLY'] !== 'N';

        if ($this->arParams['TEACHER_ID'] <= 0) {
            ShowError('Invalid teacher ID');
            return;
        }

        // Получаем часовой пояс пользователя
        $currentTimezone = $this->getUserTimezone();

        // Подготавливаем результат
        $this->arResult = [
            'TEACHER_ID' => $this->arParams['TEACHER_ID'],
            'TEACHER_NAME' => $this->arParams['TEACHER_NAME'] ?? 'Преподаватель',
            'DEFAULT_DAY_ONLY' => $this->arParams['DEFAULT_DAY_ONLY'],
            'IS_AUTHORIZED' => $GLOBALS['USER']->IsAuthorized(),
            'TIMEZONES_SORTED' => TeacherScheduleManager::getTimezonesSorted(),
            'CURRENT_TIMEZONE' => $currentTimezone,
            'SIGNED_PARAMS' => $this->getSignedParameters(),
        ];

        $this->IncludeComponentTemplate();
    }
}