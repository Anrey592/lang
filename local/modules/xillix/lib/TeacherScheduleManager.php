<?php

namespace Xillix;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

Loc::loadMessages(__FILE__);

class TeacherScheduleManager
{
    const HL_BLOCK_NAME = 'XillixSchedule';

    const STATUS_FREE = 'free';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_CANCELED = 'canceled';
    const HL_BLOCK_TEMPLATE_NAME = 'XillixScheduleTemplate';
    const HL_BLOCK_TEACHER_STUDENT_NAME = 'XillixTeacherStudent';

    public static function getTimezones()
    {
        return \DateTimeZone::listIdentifiers();
    }

    /**
     * Получить объединенное расписание для записи (шаблон + занятые слоты)
     */
    public static function getBookingSchedule($teacherId, $weekStart = null, $timezone = 'Europe/Moscow', $currentStudentId = null)
    {
        error_log("getBookingSchedule called: teacherId=$teacherId, weekStart=" . ($weekStart ?: 'null'));

        // Получаем часовой пояс преподавателя
        $teacherTimezone = self::getTeacherTimezone($teacherId);

        // Получаем занятые слоты в поясе преподавателя
        $busySlots = self::getTeacherSchedule($teacherId, $weekStart, $teacherTimezone);

        // Получаем шаблон расписания в поясе преподавателя
        $templateSlots = self::getTeacherScheduleTemplate($teacherId, false); // false - ВСЕ часы, не только дневные

        // Создаем карту занятых слотов
        $busySlotsMap = [];
        foreach ($busySlots as $slot) {
            try {
                $dateObj = \DateTime::createFromFormat('d.m.Y', $slot['UF_DATE']);
                if ($dateObj) {
                    $dateKey = $dateObj->format('Y-m-d');

                    // Извлекаем час из времени преподавателя
                    $startTime = \DateTime::createFromFormat('d.m.Y H:i:s', $slot['UF_START_TIME']);
                    if ($startTime) {
                        $hour = (int)$startTime->format('H');

                        $busySlotsMap[$dateKey][$hour] = [
                            'slot_id' => $slot['ID'],
                            'status' => $slot['UF_STATUS'],
                            'student_id' => $slot['UF_STUDENT_ID']
                        ];
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Создаем карту шаблонных слотов
        $templateSlotsMap = [];
        foreach ($templateSlots as $templateSlot) {
            $dayOfWeek = $templateSlot['UF_DAY_OF_WEEK'];
            $startHour = (int)substr($templateSlot['UF_START_TIME'], 0, 2);
            $endHour = (int)substr($templateSlot['UF_END_TIME'], 0, 2);

            // Добавляем все часы из шаблона
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                if (!isset($templateSlotsMap[$dayOfWeek])) {
                    $templateSlotsMap[$dayOfWeek] = [];
                }
                $templateSlotsMap[$dayOfWeek][$hour] = true;
            }
        }

        // Создаем неделю
        if (!$weekStart) {
            $weekStart = new \DateTime();
        } else {
            $weekStart = new \DateTime($weekStart);
        }

        $weekStartClone = clone $weekStart;
        $weekStartClone->setTime(0, 0, 0);

        // Создаем дни недели с понедельника по воскресенье
        $weekDays = [];
        for ($i = 1; $i < 8; $i++) {
            $currentDay = clone $weekStartClone;
            $currentDay->add(new \DateInterval('P' . $i . 'D'));
            $weekDays[] = $currentDay;
        }

        // Формируем объединенное расписание (пока в поясе преподавателя) - ВСЕ ЧАСЫ 0-24
        $bookingSlots = [];

        foreach ($weekDays as $day) {
            $dayOfWeek = $day->format('N'); // 1-7 (понедельник-воскресенье)
            $dateKey = $day->format('Y-m-d');

            // Генерируем все часы от 0 до 23
            for ($hour = 0; $hour < 24; $hour++) {
                $isInTemplate = isset($templateSlotsMap[$dayOfWeek][$hour]);
                $isBooked = isset($busySlotsMap[$dateKey][$hour]);
                $slotInfo = $busySlotsMap[$dateKey][$hour] ?? null;

                $isBookedByCurrentStudent = $isBooked && $currentStudentId &&
                    $slotInfo['student_id'] == $currentStudentId;
                $isBookedByOtherStudent = $isBooked && $currentStudentId &&
                    $slotInfo['student_id'] != $currentStudentId;

                // Определяем доступность слота
                $isAvailable = $isInTemplate && !$isBooked;

                // Формируем slot_id для каждого часа
                if ($isBooked) {
                    // Для занятых слотов используем ID из базы
                    $slotId = $slotInfo['slot_id'];
                } elseif ($isInTemplate) {
                    // Для свободных шаблонных слотов создаем ID на основе даты и часа
                    $slotId = 'template_' . $dateKey . '_' . $hour;
                } else {
                    // Для часов вне шаблона - тоже создаем slot_id, но отмечаем как недоступные
                    $slotId = 'unavailable_' . $dateKey . '_' . $hour;
                }

                $bookingSlots[] = [
                    'id' => $slotId,
                    'date' => $dateKey,
                    'day_of_week' => $dayOfWeek,
                    'hour' => $hour,
                    'start_time' => sprintf("%02d:00", $hour),
                    'end_time' => sprintf("%02d:00", $hour + 1),
                    'is_available' => $isAvailable,
                    'is_booked' => $isBooked,
                    'is_booked_by_current' => $isBookedByCurrentStudent,
                    'is_booked_by_other' => $isBookedByOtherStudent,
                    'is_template' => $isInTemplate,
                    'is_in_template' => $isInTemplate, // дублируем для ясности
                    'status' => $isBooked ?
                        ($isBookedByCurrentStudent ? 'booked' : 'unavailable') :
                        ($isAvailable ? 'available' : 'unavailable'),
                    'display_date' => $day->format('d.m.Y'),
                    'student_id' => $isBooked ? $slotInfo['student_id'] : null,
                    'template_teacher_timezone' => $teacherTimezone // сохраняем пояс преподавателя для шаблона
                ];
            }
        }

        return $bookingSlots;
    }

    /**
     * Конвертировать расписание из пояса преподавателя в пояс пользователя
     */
    public static function convertScheduleToUserTimezone($schedule, $teacherId, $userTimezone)
    {
        $teacherTimezone = self::getTeacherTimezone($teacherId);

        // Если пояса одинаковые - возвращаем как есть
        if ($teacherTimezone === $userTimezone) {
            return $schedule;
        }

        $convertedSchedule = [];

        foreach ($schedule as $slot) {
            $convertedSlot = $slot;

            try {
                // Для шаблонных слотов используем пояс преподавателя из template_teacher_timezone
                $recordTimezone = $slot['template_teacher_timezone'] ?? $teacherTimezone;

                // Конвертируем дату и время
                if (!empty($slot['date']) && !empty($slot['hour'])) {
                    $teacherTime = new \DateTime(
                        $slot['date'] . ' ' . $slot['hour'] . ':00:00',
                        new \DateTimeZone($recordTimezone)
                    );

                    $teacherTime->setTimezone(new \DateTimeZone($userTimezone));

                    // Сохраняем конвертированную дату для data-slot-date
                    $convertedSlot['date'] = $teacherTime->format('Y-m-d');
                    $convertedSlot['hour'] = (int)$teacherTime->format('H');
                    $convertedSlot['display_time'] = $teacherTime->format('H:i');
                    $convertedSlot['timezone_converted'] = true;

                    // Сохраняем оригинальные данные для отладки
                    $convertedSlot['original_date'] = $slot['date'];
                    $convertedSlot['original_hour'] = $slot['hour'];
                    $convertedSlot['original_timezone'] = $recordTimezone;
                    $convertedSlot['target_timezone'] = $userTimezone;
                }

            } catch (\Exception $e) {
                error_log('Schedule timezone conversion error: ' . $e->getMessage());
                $convertedSlot['timezone_converted'] = false;
            }

            $convertedSchedule[] = $convertedSlot;
        }

        return $convertedSchedule;
    }

    /**
     * Конвертировать шаблонные слоты в другой часовой пояс
     */
    private static function convertTemplateSlotsTimezone($templateSlots, $fromTimezone, $toTimezone)
    {
        // Если пояса одинаковые - возвращаем как есть
        if ($fromTimezone === $toTimezone) {
            return $templateSlots;
        }

        $convertedSlots = [];

        foreach ($templateSlots as $slot) {
            $convertedSlot = $slot;

            try {
                // Конвертируем время начала
                $startTime = $slot['UF_START_TIME'] . ':00'; // добавляем секунды
                $startDateTime = \DateTime::createFromFormat('H:i:s', $startTime);
                if ($startDateTime) {
                    // Создаем полную дату с учетом часового пояса
                    $today = new \DateTime('now', new \DateTimeZone($fromTimezone));
                    $startDateTime->setDate(
                        $today->format('Y'),
                        $today->format('m'),
                        $today->format('d')
                    );

                    // Конвертируем в целевой пояс
                    $startDateTime->setTimezone(new \DateTimeZone($toTimezone));
                    $convertedSlot['UF_START_TIME'] = $startDateTime->format('H:i');
                }

                // Конвертируем время окончания
                $endTime = $slot['UF_END_TIME'] . ':00'; // добавляем секунды
                $endDateTime = \DateTime::createFromFormat('H:i:s', $endTime);
                if ($endDateTime) {
                    // Создаем полную дату с учетом часового пояса
                    $today = new \DateTime('now', new \DateTimeZone($fromTimezone));
                    $endDateTime->setDate(
                        $today->format('Y'),
                        $today->format('m'),
                        $today->format('d')
                    );

                    // Конвертируем в целевой пояс
                    $endDateTime->setTimezone(new \DateTimeZone($toTimezone));
                    $convertedSlot['UF_END_TIME'] = $endDateTime->format('H:i');
                }

            } catch (\Exception $e) {
                error_log('Template timezone conversion error: ' . $e->getMessage());
            }

            $convertedSlots[] = $convertedSlot;
        }

        return $convertedSlots;
    }

    /**
     * Получить расписание ученика
     */
    public static function getStudentSchedule($studentId, $weekStart = null, $timezone = 'Europe/Moscow')
    {
        if (!$studentId) {
            return [];
        }

        $entity = self::getEntity();
        if (!$entity) {
            return [];
        }

        // Определяем период недели
        if (!$weekStart) {
            $weekStart = new \DateTime();
        } else {
            // Используем стандартный DateTime
            $weekStart = \DateTime::createFromFormat('Y-m-d', $weekStart);
            if (!$weekStart) {
                $weekStart = new \DateTime();
            }
        }

        $weekStart->setTime(0, 0, 0);
        $weekEnd = clone $weekStart;
        $weekEnd->add(new \DateInterval('P7D'));

        $startDate = $weekStart->format('d.m.Y');
        $endDate = $weekEnd->format('d.m.Y');

        try {
            $schedule = $entity::getList([
                'filter' => [
                    '=UF_STUDENT_ID' => (int)$studentId,
                    '>UF_DATE' => $startDate,
                    '<=UF_DATE' => $endDate
                ],
                'order' => ['UF_DATE' => 'ASC', 'UF_START_TIME' => 'ASC']
            ])->fetchAll();

            // Конвертация статусов
            $schedule = self::convertStatusToXmlId($schedule);

            // Конвертация времени в часовой пояс ученика
            foreach ($schedule as &$slot) {
                // Получаем часовой пояс записи
                $recordTimezone = $slot['UF_TIMEZONE'] ?? 'Europe/Moscow';

                // Конвертируем только если пояса разные
                if ($recordTimezone !== $timezone) {
                    if (!empty($slot['UF_START_TIME'])) {
                        $slot['UF_START_TIME'] = self::convertToTimezone($slot['UF_START_TIME'], $recordTimezone, $timezone);
                    }
                    if (!empty($slot['UF_END_TIME'])) {
                        $slot['UF_END_TIME'] = self::convertToTimezone($slot['UF_END_TIME'], $recordTimezone, $timezone);
                    }
                }
            }

            return $schedule;

        } catch (\Exception $e) {
            error_log('Xillix Student Schedule Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить преподавателей ученика
     */
    public static function getStudentTeachers($studentId)
    {
        $entity = self::getEntity();
        if (!$entity) {
            return [];
        }

        try {
            $schedule = $entity::getList([
                'filter' => [
                    '=UF_STUDENT_ID' => (int)$studentId
                ],
                'select' => ['UF_TEACHER_ID'],
                'group' => ['UF_TEACHER_ID'],
                'order' => ['UF_TEACHER_ID' => 'ASC']
            ])->fetchAll();

            return array_map(function ($item) {
                return $item['UF_TEACHER_ID'];
            }, $schedule);

        } catch (\Exception $e) {
            error_log('Xillix getStudentTeachers Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Забронировать слот для студента
     */
    public static function bookSlotForStudent($slotId, $studentId, $timezone = 'Europe/Moscow')
    {
        $entity = self::getEntity();
        if (!$entity) {
            return false;
        }

        try {
            // Сначала получаем данные слота перед обновлением
            $slot = $entity::getById($slotId)->fetch();
            if (!$slot) {
                return false;
            }

            // Обновляем слот - устанавливаем студента и статус "забронирован"
            $result = $entity::update($slotId, [
                'UF_STUDENT_ID' => (int)$studentId,
                'UF_STATUS' => self::getStatusIdByXmlId('blocked')
            ]);

            if ($result->isSuccess()) {
                self::addStudentToTrueConfConference($slotId, $studentId);

                // Отправляем уведомления в Telegram
                self::sendBookingNotifications($slotId, $slot, $studentId);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log('Xillix Book Slot Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправить уведомления о бронировании
     */
    private static function sendBookingNotifications($slotId, $slot, $studentId)
    {
        if (!class_exists('Xillix\NotificationManager')) {
            return;
        }

        try {
            // Получаем дату и время из данных слота
            $lessonDate = $slot['UF_DATE']; // в формате d.m.Y
            $lessonTime = $slot['UF_START_TIME']; // в формате d.m.Y H:i:s

            // Извлекаем только время
            $timeParts = explode(' ', $lessonTime);
            $timeOnly = count($timeParts) > 1 ? $timeParts[1] : $lessonTime;

            // Отправляем уведомление
            \Xillix\NotificationManager::sendLessonBookingNotification(
                $slotId,
                $slot['UF_TEACHER_ID'],
                $studentId,
                $lessonDate,
                $timeOnly
            );
        } catch (\Exception $e) {
            error_log('Send booking notification error: ' . $e->getMessage());
        }
    }


    public static function getTimezoneWithOffset($timezone)
    {
        try {
            $date = new \DateTime('now', new \DateTimeZone($timezone));
            $offset = $date->getOffset();
            $hours = floor(abs($offset) / 3600);
            $minutes = (abs($offset) % 3600) / 60;
            $sign = $offset >= 0 ? '+' : '-';

            return "(UTC{$sign}" . sprintf("%02d:%02d", $hours, $minutes) . ") {$timezone}";
        } catch (\Exception $e) {
            return $timezone;
        }
    }

    public static function getTimezonesSorted()
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $timezonesWithOffset = [];

        foreach ($timezones as $timezone) {
            try {
                $date = new \DateTime('now', new \DateTimeZone($timezone));
                $offset = $date->getOffset();
                $timezonesWithOffset[$timezone] = $offset;
            } catch (\Exception $e) {
                $timezonesWithOffset[$timezone] = 0;
            }
        }

        // Сортируем по offset
        asort($timezonesWithOffset);

        return array_keys($timezonesWithOffset);
    }

    public static function getTeacherTimezone($teacherId)
    {
        $user = UserTable::getList(
            [
                'filter' => [
                    'ID' => $teacherId
                ],
                'select' => [
                    'ID',
                    'UF_TIMEZONE'
                ]
            ]
        )->fetch();
        return $user['UF_TIMEZONE'] ?: 'Europe/Moscow';
    }

    public static function setTeacherTimezone($teacherId, $timezone)
    {
        $user = new \CUser;
        return $user->Update($teacherId, ['UF_TIMEZONE' => $timezone]);
    }

    public static function getHLBlockId()
    {
        if (!\Bitrix\Main\Loader::includeModule('highloadblock')) {
            return false;
        }

        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => self::HL_BLOCK_NAME]
        ])->fetch();

        return $hlblock ? $hlblock['ID'] : false;
    }

    public static function getEntity()
    {
        $hlblockId = self::getHLBlockId();
        if (!$hlblockId) {
            return false;
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblockId);
        return $entity->getDataClass();
    }

    public static function getTeacherSchedule($teacherId, $weekStart = null, $timezone = 'Europe/Moscow')
    {
        if (!$weekStart) {
            $weekStart = new \DateTime();
        }

        // Убедимся, что это DateTime объект
        if (!$weekStart instanceof \DateTime) {
            if (is_string($weekStart) && !empty($weekStart)) {
                // Используем стандартный DateTime вместо Bitrix
                $weekStart = \DateTime::createFromFormat('Y-m-d', $weekStart);
                if ($weekStart === false) {
                    $weekStart = new \DateTime();
                }
            } else {
                $weekStart = new \DateTime();
            }
        }

        $weekStartClone = clone $weekStart;
        $weekStartClone->setTime(0, 0, 0);

        $weekEnd = clone $weekStartClone;
        $weekEnd->add(new \DateInterval('P7D')); // Добавляем 7 дней

        $startDate = $weekStartClone->format('d.m.Y');
        $endDate = $weekEnd->format('d.m.Y');

        $entity = self::getEntity();
        if (!$entity) {
            return [];
        }

        $filter = [
            '=UF_TEACHER_ID' => (int)$teacherId,
        ];

        // Добавляем фильтры по дате только если они точно валидны
        if ($startDate && self::isValidDate($startDate, 'd.m.Y')) {
            $filter['>UF_DATE'] = $startDate;
        }

        if ($endDate && self::isValidDate($endDate, 'd.m.Y')) {
            $filter['<=UF_DATE'] = $endDate;
        }

        try {
            $schedule = $entity::getList([
                'filter' => $filter,
                'order' => ['UF_DATE' => 'ASC', 'UF_START_TIME' => 'ASC']
            ])->fetchAll();

            $schedule = self::convertStatusToXmlId($schedule);
        } catch (\Exception $e) {
            error_log('Xillix Schedule SQL Error: ' . $e->getMessage());
            return [];
        }

        // Конвертация времени только если часовой пояс записи отличается от целевого
        foreach ($schedule as &$slot) {
            $recordTimezone = $slot['UF_TIMEZONE'] ?? 'Europe/Moscow';

            // Конвертируем только если пояса разные
            if ($recordTimezone !== $timezone) {
                if (!empty($slot['UF_START_TIME'])) {
                    $slot['UF_START_TIME'] = self::convertToTimezone($slot['UF_START_TIME'], $recordTimezone, $timezone);
                }
                if (!empty($slot['UF_END_TIME'])) {
                    $slot['UF_END_TIME'] = self::convertToTimezone($slot['UF_END_TIME'], $recordTimezone, $timezone);
                }
            }
        }

        return $schedule;
    }

    public static function convertStatusToXmlId($schedule)
    {
        if (empty($schedule)) {
            return $schedule;
        }

        // Получаем информацию о поле UF_STATUS
        $hlblockId = self::getHLBlockId();
        if (!$hlblockId) {
            return $schedule;
        }

        $field = \CUserTypeEntity::GetList([], [
            'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
            'FIELD_NAME' => 'UF_STATUS'
        ])->Fetch();

        if (!$field) {
            return $schedule;
        }

        // Получаем значения enum
        $enum = new \CUserFieldEnum();
        $enumValues = $enum->GetList([], [
            'USER_FIELD_ID' => $field['ID']
        ]);

        $statusMap = [];
        while ($value = $enumValues->Fetch()) {
            $statusMap[$value['ID']] = $value['XML_ID'];
        }

        // Конвертируем статусы
        foreach ($schedule as &$slot) {
            if (isset($slot['UF_STATUS']) && isset($statusMap[$slot['UF_STATUS']])) {
                $slot['UF_STATUS'] = $statusMap[$slot['UF_STATUS']];
            } else {
                $slot['UF_STATUS'] = 'free'; // значение по умолчанию
            }
        }

        return $schedule;
    }

    public static function addScheduleSlot($teacherId, $date, $startTime, $endTime, $subject = 'Английский язык', $timezone = 'Europe/Moscow')
    {
        $entity = self::getEntity();
        if (!$entity) {
            return false;
        }

        if (empty($date) || empty($startTime) || empty($endTime)) {
            return false;
        }

        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj === false) {
            return false;
        }
        $dateFormatted = $dateObj->format('d.m.Y');

        // Проверяем формат времени
        $startTimeObj = \DateTime::createFromFormat('H:i', $startTime);
        $endTimeObj = \DateTime::createFromFormat('H:i', $endTime);

        if ($startTimeObj === false || $endTimeObj === false) {
            return false;
        }

        // Создаем полные datetime строки в правильном формате для Bitrix
        // Формат для datetime полей: "d.m.Y H:i:s"
        $startDateTime = $dateFormatted . ' ' . $startTime . ':00';
        $endDateTime = $dateFormatted . ' ' . $endTime . ':00';

        // Получаем ID статуса по XML_ID
        $statusId = self::getStatusIdByXmlId('free');

        try {
            $result = $entity::add([
                'UF_TEACHER_ID' => (int)$teacherId,
                'UF_DATE' => $dateFormatted,
                'UF_START_TIME' => $startDateTime,
                'UF_END_TIME' => $endDateTime,
                'UF_SUBJECT' => $subject,
                'UF_STATUS' => $statusId,
                'UF_TIMEZONE' => $timezone
            ]);

            if ($result->isSuccess()) {
                $slotId = $result->getId();
                $slotData = $entity::getById($slotId)->fetch();
                if ($slotData) {
                    self::createTrueConfConferenceForSlot($slotId, $slotData);
                }
                return $result;
            }

            return $result;
        } catch (\Exception $e) {
            error_log('Xillix Add Schedule Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Создаёт конференцию в TrueConf за 10 минут до UF_START_TIME на 2 часа
     * и сохраняет ссылку в UF_SCHEDULED_LESSON
     */
    public static function createTrueConfConferenceForSlot(int $slotId, array $slotData)
    {
        if (!Loader::includeModule('xillix.videoconf')) {
            throw new \Exception('Module xillix.videoconf not installed');
        }

        try {
            $teacherId = (int)($slotData['UF_TEACHER_ID'] ?? 0);
            if (!$teacherId) {
                throw new \Exception('UF_TEACHER_ID не указан');
            }

            // Получаем логин TrueConf преподавателя
            $teacherUser = UserTable::getList(
                [
                    'filter' => [
                        'ID' => $teacherId
                    ],
                    'select' => [
                        'ID',
                        'UF_TRUECONF_LOGIN'
                    ]
                ]
            )->fetch();
            if (!$teacherUser || empty($teacherUser['UF_TRUECONF_LOGIN'])) {
                throw new \Exception('У преподавателя ID=' . $teacherId . ' не заполнено UF_TRUECONF_LOGIN');
            }
            $ownerLogin = $teacherUser['UF_TRUECONF_LOGIN'];

            // Разбираем UF_START_TIME: "d.m.Y H:i:s"
            $startTime = \DateTime::createFromFormat('d.m.Y H:i:s', $slotData['UF_START_TIME'], new \DateTimeZone($slotData['UF_TIMEZONE']));
            if (!$startTime) {
                throw new \Exception('Неверный формат UF_START_TIME: ' . $slotData['UF_START_TIME']);
            }

            // Конференция начинается за 10 минут до урока
            $confStart = clone $startTime;
//            $confStart->modify('-10 minutes');

            // Продолжительность — 1.20
            $duration = 4800;

            // Получаем Unix-время начала в часовом поясе записи
            $startTimestamp = $confStart->getTimestamp();

            $tc = new \Xillix\Videoconf\TrueConfManager();
            $topic = 'Урок ' . $startTime->format('d.m.Y');

            $response = $tc->createScheduledConference(
                $topic,
                $startTimestamp,
                $duration,
                $ownerLogin,
                $slotData['UF_TIMEZONE'],
            );

            if (isset($response['conference']['webclient_url'])) {
                $entity = self::getEntity();
                if ($entity) {
                    $entity::update($slotId, [
                        'UF_SCHEDULED_LESSON' => $response['conference']['url']
                    ]);
                }
            }
        } catch (\Exception $e) {
//            \CEventLog::Add([
//                'SEVERITY' => 'WARNING',
//                'AUDIT_TYPE_ID' => 'TRUECONF_CONF_CREATE',
//                'MODULE_ID' => 'xillix',
//                'DESCRIPTION' => 'Ошибка создания конференции для слота ' . $slotId . ': ' . $e->getMessage()
//            ]);
        }
    }

    /**
     * Добавляет студента в конференцию через редактирование (PUT)
     */
    protected static function addStudentToTrueConfConference(int $slotId, int $studentId)
    {
        if (!\Bitrix\Main\Loader::includeModule('xillix.videoconf')) {
            return;
        }

        try {
            $entity = self::getEntity();
            $slot = $entity::getById($slotId)->fetch();

            if (!$slot || empty($slot['UF_SCHEDULED_LESSON'])) {
                return;
            }

            // Извлекаем ID конференции
            $url = $slot['UF_SCHEDULED_LESSON'];
            if (!preg_match('#[/](?:webrtc|c)/([0-9]+)#', $url, $matches)) {
                return;
            }
            $confId = $matches[1];

            // Получаем логин студента
            $studentUser = \Bitrix\Main\UserTable::getList([
                'filter' => ['ID' => $studentId],
                'select' => ['UF_TRUECONF_LOGIN']
            ])->fetch();

            if (!$studentUser || empty($studentUser['UF_TRUECONF_LOGIN'])) {
                return;
            }
            $studentLogin = $studentUser['UF_TRUECONF_LOGIN'];

            // Получаем текущие данные конференции
            $tc = new \Xillix\Videoconf\TrueConfManager();
            $confData = $tc->getConference($confId);
            $current = $confData['conference'];

            // Формируем invitations
            $invitations = $current['invitations'] ?? [];
            $exists = false;
            foreach ($invitations as $inv) {
                if ($inv['id'] === $studentLogin) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $invitations[] = ['id' => $studentLogin];
            }

            // Обновляем конференцию
            $tc->editConference($confId, [
                'topic' => $current['topic'],
                'type' => $current['type'],
                'recording' => $current['recording'],
                'max_participants' => $current['max_participants'] ?? 20,
                'schedule' => $current['schedule'],
                'owner' => $current['owner'],
                'invitations' => $invitations,
                'allow_guests' => $current['allow_guests'] ?? false,
                'auto_invite' => $current['auto_invite'] ?? false
            ]);

        } catch (\Exception $e) {
//            \CEventLog::Add([
//                'SEVERITY' => 'WARNING',
//                'AUDIT_TYPE_ID' => 'TRUECONF_INVITE',
//                'MODULE_ID' => 'xillix',
//                'DESCRIPTION' => 'Ошибка добавления студента ID=' . $studentId . ' в конференцию ' . $confId . ': ' . $e->getMessage()
//            ]);
        }
    }

    public static function getStatusIdByXmlId($xmlId)
    {
        $hlblockId = self::getHLBlockId();
        if (!$hlblockId) {
            return 1; // fallback
        }

        $field = \CUserTypeEntity::GetList([], [
            'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
            'FIELD_NAME' => 'UF_STATUS'
        ])->Fetch();

        if (!$field) {
            return 1; // fallback
        }

        $enum = new \CUserFieldEnum();
        $enumValue = $enum->GetList([], [
            'USER_FIELD_ID' => $field['ID'],
            'XML_ID' => $xmlId
        ])->Fetch();

        return $enumValue ? $enumValue['ID'] : 1; // fallback to first status
    }

    public static function updateScheduleSlot($slotId, $fields, $timezone = 'Europe/Moscow')
    {
        $entity = self::getEntity();
        if (!$entity) {
            return false;
        }

        // Если передали XML_ID статуса, конвертируем в ID
        if (isset($fields['UF_STATUS']) && !is_numeric($fields['UF_STATUS'])) {
            $fields['UF_STATUS'] = self::getStatusIdByXmlId($fields['UF_STATUS']);
        }

        // Обновляем часовой пояс записи если передан
        if (isset($fields['timezone'])) {
            $fields['UF_TIMEZONE'] = $fields['timezone'];
        }

        // Форматируем время для полей datetime
        if (isset($fields['UF_START_TIME']) && isset($fields['UF_DATE'])) {
            // UF_DATE уже в формате d.m.Y, UF_START_TIME в формате H:i
            $startDateTime = $fields['UF_DATE'] . ' ' . $fields['UF_START_TIME'] . ':00';
            $fields['UF_START_TIME'] = $startDateTime;
        }

        if (isset($fields['UF_END_TIME']) && isset($fields['UF_DATE'])) {
            $endDateTime = $fields['UF_DATE'] . ' ' . $fields['UF_END_TIME'] . ':00';
            $fields['UF_END_TIME'] = $endDateTime;
        }

        try {
            return $entity::update($slotId, $fields);
        } catch (\Exception $e) {
            error_log('Xillix Update Schedule Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Конвертировать время записи в часовой пояс пользователя
     */
    public static function convertRecordTimeToUserTimezone($recordDateTime, $recordTimezone, $userTimezone = null)
    {
        // Если не указан userTimezone, получаем текущего пользователя
        if ($userTimezone === null) {
            global $USER;
            $userTimezone = self::getTeacherTimezone($USER->GetID());
        }

        // Если пояса одинаковые - возвращаем как есть
        if ($recordTimezone === $userTimezone) {
            return $recordDateTime;
        }

        try {
            // Разбираем строку на компоненты
            $parts = explode(' ', $recordDateTime);
            if (count($parts) !== 2) {
                return $recordDateTime;
            }

            $datePart = $parts[0]; // d.m.Y
            $timePart = $parts[1]; // H:i:s

            // Разбираем дату
            $dateParts = explode('.', $datePart);
            if (count($dateParts) !== 3) {
                return $recordDateTime;
            }

            $day = $dateParts[0];
            $month = $dateParts[1];
            $year = $dateParts[2];

            // Разбираем время
            $timeParts = explode(':', $timePart);
            if (count($timeParts) !== 3) {
                return $recordDateTime;
            }

            $hour = $timeParts[0];
            $minute = $timeParts[1];
            $second = $timeParts[2];

            // Создаем DateTime объект явно с указанием часового пояса
            $recordTime = new \DateTime('now', new \DateTimeZone($recordTimezone));
            $recordTime->setDate($year, $month, $day);
            $recordTime->setTime($hour, $minute, $second);

            // Конвертируем в часовой пояс пользователя
            $recordTime->setTimezone(new \DateTimeZone($userTimezone));

            // Возвращаем в том же формате
            return $recordTime->format('d.m.Y H:i:s');

        } catch (\Exception $e) {
            error_log('Convert record time error: ' . $e->getMessage());
            return $recordDateTime;
        }
    }

    /**
     * Получить расписание с конвертацией времени в часовой пояс пользователя
     */
    public static function getTeacherScheduleWithTimezone($teacherId, $weekStart = null, $userTimezone = 'Europe/Moscow')
    {
        $schedule = self::getTeacherSchedule($teacherId, $weekStart);

        // Конвертируем время только при разных поясах
        foreach ($schedule as &$slot) {
            $recordTimezone = $slot['UF_TIMEZONE'] ?? 'Europe/Moscow';

            // Конвертируем только если пояса разные
            if ($recordTimezone !== $userTimezone) {
                if (!empty($slot['UF_START_TIME'])) {
                    $slot['UF_START_TIME'] = self::convertRecordTimeToUserTimezone(
                        $slot['UF_START_TIME'],
                        $recordTimezone,
                        $userTimezone // добавляем третий параметр
                    );
                }
                if (!empty($slot['UF_END_TIME'])) {
                    $slot['UF_END_TIME'] = self::convertRecordTimeToUserTimezone(
                        $slot['UF_END_TIME'],
                        $recordTimezone,
                        $userTimezone // добавляем третий параметр
                    );
                }
            }
        }

        return $schedule;
    }

    /**
     * Получить расписание для записи с конвертацией только при разных часовых поясах
     */
    public static function getBookingScheduleWithUserTimezone($teacherId, $weekStart = null, $userTimezone = 'Europe/Moscow', $currentStudentId = null)
    {
        // Получаем расписание как обычно
        $schedule = self::getBookingSchedule($teacherId, $weekStart, $userTimezone, $currentStudentId);

        // Получаем часовой пояс преподавателя для слотов из шаблона
        $teacherTimezone = self::getTeacherTimezone($teacherId);

        // Конвертируем время только если пояса отличаются
        foreach ($schedule as &$slot) {
            if (!empty($slot['date']) && !empty($slot['hour'])) {
                try {
                    // Определяем пояс записи:
                    // - для шаблонных слотов используем пояс преподавателя
                    // - для занятых слотов получаем из базы данных
                    $recordTimezone = $teacherTimezone; // по умолчанию пояс преподавателя

                    if ($slot['is_booked'] && !empty($slot['id']) && strpos($slot['id'], 'template_') !== 0) {
                        // Для занятых слотов получаем пояс из базы
                        $entity = self::getEntity();
                        if ($entity) {
                            $dbSlot = $entity::getById($slot['id'])->fetch();
                            if ($dbSlot && !empty($dbSlot['UF_TIMEZONE'])) {
                                $recordTimezone = $dbSlot['UF_TIMEZONE'];
                            }
                        }
                    }

                    // Конвертируем только если пояса разные
                    if ($recordTimezone !== $userTimezone) {
                        // Создаем DateTime в поясе записи
                        $recordTime = new \DateTime(
                            $slot['date'] . ' ' . $slot['hour'] . ':00:00',
                            new \DateTimeZone($recordTimezone)
                        );

                        // Конвертируем в часовой пояс пользователя
                        $recordTime->setTimezone(new \DateTimeZone($userTimezone));

                        // Обновляем данные для отображения
                        $slot['date'] = $recordTime->format('Y-m-d');
                        $slot['hour'] = (int)$recordTime->format('H');
                        $slot['display_time'] = $recordTime->format('H:i');
                        $slot['timezone_converted'] = true;
                        $slot['original_timezone'] = $recordTimezone;
                        $slot['target_timezone'] = $userTimezone;
                    } else {
                        // Пояса одинаковые - не конвертируем
                        $slot['timezone_converted'] = false;
                        $slot['original_timezone'] = $recordTimezone;
                        $slot['target_timezone'] = $userTimezone;
                    }

                } catch (\Exception $e) {
                    error_log('User timezone conversion error: ' . $e->getMessage());
                    $slot['timezone_converted'] = false;
                    $slot['original_timezone'] = $teacherTimezone;
                    $slot['target_timezone'] = $userTimezone;
                }
            }
        }

        return $schedule;
    }

    /**
     * Получить объединенное расписание для записи с конвертацией времени
     */
    public static function getBookingScheduleWithTimezone($teacherId, $weekStart = null, $teacherTimezone = 'Europe/Moscow', $studentTimezone = 'Europe/Moscow')
    {
        $schedule = self::getBookingSchedule($teacherId, $weekStart, $teacherTimezone);

        // Конвертируем время для студента
        foreach ($schedule as &$slot) {
            if (!empty($slot['date']) && !empty($slot['hour'])) {
                try {
                    // Создаем DateTime в часовом поясе преподавателя
                    $teacherTime = new \DateTime(
                        $slot['date'] . ' ' . $slot['hour'] . ':00:00',
                        new \DateTimeZone($teacherTimezone)
                    );

                    // Конвертируем в часовой пояс студента
                    $teacherTime->setTimezone(new \DateTimeZone($studentTimezone));

                    // Обновляем данные слота
                    $slot['date'] = $teacherTime->format('Y-m-d');
                    $slot['hour'] = (int)$teacherTime->format('H');
                    $slot['display_time'] = $teacherTime->format('H:i');

                    $slot['timezone_converted'] = true;
                    $slot['original_date'] = $slot['date'];
                    $slot['original_hour'] = $slot['hour'];

                } catch (\Exception $e) {
                    error_log('Booking schedule timezone conversion error: ' . $e->getMessage());
                    $slot['timezone_converted'] = false;
                }
            }
        }

        return $schedule;
    }

    public static function deleteScheduleSlot($slotId)
    {
        $entity = self::getEntity();
        if (!$entity) {
            return false;
        }

        try {
            return $entity::delete($slotId);
        } catch (\Exception $e) {
            error_log('Xillix Delete Schedule Error: ' . $e->getMessage());
            return false;
        }
    }

    private static function convertToTimezone($datetime, $fromTimezone, $toTimezone)
    {
        // Если пояса одинаковые - возвращаем как есть
        if ($fromTimezone === $toTimezone) {
            return $datetime;
        }

        try {
            // Входной формат из БД: 'd.m.Y H:i:s'
            $date = \DateTime::createFromFormat('d.m.Y H:i:s', $datetime, new \DateTimeZone($fromTimezone));

            if ($date === false) {
                // Пробуем другие форматы как fallback
                $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'd.m.Y H:i'];
                foreach ($formats as $format) {
                    $date = \DateTime::createFromFormat($format, $datetime, new \DateTimeZone($fromTimezone));
                    if ($date !== false) break;
                }

                // Если все еще не получилось
                if ($date === false) {
                    $date = new \DateTime($datetime, new \DateTimeZone($fromTimezone));
                }
            }

            // Конвертируем в целевой часовой пояс
            $date->setTimezone(new \DateTimeZone($toTimezone));

            // Возвращаем в формате d.m.Y H:i:s для совместимости с Bitrix
            return $date->format('d.m.Y H:i:s');

        } catch (\Exception $e) {
            error_log('Xillix Timezone Convert Error: ' . $e->getMessage() . ' for datetime: ' . $datetime . ', from: ' . $fromTimezone . ', to: ' . $toTimezone);
            return $datetime;
        }
    }

    private static function convertFromTimezone($datetime, $sourceTimezone)
    {
        try {
            // Определяем формат входных данных
            // Входной формат может быть: 'Y-m-d H:i:s', 'Y-m-d H:i', 'H:i', или просто время

            // Если передано только время (HH:MM)
            if (preg_match('/^\d{1,2}:\d{2}$/', $datetime)) {
                // Создаем полную дату с текущим днем
                $today = (new \DateTime('now', new \DateTimeZone($sourceTimezone)))->format('Y-m-d');
                $fullDatetime = $today . ' ' . $datetime . ':00';
                $date = new \DateTime($fullDatetime, new \DateTimeZone($sourceTimezone));
            } // Если передана полная дата и время
            else {
                // Пробуем разные форматы datetime
                $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'd.m.Y H:i:s', 'd.m.Y H:i'];
                $date = null;

                foreach ($formats as $format) {
                    $date = \DateTime::createFromFormat($format, $datetime, new \DateTimeZone($sourceTimezone));
                    if ($date !== false) {
                        break;
                    }
                }

                // Если ни один формат не подошел, пробуем стандартный парсер
                if (!$date) {
                    $date = new \DateTime($datetime, new \DateTimeZone($sourceTimezone));
                }
            }

            // Конвертируем в UTC
//            $date->setTimezone(new \DateTimeZone('UTC'));

            // Возвращаем в формате d.m.Y H:i:s для хранения в БД
            return $date->format('d.m.Y H:i:s');

        } catch (\Exception $e) {
            error_log('Xillix Timezone Convert FROM Error: ' . $e->getMessage() . ' for datetime: ' . $datetime . ', timezone: ' . $sourceTimezone);
            return $datetime;
        }
    }

    private static function isValidDate($date, $format = 'd.m.Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public static function getHLTemplateBlockId()
    {
        if (!\Bitrix\Main\Loader::includeModule('highloadblock')) {
            return false;
        }

        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => self::HL_BLOCK_TEMPLATE_NAME]
        ])->fetch();

        return $hlblock ? $hlblock['ID'] : false;
    }

    public static function getTemplateEntity()
    {
        $hlblockId = self::getHLTemplateBlockId();
        if (!$hlblockId) {
            return false;
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblockId);
        return $entity->getDataClass();
    }

    /**
     * Получить шаблон расписания учителя
     */
    public static function getTeacherScheduleTemplate($teacherId, $dayOnly = false, $timezone = 'Europe/Moscow')
    {
        $entity = self::getTemplateEntity();
        if (!$entity) {
            return [];
        }

        $filter = [
            '=UF_TEACHER_ID' => (int)$teacherId,
            '=UF_IS_ACTIVE' => true
        ];

        try {
            $template = $entity::getList([
                'filter' => $filter,
                'order' => ['UF_DAY_OF_WEEK' => 'ASC', 'UF_START_TIME' => 'ASC']
            ])->fetchAll();

            // Форматируем время
            $formattedTemplate = [];
            foreach ($template as $slot) {
                $slot['UF_START_TIME'] = substr($slot['UF_START_TIME'], 0, 5); // HH:MM
                $slot['UF_END_TIME'] = substr($slot['UF_END_TIME'], 0, 5); // HH:MM

                // Если dayOnly = false, возвращаем ВСЕ слоты (0-24 часов)
                if (!$dayOnly) {
                    $formattedTemplate[] = $slot;
                } else {
                    // Режим "только день" - показываем только слоты с 8:00 до 22:00
                    $startHour = (int)substr($slot['UF_START_TIME'], 0, 2);
                    if ($startHour >= 8 && $startHour < 22) {
                        $formattedTemplate[] = $slot;
                    }
                }
            }

            return $formattedTemplate;
        } catch (\Exception $e) {
            error_log('Xillix Schedule Template Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Сохранить шаблон расписания учителя
     */
    public static function saveTeacherScheduleTemplate($teacherId, $slots)
    {
        $entity = self::getTemplateEntity();
        if (!$entity) {
            return false;
        }

        // Сначала деактивируем старые записи
        $existing = $entity::getList([
            'filter' => ['=UF_TEACHER_ID' => (int)$teacherId]
        ])->fetchAll();

        foreach ($existing as $item) {
            $entity::update($item['ID'], ['UF_IS_ACTIVE' => false]);
        }

        // Сохраняем новые слоты
        foreach ($slots as $slot) {
            $entity::add([
                'UF_TEACHER_ID' => (int)$teacherId,
                'UF_DAY_OF_WEEK' => (int)$slot['day'],
                'UF_START_TIME' => $slot['startTime'] . ':00',
                'UF_END_TIME' => $slot['endTime'] . ':00',
                'UF_IS_ACTIVE' => true
            ]);
        }

        return true;
    }

    /**
     * Удалить шаблон расписания учителя
     */
    public static function clearTeacherScheduleTemplate($teacherId)
    {
        $entity = self::getTemplateEntity();
        if (!$entity) {
            return false;
        }

        $existing = $entity::getList([
            'filter' => ['=UF_TEACHER_ID' => (int)$teacherId]
        ])->fetchAll();

        foreach ($existing as $item) {
            $entity::update($item['ID'], ['UF_IS_ACTIVE' => false]);
        }

        return true;
    }

    /**
     * Забронировать слот расписания
     */
    public static function bookScheduleSlot($slotId, $studentId, $timezone = 'Europe/Moscow')
    {
        $entity = self::getEntity();
        if (!$entity) {
            return false;
        }

        try {
            // Обновляем слот - устанавливаем студента и статус "забронирован"
            $result = $entity::update($slotId, [
                'UF_STUDENT_ID' => (int)$studentId,
                'UF_STATUS' => self::getStatusIdByXmlId('blocked')
            ]);

            return $result->isSuccess();
        } catch (\Exception $e) {
            error_log('Xillix Book Schedule Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить ID Highload-блока для связей преподаватель-ученик
     */
    public static function getHLTeacherStudentBlockId()
    {
        if (!\Bitrix\Main\Loader::includeModule('highloadblock')) {
            return false;
        }

        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => self::HL_BLOCK_TEACHER_STUDENT_NAME]
        ])->fetch();

        return $hlblock ? $hlblock['ID'] : false;
    }

    /**
     * Получить сущность для связей преподаватель-ученик
     */
    public static function getTeacherStudentEntity()
    {
        $hlblockId = self::getHLTeacherStudentBlockId();
        if (!$hlblockId) {
            return false;
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblockId);
        return $entity->getDataClass();
    }

    /**
     * Добавить связь преподаватель-ученик
     */
    public static function addTeacherStudentRelation($teacherId, $studentId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return false;
        }

        // Проверяем, нет ли уже активной связи
        $existing = $entity::getList([
            'filter' => [
                '=UF_TEACHER_ID' => (int)$teacherId,
                '=UF_STUDENT_ID' => (int)$studentId,
                '=UF_IS_ACTIVE' => true
            ]
        ])->fetch();

        if ($existing) {
            return true; // Связь уже существует
        }

        try {
            $result = $entity::add([
                'UF_TEACHER_ID' => (int)$teacherId,
                'UF_STUDENT_ID' => (int)$studentId,
                'UF_IS_ACTIVE' => true,
                'UF_CREATED_AT' => new DateTime()
            ]);

            return $result->isSuccess();
        } catch (\Exception $e) {
            error_log('Add teacher-student relation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Деактивировать связь преподаватель-ученик
     */
    public static function deactivateTeacherStudentRelation($teacherId, $studentId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return false;
        }

        try {
            $relation = $entity::getList([
                'filter' => [
                    '=UF_TEACHER_ID' => (int)$teacherId,
                    '=UF_STUDENT_ID' => (int)$studentId,
                    '=UF_IS_ACTIVE' => true
                ]
            ])->fetch();

            if ($relation) {
                $result = $entity::update($relation['ID'], [
                    'UF_IS_ACTIVE' => false
                ]);

                return $result->isSuccess();
            }

            return true; // Связь уже не активна
        } catch (\Exception $e) {
            error_log('Deactivate teacher-student relation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить активных учеников преподавателя
     */
    public static function getTeacherStudents($teacherId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return [];
        }

        try {
            $relations = $entity::getList([
                'filter' => [
                    '=UF_TEACHER_ID' => (int)$teacherId,
                    '=UF_IS_ACTIVE' => true
                ],
                'select' => ['UF_STUDENT_ID']
            ])->fetchAll();

            return array_map(function ($relation) {
                return $relation['UF_STUDENT_ID'];
            }, $relations);
        } catch (\Exception $e) {
            error_log('Get teacher students error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить активных преподавателей ученика
     */
    public static function getStudentTeachersActive($studentId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return [];
        }

        try {
            $relations = $entity::getList([
                'filter' => [
                    '=UF_STUDENT_ID' => (int)$studentId,
                    '=UF_IS_ACTIVE' => true
                ],
                'select' => ['UF_TEACHER_ID']
            ])->fetchAll();

            return array_map(function ($relation) {
                return $relation['UF_TEACHER_ID'];
            }, $relations);
        } catch (\Exception $e) {
            error_log('Get student teachers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Проверить активную связь преподаватель-ученик
     */
    public static function checkTeacherStudentRelation($teacherId, $studentId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return false;
        }

        try {
            $relation = $entity::getList([
                'filter' => [
                    '=UF_TEACHER_ID' => (int)$teacherId,
                    '=UF_STUDENT_ID' => (int)$studentId,
                    '=UF_IS_ACTIVE' => true
                ]
            ])->fetch();

            return (bool)$relation;
        } catch (\Exception $e) {
            error_log('Check teacher-student relation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить все связи преподавателя (включая неактивные)
     */
    public static function getAllTeacherRelations($teacherId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return [];
        }

        try {
            return $entity::getList([
                'filter' => [
                    '=UF_TEACHER_ID' => (int)$teacherId
                ],
                'order' => ['UF_CREATED_AT' => 'DESC']
            ])->fetchAll();
        } catch (\Exception $e) {
            error_log('Get all teacher relations error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить все связи ученика (включая неактивные)
     */
    public static function getAllStudentRelations($studentId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return [];
        }

        try {
            return $entity::getList([
                'filter' => [
                    '=UF_STUDENT_ID' => (int)$studentId
                ],
                'order' => ['UF_CREATED_AT' => 'DESC']
            ])->fetchAll();
        } catch (\Exception $e) {
            error_log('Get all student relations error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Обновить заметки о ученике
     */
    public static function updateStudentNotes($teacherId, $studentId, $notes)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return false;
        }

        try {
            // Ищем активную связь
            $relation = $entity::getList([
                'filter' => [
                    '=UF_TEACHER_ID' => (int)$teacherId,
                    '=UF_STUDENT_ID' => (int)$studentId,
                    '=UF_IS_ACTIVE' => true
                ]
            ])->fetch();

            if ($relation) {
                $result = $entity::update($relation['ID'], [
                    'UF_NOTES' => $notes
                ]);

                return $result->isSuccess();
            }

            return false; // Связь не найдена
        } catch (\Exception $e) {
            error_log('Update student notes error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить заметки о ученике
     */
    public static function getStudentNotes($teacherId, $studentId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return '';
        }

        try {
            $relation = $entity::getList([
                'filter' => [
                    '=UF_TEACHER_ID' => (int)$teacherId,
                    '=UF_STUDENT_ID' => (int)$studentId,
                    '=UF_IS_ACTIVE' => true
                ],
                'select' => ['UF_NOTES']
            ])->fetch();

            return $relation ? $relation['UF_NOTES'] : '';
        } catch (\Exception $e) {
            error_log('Get student notes error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Получить подробную информацию об учениках преподавателя
     */
    public static function getTeacherStudentsDetailed($teacherId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return [];
        }

        try {
            $relations = $entity::getList([
                'filter' => [
                    '=UF_TEACHER_ID' => (int)$teacherId,
                    '=UF_IS_ACTIVE' => true
                ],
                'order' => ['UF_CREATED_AT' => 'DESC']
            ])->fetchAll();

            // Дополняем информацией о пользователях
            $detailedStudents = [];
            foreach ($relations as $relation) {
                $user = \CUser::GetByID($relation['UF_STUDENT_ID'])->Fetch();
                if ($user) {
                    $userName = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
                    if (empty($userName)) {
                        $userName = $user['LOGIN'];
                    }

                    $detailedStudents[] = [
                        'ID' => $relation['ID'],
                        'STUDENT_ID' => $relation['UF_STUDENT_ID'],
                        'STUDENT_NAME' => $userName,
                        'NOTES' => $relation['UF_NOTES'],
                        'CREATED_AT' => $relation['UF_CREATED_AT'],
                        'IS_ACTIVE' => $relation['UF_IS_ACTIVE']
                    ];
                }
            }

            return $detailedStudents;
        } catch (\Exception $e) {
            error_log('Get teacher students detailed error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить подробную информацию об ученике преподавателя
     */
    public static function getTeacherStudentDetailed($teacherId, $studentId)
    {
        $entity = self::getTeacherStudentEntity();
        if (!$entity) {
            return [];
        }

        try {
            $relation = $entity::getList([
                'filter' => [
                    '=UF_TEACHER_ID' => (int)$teacherId,
                    '=UF_STUDENT_ID' => (int)$studentId,
                    '=UF_IS_ACTIVE' => true
                ],
                'order' => ['UF_CREATED_AT' => 'DESC']
            ])->fetch();

            // Дополняем информацией о пользователях
            $detailedStudent = [];
            $user = \CUser::GetByID($relation['UF_STUDENT_ID'])->Fetch();
            if ($user) {
                $userName = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
                if (empty($userName)) {
                    $userName = $user['LOGIN'];
                }

                $detailedStudent = [
                    'ID' => $relation['ID'],
                    'STUDENT_ID' => $relation['UF_STUDENT_ID'],
                    'STUDENT_NAME' => $userName,
                    'NOTES' => $relation['UF_NOTES'],
                    'CREATED_AT' => $relation['UF_CREATED_AT'],
                    'IS_ACTIVE' => $relation['UF_IS_ACTIVE']
                ];
            }

            return $detailedStudent;
        } catch (\Exception $e) {
            error_log('Get teacher students detailed error: ' . $e->getMessage());
            return [];
        }
    }
}