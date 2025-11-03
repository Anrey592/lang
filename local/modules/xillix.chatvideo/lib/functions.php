<?php

namespace Xillix\ChatVideo;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Module
{
    const MODULE_ID = 'xillix.chatvideo';

    public static function getModulePath()
    {
        return __DIR__ . '/..';
    }

    public static function includeModule()
    {
        if (!Loader::includeModule(self::MODULE_ID)) {
            throw new \Exception(Loc::getMessage('XILLIX_CHATVIDEO_MODULE_NOT_INSTALLED'));
        }
        return true;
    }

    /**
     * Очистка неактивных участников по cron
     */
    public static function cleanupInactiveParticipants()
    {
        if (!Loader::includeModule(self::MODULE_ID)) {
            return false;
        }

        try {
            $count = \Xillix\ChatVideo\HighloadBlock\ParticipantManager::cleanupInactiveParticipants();
            return $count;
        } catch (\Exception $e) {
            // Логируем ошибку
            AddMessage2Log(
                'XillixChatVideo cleanup error: ' . $e->getMessage(),
                self::MODULE_ID
            );
            return false;
        }
    }

    /**
     * Получение настроек модуля
     */
    public static function getOption($name, $default = '')
    {
        return \Bitrix\Main\Config\Option::get(self::MODULE_ID, $name, $default);
    }

    /**
     * Установка настроек модуля
     */
    public static function setOption($name, $value)
    {
        \Bitrix\Main\Config\Option::set(self::MODULE_ID, $name, $value);
    }
}

// Функции для глобального использования
if (!function_exists('xillix_chatvideo_include_module')) {
    function xillix_chatvideo_include_module()
    {
        return \Xillix\ChatVideo\Module::includeModule();
    }
}

if (!function_exists('xillix_chatvideo_get_option')) {
    function xillix_chatvideo_get_option($name, $default = '')
    {
        return \Xillix\ChatVideo\Module::getOption($name, $default);
    }
}