<?php
namespace Xillix;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TimezoneHelper
{
    public static function getUserTimezone()
    {
        // Пытаемся получить из cookie
        if (isset($_COOKIE['user_timezone'])) {
            $timezone = $_COOKIE['user_timezone'];
            if (in_array($timezone, \DateTimeZone::listIdentifiers())) {
                return $timezone;
            }
        }

        // Определяем по IP (упрощенная логика)
        return self::getTimezoneByIP();
    }

    private static function getTimezoneByIP()
    {
        // В реальном проекте использовать сервис типа ipapi.co
        // Для примера возвращаем московское время
        return 'Europe/Moscow';
    }

    public static function getTimezoneOptions()
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $options = [];

        foreach ($timezones as $timezone) {
            $options[$timezone] = self::getTimezoneWithOffset($timezone);
        }

        return $options;
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
}
