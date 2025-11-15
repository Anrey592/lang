<?php

namespace Xillix;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

class NotificationManager
{
    /**
     * Отправить уведомление о записи на урок
     */
    public static function sendLessonBookingNotification($lessonId, $teacherId, $studentId, $lessonDate, $lessonTime)
    {
        if (!Loader::includeModule('xillix.telegram')) {
            return false;
        }

        // Получаем данные пользователей с UF_TELEGRAM_CHAT_ID и UF_TIMEZONE
        $teacher = UserTable::getList([
            'filter' => ['=ID' => $teacherId],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN', 'UF_TELEGRAM_CHAT_ID', 'UF_TIMEZONE']
        ])->fetch();

        $student = UserTable::getList([
            'filter' => ['=ID' => $studentId],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN', 'UF_TELEGRAM_CHAT_ID', 'UF_TIMEZONE']
        ])->fetch();

        if (!$teacher || !$student) {
            return false;
        }

        // Получаем часовой пояс записи из расписания
        $recordTimezone = self::getLessonTimezone($lessonId);

        // Конвертируем дату и время для каждого пользователя
        $teacherDateTime = self::convertDateTimeToUserTimezone($lessonDate, $lessonTime, $recordTimezone, $teacher['UF_TIMEZONE']);
        $studentDateTime = self::convertDateTimeToUserTimezone($lessonDate, $lessonTime, $recordTimezone, $student['UF_TIMEZONE']);

        // Текст уведомления для ученика
        $studentMessage = "🎓 Вы записались на урок!\n\n";
        $studentMessage .= "👨‍🏫 Преподаватель: " . self::getUserFullName($teacher) . "\n";
        $studentMessage .= "📅 Дата: " . $studentDateTime['date'] . "\n";
        $studentMessage .= "⏰ Время: " . $studentDateTime['time'] . "\n";

        // Добавляем информацию о часовом поясе, если он отличается
//        if ($studentDateTime['timezone_converted']) {
//            $studentMessage .= "🌍 Ваш часовой пояс: " . self::getTimezoneDisplayName($student['UF_TIMEZONE']) . "\n";
//        }

        $studentMessage .= "\n📍 Не забудьте подготовиться к уроку!";

        // Текст уведомления для преподавателя
        $teacherMessage = "🎓 Новая запись на урок!\n\n";
        $teacherMessage .= "👤 Ученик: " . self::getUserFullName($student) . "\n";
        $teacherMessage .= "📅 Дата: " . $teacherDateTime['date'] . "\n";
        $teacherMessage .= "⏰ Время: " . $teacherDateTime['time'] . "\n";

        // Добавляем информацию о часовом поясе, если он отличается
        if ($teacherDateTime['timezone_converted']) {
            $teacherMessage .= "🌍 Ваш часовой пояс: " . self::getTimezoneDisplayName($teacher['UF_TIMEZONE']) . "\n";
        }

        // Отправляем уведомления
        $bot = new \Xillix\Telegram\Bot();
        $results = [];

        // Уведомление ученику
        if (!empty($student['UF_TELEGRAM_CHAT_ID'])) {
            $results['student'] = $bot->sendMessage($student['UF_TELEGRAM_CHAT_ID'], $studentMessage);
        } else {
            error_log("Student {$studentId} doesn't have UF_TELEGRAM_CHAT_ID");
        }

        // Уведомление преподавателю
        if (!empty($teacher['UF_TELEGRAM_CHAT_ID'])) {
            $results['teacher'] = $bot->sendMessage($teacher['UF_TELEGRAM_CHAT_ID'], $teacherMessage);
        } else {
            error_log("Teacher {$teacherId} doesn't have UF_TELEGRAM_CHAT_ID");
        }

        return $results;
    }

    /**
     * Получить часовой пояс записи из расписания
     */
    private static function getLessonTimezone($lessonId)
    {
        if (!Loader::includeModule('xillix')) {
            return 'Europe/Moscow';
        }

        $entity = \Xillix\TeacherScheduleManager::getEntity();
        if (!$entity) {
            return 'Europe/Moscow';
        }

        try {
            $lesson = $entity::getById($lessonId)->fetch();
            if ($lesson && !empty($lesson['UF_TIMEZONE'])) {
                return $lesson['UF_TIMEZONE'];
            }
        } catch (\Exception $e) {
            error_log('Get lesson timezone error: ' . $e->getMessage());
        }

        return 'Europe/Moscow';
    }

    /**
     * Конвертировать дату и время в часовой пояс пользователя
     */
    private static function convertDateTimeToUserTimezone($date, $time, $fromTimezone, $toTimezone)
    {
        // Если пояса одинаковые или не указаны, возвращаем как есть
        if (empty($fromTimezone) || empty($toTimezone) || $fromTimezone === $toTimezone) {
            return [
                'date' => self::formatDateForNotification($date),
                'time' => self::formatTimeForNotification($time),
                'timezone_converted' => false
            ];
        }

        try {
            // Создаем полную строку datetime
            $datetimeString = self::createDateTimeString($date, $time);

            if (!$datetimeString) {
                return [
                    'date' => self::formatDateForNotification($date),
                    'time' => self::formatTimeForNotification($time),
                    'timezone_converted' => false
                ];
            }

            // Создаем DateTime объект в исходном часовом поясе
            $dateTime = new \DateTime($datetimeString, new \DateTimeZone($fromTimezone));

            // Конвертируем в целевой часовой пояс
            $dateTime->setTimezone(new \DateTimeZone($toTimezone));

            return [
                'date' => $dateTime->format('d.m.Y'),
                'time' => $dateTime->format('H:i'),
                'timezone_converted' => true
            ];

        } catch (\Exception $e) {
            error_log('DateTime conversion error: ' . $e->getMessage());
            return [
                'date' => self::formatDateForNotification($date),
                'time' => self::formatTimeForNotification($time),
                'timezone_converted' => false
            ];
        }
    }

    /**
     * Создать строку datetime из даты и времени
     */
    private static function createDateTimeString($date, $time)
    {
        // Пробуем разные форматы даты
        $dateFormats = ['d.m.Y', 'Y-m-d', 'Y-m-d H:i:s'];
        $timeFormats = ['H:i:s', 'H:i', 'd.m.Y H:i:s'];

        $datePart = null;
        $timePart = null;

        // Определяем дату
        foreach ($dateFormats as $format) {
            $dateObj = \DateTime::createFromFormat($format, $date);
            if ($dateObj !== false) {
                $datePart = $dateObj->format('Y-m-d');
                break;
            }
        }

        // Определяем время
        foreach ($timeFormats as $format) {
            $timeObj = \DateTime::createFromFormat($format, $time);
            if ($timeObj !== false) {
                $timePart = $timeObj->format('H:i:s');
                break;
            }
        }

        // Если не удалось определить дату или время
        if (!$datePart || !$timePart) {
            // Пробуем стандартный парсер
            try {
                $fullDateTime = $date . ' ' . $time;
                $testObj = new \DateTime($fullDateTime);
                return $testObj->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }

        return $datePart . ' ' . $timePart;
    }

    /**
     * Получить отображаемое название часового пояса
     */
    private static function getTimezoneDisplayName($timezone)
    {
        try {
            $date = new \DateTime('now', new \DateTimeZone($timezone));
            $offset = $date->getOffset();
            $hours = floor(abs($offset) / 3600);
            $minutes = (abs($offset) % 3600) / 60;
            $sign = $offset >= 0 ? '+' : '-';

            return "(UTC{$sign}" . sprintf("%02d:%02d", $hours, $minutes) . ")";
        } catch (\Exception $e) {
            return $timezone;
        }
    }

    /**
     * Отправить уведомление об отмене урока
     */
    public static function sendLessonCancellationNotification($lessonId, $teacherId, $studentId, $lessonDate, $lessonTime, $cancelledBy)
    {
        if (!Loader::includeModule('xillix.telegram')) {
            return false;
        }

        $teacher = UserTable::getList([
            'filter' => ['=ID' => $teacherId],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN', 'UF_TELEGRAM_CHAT_ID', 'UF_TIMEZONE']
        ])->fetch();

        $student = UserTable::getList([
            'filter' => ['=ID' => $studentId],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN', 'UF_TELEGRAM_CHAT_ID', 'UF_TIMEZONE']
        ])->fetch();

        if (!$teacher || !$student) {
            return false;
        }

        // Получаем часовой пояс записи из расписания
        $recordTimezone = self::getLessonTimezone($lessonId);

        // Конвертируем дату и время для каждого пользователя
        $teacherDateTime = self::convertDateTimeToUserTimezone($lessonDate, $lessonTime, $recordTimezone, $teacher['UF_TIMEZONE']);
        $studentDateTime = self::convertDateTimeToUserTimezone($lessonDate, $lessonTime, $recordTimezone, $student['UF_TIMEZONE']);

        $cancelledByName = ($cancelledBy == 'teacher') ?
            self::getUserFullName($teacher) :
            self::getUserFullName($student);

        // Сообщение для ученика
        $studentMessage = "❌ Урок отменен\n\n";
        $studentMessage .= "📅 Дата: " . $studentDateTime['date'] . "\n";
        $studentMessage .= "⏰ Время: " . $studentDateTime['time'] . "\n";
        $studentMessage .= "🚫 Отменил: " . $cancelledByName . "\n\n";
        $studentMessage .= "Вы можете записаться на другое время.";

        // Сообщение для преподавателя
        $teacherMessage = "❌ Урок отменен\n\n";
        $teacherMessage .= "📅 Дата: " . $teacherDateTime['date'] . "\n";
        $teacherMessage .= "⏰ Время: " . $teacherDateTime['time'] . "\n";
        $teacherMessage .= "🚫 Отменил: " . $cancelledByName . "\n\n";
        $teacherMessage .= "Урок удален из расписания.";

        $bot = new \Xillix\Telegram\Bot();
        $results = [];

        // Уведомление ученику
        if (!empty($student['UF_TELEGRAM_CHAT_ID'])) {
            $results['student'] = $bot->sendMessage($student['UF_TELEGRAM_CHAT_ID'], $studentMessage);
        }

        // Уведомление преподавателю
        if (!empty($teacher['UF_TELEGRAM_CHAT_ID'])) {
            $results['teacher'] = $bot->sendMessage($teacher['UF_TELEGRAM_CHAT_ID'], $teacherMessage);
        }

        return $results;
    }

    /**
     * Отправить напоминание о предстоящем уроке
     */
    public static function sendLessonReminder($lessonId, $teacherId, $studentId, $lessonDate, $lessonTime, $hoursBefore = 24)
    {
        if (!Loader::includeModule('xillix.telegram')) {
            return false;
        }

        $teacher = UserTable::getList([
            'filter' => ['=ID' => $teacherId],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN', 'UF_TELEGRAM_CHAT_ID', 'UF_TIMEZONE']
        ])->fetch();

        $student = UserTable::getList([
            'filter' => ['=ID' => $studentId],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN', 'UF_TELEGRAM_CHAT_ID', 'UF_TIMEZONE']
        ])->fetch();

        if (!$teacher || !$student) {
            return false;
        }

        // Получаем часовой пояс записи из расписания
        $recordTimezone = self::getLessonTimezone($lessonId);

        // Конвертируем дату и время для каждого пользователя
        $teacherDateTime = self::convertDateTimeToUserTimezone($lessonDate, $lessonTime, $recordTimezone, $teacher['UF_TIMEZONE']);
        $studentDateTime = self::convertDateTimeToUserTimezone($lessonDate, $lessonTime, $recordTimezone, $student['UF_TIMEZONE']);

        // Сообщение для ученика
        $studentMessage = "🔔 Напоминание о уроке\n\n";
        $studentMessage .= "👨‍🏫 Преподаватель: " . self::getUserFullName($teacher) . "\n";
        $studentMessage .= "📅 Дата: " . $studentDateTime['date'] . "\n";
        $studentMessage .= "⏰ Время: " . $studentDateTime['time'] . "\n";
//        $studentMessage .= "⏱ До начала: " . $hoursBefore . " часов\n\n";
        $studentMessage .= "🎒 Подготовьтесь к уроку!";

        // Сообщение для преподавателя
        $teacherMessage = "🔔 Напоминание о уроке\n\n";
        $teacherMessage .= "👤 Ученик: " . self::getUserFullName($student) . "\n";
        $teacherMessage .= "📅 Дата: " . $teacherDateTime['date'] . "\n";
        $teacherMessage .= "⏰ Время: " . $teacherDateTime['time'] . "\n";
//        $teacherMessage .= "⏱ До начала: " . $hoursBefore . " часов\n\n";
        $teacherMessage .= "✅ Будьте готовы к уроку!";

        $bot = new \Xillix\Telegram\Bot();
        $results = [];

        // Напоминание ученику
        if (!empty($student['UF_TELEGRAM_CHAT_ID'])) {
            $results['student'] = $bot->sendMessage($student['UF_TELEGRAM_CHAT_ID'], $studentMessage);
        }

        // Напоминание преподавателю
        if (!empty($teacher['UF_TELEGRAM_CHAT_ID'])) {
            $results['teacher'] = $bot->sendMessage($teacher['UF_TELEGRAM_CHAT_ID'], $teacherMessage);
        }

        return $results;
    }

    /**
     * Получить полное имя пользователя
     */
    private static function getUserFullName($user)
    {
        $name = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
        return !empty($name) ? $name : $user['LOGIN'];
    }

    /**
     * Форматировать дату для уведомления
     */
    private static function formatDateForNotification($date)
    {
        try {
            // Пробуем разные форматы даты
            $formats = ['d.m.Y', 'Y-m-d', 'Y-m-d H:i:s'];

            foreach ($formats as $format) {
                $dateObj = \DateTime::createFromFormat($format, $date);
                if ($dateObj !== false) {
                    return $dateObj->format('d.m.Y');
                }
            }

            // Если ни один формат не подошел, пробуем стандартный парсер
            $dateObj = new \DateTime($date);
            return $dateObj->format('d.m.Y');
        } catch (\Exception $e) {
            error_log('Date formatting error: ' . $e->getMessage());
            return $date;
        }
    }

    /**
     * Форматировать время для уведомления
     */
    private static function formatTimeForNotification($time)
    {
        try {
            // Пробуем разные форматы времени
            $formats = ['H:i:s', 'H:i', 'd.m.Y H:i:s'];

            foreach ($formats as $format) {
                $timeObj = \DateTime::createFromFormat($format, $time);
                if ($timeObj !== false) {
                    return $timeObj->format('H:i');
                }
            }

            // Если ни один формат не подошел, пробуем стандартный парсер
            $timeObj = new \DateTime($time);
            return $timeObj->format('H:i');
        } catch (\Exception $e) {
            error_log('Time formatting error: ' . $e->getMessage());
            return $time;
        }
    }

    /**
     * Отправить тестовое уведомление
     */
    public static function sendTestNotification($userId, $message = "Тестовое уведомление от системы")
    {
        if (!Loader::includeModule('xillix.telegram')) {
            return false;
        }

        $user = UserTable::getList([
            'filter' => ['=ID' => $userId],
            'select' => ['ID', 'UF_TELEGRAM_CHAT_ID']
        ])->fetch();

        if (!$user || empty($user['UF_TELEGRAM_CHAT_ID'])) {
            error_log("User {$userId} doesn't have UF_TELEGRAM_CHAT_ID");
            return false;
        }

        $bot = new \Xillix\Telegram\Bot();
        return $bot->sendMessage($user['UF_TELEGRAM_CHAT_ID'], $message);
    }

    /**
     * Проверить, есть ли у пользователя привязанный Telegram
     */
    public static function hasTelegram($userId)
    {
        $user = UserTable::getList([
            'filter' => ['=ID' => $userId],
            'select' => ['ID', 'UF_TELEGRAM_CHAT_ID']
        ])->fetch();

        return !empty($user['UF_TELEGRAM_CHAT_ID']);
    }
}