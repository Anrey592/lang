<?php

namespace Xillix;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

class ReminderAgent
{
    /**
     * Агент для отправки напоминаний о занятиях
     */
    public static function sendLessonReminders()
    {
        if (!Loader::includeModule('xillix')) {
            return "Xillix\\ReminderAgent::sendLessonReminders();";
        }

        try {
            $entity = TeacherScheduleManager::getEntity();
            if (!$entity) {
                return "Xillix\\ReminderAgent::sendLessonReminders();";
            }

            // Текущее время
            $now = new DateTime();

            // Время через 24 часа
            $reminderTime = clone $now;
            $reminderTime->add('+24 hours');

            // Форматируем дату для фильтра
            $reminderDateTime = $reminderTime->format('d.m.Y H:i:s');
            $reminderDate = $reminderTime->format('d.m.Y');

            error_log("Reminder agent started. Looking for lessons on: " . $reminderDateTime);

            // Ищем занятия, которые начнутся через 24 часа и напоминание еще не отправлено
            $lessons = $entity::getList([
                'filter' => [
                    '=UF_DATE' => $reminderDate,
                    '<=UF_START_TIME' => $reminderDateTime,
                    '=UF_STATUS' => TeacherScheduleManager::getStatusIdByXmlId('blocked'),
                    '=UF_REMINDER_SENT' => false,
                    '!=UF_STUDENT_ID' => false // только забронированные занятия
                ],
                'select' => [
                    'ID',
                    'UF_TEACHER_ID',
                    'UF_STUDENT_ID',
                    'UF_DATE',
                    'UF_START_TIME',
                    'UF_END_TIME',
                    'UF_TIMEZONE',
                    'UF_REMINDER_SENT'
                ]
            ])->fetchAll();

            $sentCount = 0;
            $errorCount = 0;

            foreach ($lessons as $lesson) {
                try {
                    $success = self::sendReminderForLesson($lesson);

                    if ($success) {
                        // Помечаем напоминание как отправленное
                        self::markReminderAsSent($lesson['ID']);
                        $sentCount++;
                        error_log("Reminder sent for lesson ID: " . $lesson['ID'] . " (in " . round($timeDiff, 2) . " hours)");
                    } else {
                        $errorCount++;
                        error_log("Failed to send reminder for lesson ID: " . $lesson['ID']);
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    error_log("Error processing lesson ID: " . $lesson['ID'] . " - " . $e->getMessage());
                }
            }

            error_log("Reminder agent finished. Sent: {$sentCount}, Errors: {$errorCount}");

        } catch (\Exception $e) {
            error_log("Reminder agent error: " . $e->getMessage());
        }

        return "Xillix\\ReminderAgent::sendLessonReminders();";
    }


    /**
     * Отправить напоминание для занятия
     */
    private static function sendReminderForLesson($lesson)
    {
        if (!Loader::includeModule('xillix.telegram')) {
            return false;
        }

        try {
            // Извлекаем время из UF_START_TIME (формат: d.m.Y H:i:s)
            $timeParts = explode(' ', $lesson['UF_START_TIME']);
            $timeOnly = count($timeParts) > 1 ? $timeParts[1] : $lesson['UF_START_TIME'];

            // Отправляем напоминание через NotificationManager
            $result = NotificationManager::sendLessonReminder(
                $lesson['ID'],
                $lesson['UF_TEACHER_ID'],
                $lesson['UF_STUDENT_ID'],
                $lesson['UF_DATE'],
                $timeOnly,
                24 // за 24 часа
            );

            return !empty($result['student']) || !empty($result['teacher']);

        } catch (\Exception $e) {
            error_log("Send reminder for lesson error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Пометить напоминание как отправленное
     */
    private static function markReminderAsSent($lessonId)
    {
        $entity = TeacherScheduleManager::getEntity();
        if (!$entity) {
            return false;
        }

        try {
            $result = $entity::update($lessonId, [
                'UF_REMINDER_SENT' => true
            ]);

            return $result->isSuccess();
        } catch (\Exception $e) {
            error_log("Mark reminder as sent error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Сбросить флаг отправленных напоминаний (для тестирования)
     */
    public static function resetReminderFlags()
    {
        $entity = TeacherScheduleManager::getEntity();
        if (!$entity) {
            return false;
        }

        try {
            // Находим все занятия с отправленными напоминаниями
            $lessons = $entity::getList([
                'filter' => ['=UF_REMINDER_SENT' => true],
                'select' => ['ID']
            ])->fetchAll();

            $resetCount = 0;
            foreach ($lessons as $lesson) {
                $entity::update($lesson['ID'], ['UF_REMINDER_SENT' => false]);
                $resetCount++;
            }

            error_log("Reset reminder flags for {$resetCount} lessons");
            return $resetCount;

        } catch (\Exception $e) {
            error_log("Reset reminder flags error: " . $e->getMessage());
            return false;
        }
    }
}