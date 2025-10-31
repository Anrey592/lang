<?php

namespace Xillix\Telegram;

use Bitrix\Main\Security\Random;
use Bitrix\Main\UserTable;

class UserManager
{
    public function registerUserFromTelegram($chatId, $phone, $name, $lastName = "")
    {
        global $USER;

        // Нормализуем телефон
        $cleanPhone = preg_replace('/\D/', '', $phone);

        // Проверяем, не зарегистрирован ли уже пользователь с этим chat_id
        $existingUserByChat = UserTable::getList([
            'filter' => ['=UF_TELEGRAM_CHAT_ID' => $chatId],
            'select' => ['ID', 'ACTIVE', 'NAME', 'LAST_NAME', 'PERSONAL_PHONE']
        ])->fetch();

        if ($existingUserByChat) {
            // Пользователь с этим chat_id уже существует
            return [
                'success' => false,
                'error' => 'already_registered',
                'user_data' => [
                    'name' => $existingUserByChat['NAME'],
                    'last_name' => $existingUserByChat['LAST_NAME'],
                    'phone' => $existingUserByChat['PERSONAL_PHONE']
                ]
            ];
        }

        // Проверяем, не занят ли телефон другим пользователем
        $phoneUser = UserTable::getList([
            'filter' => [
                '=PERSONAL_PHONE' => $cleanPhone,
                '=ACTIVE' => 'Y'
            ],
            'select' => ['ID', 'UF_TELEGRAM_CHAT_ID', 'NAME', 'LAST_NAME']
        ])->fetch();

        if ($phoneUser) {
            // Телефон занят другим пользователем
            if ($phoneUser['UF_TELEGRAM_CHAT_ID'] && $phoneUser['UF_TELEGRAM_CHAT_ID'] != $chatId) {
                // Телефон привязан к другому Telegram аккаунту
                return [
                    'success' => false,
                    'error' => 'phone_taken_by_other',
                    'user_data' => [
                        'name' => $phoneUser['NAME'],
                        'last_name' => $phoneUser['LAST_NAME']
                    ]
                ];
            } elseif (!$phoneUser['UF_TELEGRAM_CHAT_ID']) {
                // Телефон занят, но не привязан к Telegram
                return [
                    'success' => false,
                    'error' => 'phone_exists_not_linked'
                ];
            }
        }

        // Проверяем, не занят ли логин
        $loginUser = UserTable::getList([
            'filter' => [
                '=LOGIN' => $cleanPhone,
                '=ACTIVE' => 'Y'
            ],
            'select' => ['ID', 'UF_TELEGRAM_CHAT_ID']
        ])->fetch();

        if ($loginUser) {
            if ($loginUser['UF_TELEGRAM_CHAT_ID'] && $loginUser['UF_TELEGRAM_CHAT_ID'] != $chatId) {
                return [
                    'success' => false,
                    'error' => 'login_taken_by_other'
                ];
            }
        }

        $email = $cleanPhone . '@multilang.ru';
        $password = Random::getString(8, true);
        $login = $cleanPhone;

        $user = new \CUser;
        $fields = array(
            "NAME" => $name,
            "LAST_NAME" => $lastName,
            "EMAIL" => $email,
            "LOGIN" => $login,
            "LID" => "s1",
            "ACTIVE" => "Y",
            "GROUP_ID" => array(6), // Группа students
            "PASSWORD" => $password,
            "CONFIRM_PASSWORD" => $password,
            "PERSONAL_PHONE" => $cleanPhone,
            "UF_TELEGRAM_CHAT_ID" => $chatId
        );

        $userId = $user->Add($fields);

        if ($userId > 0) {
            $USER->Authorize($userId);
            return [
                'success' => true,
                'user_id' => $userId,
                'phone' => $cleanPhone,
                'password' => $password,
                'site_url' => 'https://' . $_SERVER['HTTP_HOST']
            ];
        } else {
            return [
                'success' => false,
                'error' => 'registration_failed',
                'error_message' => $user->LAST_ERROR
            ];
        }
    }

    public function linkUserToTelegram($phone, $chatId)
    {
        // Находим пользователя по телефону
        $user = UserTable::getList([
            'filter' => [
                '=PERSONAL_PHONE' => $phone,
                '=ACTIVE' => 'Y'
            ],
            'select' => ['ID', 'UF_TELEGRAM_CHAT_ID', 'NAME', 'LAST_NAME']
        ])->fetch();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'user_not_found'
            ];
        }

        // Проверяем, не привязан ли уже этот chat_id к другому пользователю
        $existingChatUser = UserTable::getList([
            'filter' => ['=UF_TELEGRAM_CHAT_ID' => $chatId],
            'select' => ['ID']
        ])->fetch();

        if ($existingChatUser) {
            return [
                'success' => false,
                'error' => 'chat_already_linked'
            ];
        }

        // Проверяем, не привязан ли пользователь уже к другому Telegram
        if ($user['UF_TELEGRAM_CHAT_ID'] && $user['UF_TELEGRAM_CHAT_ID'] != $chatId) {
            return [
                'success' => false,
                'error' => 'user_already_linked'
            ];
        }

        // Привязываем пользователя к Telegram
        $cUser = new \CUser;
        $result = $cUser->Update($user['ID'], [
            'UF_TELEGRAM_CHAT_ID' => $chatId
        ]);

        if ($result) {
            return [
                'success' => true,
                'user_id' => $user['ID'],
                'name' => $user['NAME'],
                'last_name' => $user['LAST_NAME'],
                'phone' => $phone
            ];
        } else {
            return [
                'success' => false,
                'error' => 'link_failed',
                'error_message' => $cUser->LAST_ERROR
            ];
        }
    }

    public function reactivateUser($userId, $phone, $name, $lastName = "")
    {
        $newPassword = Random::getString(8, true);

        $user = new \CUser;
        $fields = array(
            "NAME" => $name,
            "LAST_NAME" => $lastName,
            "ACTIVE" => "Y",
            "PERSONAL_PHONE" => $phone,
            "PASSWORD" => $newPassword,
            "CONFIRM_PASSWORD" => $newPassword
        );

        $result = $user->Update($userId, $fields);

        if ($result) {
            return [
                'success' => true,
                'user_id' => $userId,
                'phone' => $phone,
                'password' => $newPassword,
                'site_url' => 'https://' . $_SERVER['HTTP_HOST'],
                'reactivated' => true
            ];
        } else {
            return [
                'success' => false,
                'error' => $user->LAST_ERROR
            ];
        }
    }

    public function resetPassword($chatId)
    {
        $user = UserTable::getList([
            'filter' => ['=UF_TELEGRAM_CHAT_ID' => $chatId],
            'select' => ['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'ACTIVE']
        ])->fetch();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'Пользователь не найден'
            ];
        }

        // Если пользователь деактивирован, реактивируем его
        if ($user['ACTIVE'] == 'N') {
            $cUser = new \CUser;
            $cUser->Update($user['ID'], ['ACTIVE' => 'Y']);
        }

        $newPassword = Random::getString(8, true);
        $cUser = new \CUser;
        $result = $cUser->Update($user['ID'], [
            'PASSWORD' => $newPassword,
            'CONFIRM_PASSWORD' => $newPassword
        ]);

        if ($result) {
            return [
                'success' => true,
                'new_password' => $newPassword,
                'user_name' => trim($user['NAME'] . ' ' . $user['LAST_NAME']),
                'reactivated' => ($user['ACTIVE'] == 'N')
            ];
        } else {
            return [
                'success' => false,
                'error' => $cUser->LAST_ERROR
            ];
        }
    }

    public function getUserByChatId($chatId)
    {
        return UserTable::getList([
            'filter' => ['=UF_TELEGRAM_CHAT_ID' => $chatId],
            'select' => ['*']
        ])->fetch();
    }
}