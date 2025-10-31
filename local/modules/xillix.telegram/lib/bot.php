<?php

namespace Xillix\Telegram;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Xillix\Telegram\StateTable;
use Xillix\Telegram\TempTable;

class Bot
{
    const MODULE_ID = 'xillix.telegram';
    private $token;
    private $apiUrl;

    public function __construct()
    {
        $this->token = Option::get(self::MODULE_ID, 'TELEGRAM_BOT_TOKEN');
        $this->apiUrl = 'https://api.telegram.org/bot' . $this->token . '/';
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        Option::set(self::MODULE_ID, 'TELEGRAM_BOT_TOKEN', $token);
        $this->token = $token;
        $this->apiUrl = 'https://api.telegram.org/bot' . $this->token . '/';
    }

    public function sendMessage($chatId, $text, $keyboard = null)
    {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Token not set'];
        }

        $http = new HttpClient();
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        $response = $http->post($this->apiUrl . 'sendMessage', $data);
        return json_decode($response, true);
    }

    public function setWebhook($url)
    {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Token not set'];
        }

        $http = new HttpClient();
        $response = $http->post($this->apiUrl . 'setWebhook', ['url' => $url]);
        return json_decode($response, true);
    }

    public function deleteWebhook()
    {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Token not set'];
        }

        $http = new HttpClient();
        $response = $http->post($this->apiUrl . 'deleteWebhook');
        return json_decode($response, true);
    }

    public function processUpdate($update)
    {
        if (isset($update['callback_query'])) {
            $callback = $update['callback_query'];
            $chatId = $callback['message']['chat']['id'];
            $data = $callback['data'];

            switch ($data) {
                case 'reset_password_after_check':
                    $this->handlePasswordReset($chatId);
                    $this->answerCallbackQuery($callback['id']);
                    break;
            }
            return;
        }

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $entities = $message['entities'] ?? [];

            $isCommand = false;
            foreach ($entities as $entity) {
                if ($entity['type'] === 'bot_command') {
                    $isCommand = true;
                    break;
                }
            }

            if ($isCommand) {
                $command = trim($text);
                $baseCommand = explode(' ', $command)[0];

                switch ($baseCommand) {
                    case '/start':
                    case '/start@' . $this->getBotUsername():
                        $this->handleStartCommand($chatId, $text);
                        $this->clearUserState($chatId);
                        break;

                    case '/register':
                    case '/register@' . $this->getBotUsername():
                        $this->handleRegistration($chatId);
                        break;

                    case '/resetpassword':
                    case '/resetpassword@' . $this->getBotUsername():
                        $this->handlePasswordReset($chatId);
                        break;

                    case '/schedule':
                    case '/schedule@' . $this->getBotUsername():
                        $this->handleScheduleCommand($chatId);
                        break;

                    default:
                        $this->sendMessage($chatId, "❌ Неизвестная команда. Используйте /start для просмотра меню.");
                        break;
                }
                return;
            }

            $state = $this->getUserState($chatId);

            if ($text === 'Регистрация') {
                $this->handleRegistration($chatId);
            } elseif ($text === 'Забыли пароль') {
                $this->handlePasswordReset($chatId);
            } else {
                if ($state === 'awaiting_phone') {
                    $this->handlePhoneInput($chatId, $text);
                } elseif ($state === 'awaiting_name') {
                    $this->handleNameInput($chatId, $text);
                } elseif ($state === 'awaiting_last_name') {
                    $this->handleLastNameInput($chatId, $text);
                } else {
                    $this->showMainMenu($chatId);
                }
            }
        }
    }

    private function handleStartCommand($chatId, $text)
    {
        if (preg_match('/\/start backUrl_(.+)/', $text, $matches)) {
            $encodedUrl = trim($matches[1]);
            $returnUrl = $this->decodeReturnUrl($encodedUrl);
            $this->saveReturnUrl($chatId, $returnUrl);
        }

        $message = "👋 <b>Добро пожаловать в MultiLang School!</b>\n\n";
        $message .= "Для доступа к расписанию уроков необходимо зарегистрироваться. В меню ниже выберите пункт 'Регистрация'\n\n";
        $message .= "Доступные команды:\n";
        $message .= "📝 /register - Регистрация в системе\n";
        $message .= "📅 /schedule - Мое расписание\n";
        $message .= "🔐 /resetpassword - Сброс пароля\n\n";
        $message .= "Выберите команду из меню или введите ее вручную.";

        $this->sendMessage($chatId, $message);
    }

    private function decodeReturnUrl($encodedUrl)
    {
        return str_replace(
            ['__', '_Q_', '_E_', '_A_', '_H_', '_D_', '_C_', '_P_', '_PL_', '_M_'],
            ['/', '?', '=', '&', '#', '.', ':', '%', '+', '-'],
            $encodedUrl
        );
    }

    public function answerCallbackQuery($callbackQueryId, $text = null, $showAlert = false)
    {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Token not set'];
        }

        $http = new HttpClient();
        $data = [
            'callback_query_id' => $callbackQueryId
        ];

        if ($text !== null) {
            $data['text'] = $text;
        }

        if ($showAlert) {
            $data['show_alert'] = $showAlert;
        }

        $response = $http->post($this->apiUrl . 'answerCallbackQuery', $data);
        return json_decode($response, true);
    }

    public function getBotUsername()
    {
        if (!$this->token) {
            return null;
        }

        $http = new HttpClient();
        $response = $http->get($this->apiUrl . 'getMe');
        $result = json_decode($response, true);

        if ($result['ok']) {
            return $result['result']['username'];
        }

        return null;
    }

    public function setMyCommands()
    {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Token not set'];
        }

        $http = new HttpClient();

        $commands = [
            [
                'command' => 'start',
                'description' => 'Запустить бота и показать меню'
            ],
            [
                'command' => 'register',
                'description' => 'Регистрация'
            ],
            [
                'command' => 'schedule',
                'description' => 'Мое расписание'
            ],
            [
                'command' => 'resetpassword',
                'description' => 'Сбросить пароль'
            ]
        ];

        $data = [
            'commands' => json_encode($commands)
        ];

        $response = $http->post($this->apiUrl . 'setMyCommands', $data);
        return json_decode($response, true);
    }

    public function deleteMyCommands()
    {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Token not set'];
        }

        $http = new HttpClient();
        $response = $http->post($this->apiUrl . 'deleteMyCommands');
        return json_decode($response, true);
    }

    private function saveReturnUrl($chatId, $returnUrl)
    {
        $this->setTempData($chatId, 'return_url', $returnUrl);
    }

    private function getReturnUrl($chatId)
    {
        return $this->getTempData($chatId, 'return_url');
    }

    private function clearReturnUrl($chatId)
    {
        $this->setTempData($chatId, 'return_url', '');
    }

    private function showMainMenu($chatId)
    {
        $text = "👋 <b>Добро пожаловать в MultiLang School!</b>\n\n";
        $text .= "Выберите действие из меню ниже:";

        return $this->sendMessage($chatId, $text);
    }

    private function handleRegistration($chatId)
    {
        // Сначала проверяем, не зарегистрирован ли уже пользователь с этим chat_id
        $userManager = new UserManager();
        $existingUser = $userManager->getUserByChatId($chatId);

        if ($existingUser) {
            $userName = trim(($existingUser['NAME'] ?? '') . ' ' . ($existingUser['LAST_NAME'] ?? ''));
            $userPhone = $existingUser['PERSONAL_PHONE'] ?? '';

            $text = "ℹ️ <b>Вы уже зарегистрированы!</b>\n\n";
            if ($userName) {
                $text .= "Пользователь: <b>{$userName}</b>\n";
            }
            if ($userPhone) {
                $text .= "Телефон: <code>{$userPhone}</code>\n\n";
            }
            $text .= "Хотите восстановить пароль?";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🔐 Восстановить пароль', 'callback_data' => 'reset_password_after_check']
                    ]
                ]
            ];

            $this->sendMessage($chatId, $text, $keyboard);
            return;
        }

        $domain = $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_HOST'];
        $text = "📝 <b>Регистрация</b>\n\n";
        $text .= "Продолжая регистрацию вы подтверждаете, что ознакомились\n";
        $text .= "<a href='$domain/politika-obrabotki-personalnykh-dannykh/'>политикой обработки персональных данных</a>\n";
        $text .= "<a href='$domain/soglasie-na-obrabotku-personalnykh-dannykh/'>даете согласие на обработку персональных данных</a>\n\n";
        $text .= "Для регистрации введите ваш номер телефона, имя и фамилию\n\n";
        $text .= "Шаг 1 из 3\n";
        $text .= "Введите ваш номер телефона в формате:\n";
        $text .= "<code>79991234567</code> (11 цифр, начинается с 7)\n\n";
        $text .= "Пример: <code>79991234567</code>";

        $this->sendMessage($chatId, $text);
        $this->setUserState($chatId, 'awaiting_phone');
    }

    private function handlePasswordReset($chatId)
    {
        $userManager = new UserManager();
        $result = $userManager->resetPassword($chatId);

        if ($result['success']) {
            $text = "🔐 <b>Пароль сброшен</b>\n\n";
            if (!empty($result['user_name'])) {
                $text .= "Пользователь: " . $result['user_name'] . "\n";
            }
            $text .= "Ваш новый пароль: <code>" . $result['new_password'] . "</code>\n\n";
            $text .= "⚠️ Используйте его для входа на сайт";
        } else {
            $text = "❌ <b>Ошибка:</b>\n" . $result['error'];
        }

        return $this->sendMessage($chatId, $text);
    }

    private function setUserState($chatId, $state)
    {
        try {
            $result = StateTable::getList([
                'filter' => ['CHAT_ID' => $chatId]
            ])->fetch();

            if ($result) {
                StateTable::update($chatId, ['STATE' => $state]);
            } else {
                StateTable::add([
                    'CHAT_ID' => $chatId,
                    'STATE' => $state
                ]);
            }
        } catch (\Exception $e) {
            // silent
        }
    }

    private function getUserState($chatId)
    {
        try {
            $result = StateTable::getList([
                'filter' => ['CHAT_ID' => $chatId],
                'select' => ['STATE']
            ])->fetch();

            if ($result && isset($result['STATE'])) {
                return $result['STATE'];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function clearUserState($chatId)
    {
        try {
            StateTable::delete($chatId);
        } catch (\Exception $e) {
            // silent
        }
    }

    public function handlePhoneInput($chatId, $phone)
    {
        $cleanPhone = preg_replace('/\D/', '', $phone);

        if (strlen($cleanPhone) === 11 && $cleanPhone[0] === '8') {
            $cleanPhone = '7' . substr($cleanPhone, 1);
        }

        if (strlen($cleanPhone) !== 11 || !preg_match('/^7\d{10}$/', $cleanPhone)) {
            $text = "❌ <b>Неверный формат телефона</b>\n\n";
            $text .= "Введите 11 цифр, начинающихся с 7 или 8:\n";
            $text .= "Пример: <code>79991234567</code> или <code>89991234567</code>";
            $this->sendMessage($chatId, $text);
            return;
        }

        $this->setTempData($chatId, 'phone', $cleanPhone);

        $text = "📝 <b>Регистрация</b>\n\n";
        $text .= "Шаг 2 из 3\n";
        $text .= "Введите ваше имя:";

        $this->sendMessage($chatId, $text);
        $this->setUserState($chatId, 'awaiting_name');
    }

    private function handleNameInput($chatId, $name)
    {
        if (empty(trim($name))) {
            $text = "❌ <b>Имя не может быть пустым</b>\n\n";
            $text .= "Пожалуйста, введите ваше имя:";
            return $this->sendMessage($chatId, $text);
        }

        $this->setTempData($chatId, 'name', trim($name));

        $text = "📝 <b>Регистрация</b>\n\n";
        $text .= "Шаг 3 из 3\n";
        $text .= "Введите вашу фамилию:";

        $this->sendMessage($chatId, $text);
        $this->setUserState($chatId, 'awaiting_last_name');
    }

    private function handleLastNameInput($chatId, $lastName)
    {
        if (empty(trim($lastName))) {
            $text = "❌ <b>Фамилия не может быть пустой</b>\n\n";
            $text .= "Пожалуйста, введите вашу фамилию:";
            return $this->sendMessage($chatId, $text);
        }

        $phone = $this->getTempData($chatId, 'phone');
        $name = $this->getTempData($chatId, 'name');
        $returnUrl = $this->getReturnUrl($chatId);

        $userManager = new UserManager();
        $result = $userManager->registerUserFromTelegram($chatId, $phone, trim($name), trim($lastName));

        if ($result['success']) {
            $text = "🎉 <b>Регистрация успешна!</b>\n\n";
            $text .= "✅ Ваш аккаунт создан\n\n";

            if ($returnUrl) {
                $siteUrl = $result['site_url'];
                $fullReturnUrl = $siteUrl . $returnUrl;
                $text .= "🔗 <a href=\"" . $fullReturnUrl . "\">Вернуться на сайт</a>\n\n";
            } else {
                $text .= "🔗 <a href=\"" . $result['site_url'] . "\">Перейти на сайт</a>\n\n";
            }

            $text .= "📝 <b>Ваши данные для входа:</b>\n";
            $text .= "Имя: " . trim($name) . "\n";
            $text .= "Фамилия: " . trim($lastName) . "\n";
            $text .= "Телефон: <code>" . $result['phone'] . "</code>\n";
            $text .= "Пароль: <code>" . $result['password'] . "</code>\n\n";
            $text .= "⚠️ Сохраните эти данные!";

            $this->clearUserState($chatId);
            $this->clearTempData($chatId);
            $this->clearReturnUrl($chatId);
            $this->sendMessage($chatId, $text);
        } else {
            switch ($result['error']) {
                case 'already_registered':
                    $userName = trim(($result['user_data']['name'] ?? '') . ' ' . ($result['user_data']['last_name'] ?? ''));
                    $userPhone = $result['user_data']['phone'] ?? '';

                    $text = "ℹ️ <b>Вы уже зарегистрированы!</b>\n\n";
                    if ($userName) {
                        $text .= "Пользователь: <b>{$userName}</b>\n";
                    }
                    if ($userPhone) {
                        $text .= "Телефон: <code>{$userPhone}</code>\n\n";
                    }
                    $text .= "Хотите восстановить пароль?";

                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🔐 Восстановить пароль', 'callback_data' => 'reset_password_after_check']
                            ]
                        ]
                    ];
                    $this->sendMessage($chatId, $text, $keyboard);
                    break;

                case 'phone_taken_by_other':
                    $otherUserName = trim(($result['user_data']['name'] ?? '') . ' ' . ($result['user_data']['last_name'] ?? ''));
                    $text = "❌ <b>Номер телефона уже занят</b>\n\n";
                    $text .= "Этот номер телефона привязан к другому аккаунту Telegram";
                    if ($otherUserName) {
                        $text .= ":\n<b>{$otherUserName}</b>";
                    }
                    $text .= "\n\nИспользуйте другой номер телефона или обратитесь в поддержку.";
                    $this->sendMessage($chatId, $text);
                    break;

                case 'phone_exists_not_linked':
                    $text = "❌ <b>Номер телефона уже зарегистрирован</b>\n\n";
                    $text .= "Этот номер телефона уже используется на сайте, но не привязан к Telegram.\n\n";
                    $text .= "Обратитесь в поддержку для привязки аккаунта.";
                    $this->sendMessage($chatId, $text);
                    break;

                case 'login_taken_by_other':
                    $text = "❌ <b>Ошибка регистрации</b>\n\n";
                    $text .= "Логин уже занят другим пользователем.\n";
                    $text .= "Используйте другой номер телефона.";
                    $this->sendMessage($chatId, $text);
                    break;

                default:
                    $errorMessage = $result['error_message'] ?? $result['error'];
                    $text = "❌ <b>Ошибка регистрации:</b>\n" . $errorMessage;
                    $this->sendMessage($chatId, $text);
                    break;
            }

            $this->clearUserState($chatId);
            $this->clearTempData($chatId);
            $this->clearReturnUrl($chatId);
        }

        $this->showMainMenu($chatId);
    }

    private function setTempData($chatId, $key, $value)
    {
        try {
            $result = TempTable::getList([
                'filter' => [
                    'CHAT_ID' => $chatId,
                    'DATA_KEY' => $key
                ]
            ])->fetch();

            if ($result) {
                TempTable::update(['CHAT_ID' => $chatId, 'DATA_KEY' => $key], ['DATA_VALUE' => $value]);
            } else {
                TempTable::add([
                    'CHAT_ID' => $chatId,
                    'DATA_KEY' => $key,
                    'DATA_VALUE' => $value
                ]);
            }
        } catch (\Exception $e) {
            // silent
        }
    }

    private function getTempData($chatId, $key)
    {
        try {
            $result = TempTable::getList([
                'filter' => [
                    'CHAT_ID' => $chatId,
                    'DATA_KEY' => $key
                ],
                'select' => ['DATA_VALUE']
            ])->fetch();

            return $result ? $result['DATA_VALUE'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function clearTempData($chatId)
    {
        try {
            TempTable::deleteByChatId($chatId);
        } catch (\Exception $e) {
            // silent
        }
    }

    public function getWebhookInfo()
    {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Token not set'];
        }

        $http = new HttpClient();
        $response = $http->get($this->apiUrl . 'getWebhookInfo');
        return json_decode($response, true);
    }

    /**
     * Обработка команды /schedule - показ расписания пользователя
     */
    private function handleScheduleCommand($chatId)
    {
        // Ищем пользователя по chat_id
        $userManager = new UserManager();
        $user = $userManager->getUserByChatId($chatId);

        if (!$user) {
            $this->sendMessage($chatId,
                "❌ Вы не зарегистрированы в системе.\n\n" .
                "Для просмотра расписания необходимо зарегистрироваться.\n" .
                "Используйте команду /register для регистрации."
            );
            return;
        }

        // Получаем расписание пользователя
        $schedule = $this->getUserSchedule($user['ID']);

        if (empty($schedule)) {
            $this->sendMessage($chatId,
                "📅 У вас пока нет запланированных занятий.\n\n" .
                "Запишитесь на урок через сайт или обратитесь к преподавателю."
            );
            return;
        }

        // Формируем сообщение с расписанием
        $message = $this->formatScheduleMessage($schedule, $user);
        $this->sendMessage($chatId, $message);
    }

    /**
     * Получить расписание пользователя
     */
    private function getUserSchedule($userId)
    {
        if (!\Bitrix\Main\Loader::includeModule('xillix')) {
            return [];
        }

        try {
            // Получаем ближайшие занятия (на 2 недели вперед)
            $startDate = new \DateTime();
            $endDate = new \DateTime();
            $endDate->add(new \DateInterval('P14D')); // +14 дней

            $entity = \Xillix\TeacherScheduleManager::getEntity();
            if (!$entity) {
                return [];
            }

            // Ищем занятия где пользователь является либо учеником, либо преподавателем
            $schedule = $entity::getList([
                'filter' => [
                    'LOGIC' => 'OR',
                    [
                        '=UF_STUDENT_ID' => (int)$userId,
                        '=UF_STATUS' => \Xillix\TeacherScheduleManager::getStatusIdByXmlId('blocked')
                    ],
                    [
                        '=UF_TEACHER_ID' => (int)$userId,
                        '=UF_STATUS' => \Xillix\TeacherScheduleManager::getStatusIdByXmlId('blocked')
                    ]
                ],
                'select' => [
                    'ID',
                    'UF_TEACHER_ID',
                    'UF_STUDENT_ID',
                    'UF_DATE',
                    'UF_START_TIME',
                    'UF_END_TIME',
                    'UF_SUBJECT',
                    'UF_TIMEZONE'
                ],
                'order' => ['UF_DATE' => 'ASC', 'UF_START_TIME' => 'ASC']
            ])->fetchAll();

            // Конвертируем статусы
            $schedule = \Xillix\TeacherScheduleManager::convertStatusToXmlId($schedule);

            // Фильтруем по дате
            $filteredSchedule = [];
            foreach ($schedule as $lesson) {
                // Правильно получаем дату из объекта Bitrix\Main\Type\Date
                $lessonDate = $lesson['UF_DATE'] instanceof \Bitrix\Main\Type\Date
                    ? $lesson['UF_DATE']->format('Y-m-d')
                    : $lesson['UF_DATE'];

                $lessonDateObj = \DateTime::createFromFormat('Y-m-d', $lessonDate);
                if ($lessonDateObj && $lessonDateObj >= $startDate && $lessonDateObj <= $endDate) {
                    $filteredSchedule[] = $lesson;
                }
            }

            return $filteredSchedule;

        } catch (\Exception $e) {
            error_log('Get user schedule error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Форматировать сообщение с расписанием
     */
    private function formatScheduleMessage($schedule, $user)
    {
        $domain = 'https://' . $_SERVER['HTTP_HOST'];
        $personalUrl = $domain . '/personal';

        $message = "📅 <b>Ваше расписание на 2 недели:</b>\n\n";

        $groupedSchedule = [];

        // Группируем занятия по датам
        foreach ($schedule as $lesson) {
            // Правильно получаем дату из объекта Bitrix\Main\Type\Date
            $dateKey = $lesson['UF_DATE'] instanceof \Bitrix\Main\Type\Date
                ? $lesson['UF_DATE']->format('d.m.Y')
                : $lesson['UF_DATE'];

            if (!isset($groupedSchedule[$dateKey])) {
                $groupedSchedule[$dateKey] = [];
            }
            $groupedSchedule[$dateKey][] = $lesson;
        }

        // Сортируем даты
        uksort($groupedSchedule, function ($a, $b) {
            $dateA = \DateTime::createFromFormat('d.m.Y', $a);
            $dateB = \DateTime::createFromFormat('d.m.Y', $b);
            return $dateA <=> $dateB;
        });

        $lessonCount = 0;
        $maxLessons = 10; // Ограничиваем количество выводимых занятий

        foreach ($groupedSchedule as $date => $lessons) {
            if ($lessonCount >= $maxLessons) {
                break;
            }

            $formattedDate = $this->formatDisplayDate($date);
            $message .= "📌 <b>{$formattedDate}</b>\n";

            foreach ($lessons as $lesson) {
                if ($lessonCount >= $maxLessons) {
                    break;
                }

                // Извлекаем время из UF_START_TIME и UF_END_TIME
                $startTime = $this->extractTimeFromDateTime($lesson['UF_START_TIME']);
                $endTime = $this->extractTimeFromDateTime($lesson['UF_END_TIME']);

                // Определяем роль пользователя и второго участника
                if ($user['ID'] == $lesson['UF_TEACHER_ID']) {
                    $role = "👨‍🏫 Преподаватель";
                    $counterpartId = $lesson['UF_STUDENT_ID'];
                    $counterpartRole = "ученик";
                } else {
                    $role = "👨‍🎓 Ученик";
                    $counterpartId = $lesson['UF_TEACHER_ID'];
                    $counterpartRole = "преподаватель";
                }

                // Получаем имя второго участника
                $counterpartName = $this->getUserName($counterpartId);

                $message .= "   ⏰ {$startTime} - {$endTime}\n";
                $message .= "   👤 {$counterpartRole}: {$counterpartName}\n";
                $message .= "   ─────────────────────\n";

                $lessonCount++;
            }
        }

        if ($lessonCount === 0) {
            $message = "📅 У вас нет запланированных занятий на ближайшие 2 недели.";
        } else {
            if (count($schedule) > $maxLessons) {
                $message .= "\n📋 <i>Показаны ближайшие {$maxLessons} занятий</i>\n";
            }

            $message .= "\n🔍 <b>Подробное расписание в <a href=\"{$personalUrl}\">личном кабинете</a></b>";
        }

        return $message;
    }

    /**
     * Извлечь время из datetime строки
     */
    private function extractTimeFromDateTime($datetime)
    {
        try {
            // Если это объект DateTime
            if ($datetime instanceof \Bitrix\Main\Type\DateTime || $datetime instanceof \DateTime) {
                return $datetime->format('H:i');
            }

            // Если это строка - пробуем разные форматы
            $formats = ['d.m.Y H:i:s', 'Y-m-d H:i:s', 'd.m.Y H:i', 'Y-m-d H:i', 'H:i:s', 'H:i'];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $datetime);
                if ($date !== false) {
                    return $date->format('H:i');
                }
            }

            // Если не удалось распарсить, возвращаем как есть
            return $datetime;
        } catch (\Exception $e) {
            return $datetime;
        }
    }

    /**
     * Получить имя пользователя по ID
     */
    private function getUserName($userId)
    {
        try {
            $user = \Bitrix\Main\UserTable::getList([
                'filter' => ['=ID' => $userId],
                'select' => ['NAME', 'LAST_NAME', 'LOGIN']
            ])->fetch();

            if ($user) {
                $name = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
                return !empty($name) ? $name : $user['LOGIN'];
            }

            return "Пользователь #{$userId}";
        } catch (\Exception $e) {
            return "Пользователь #{$userId}";
        }
    }

    /**
     * Форматировать дату для отображения
     */
    private function formatDisplayDate($dateString)
    {
        try {
            // Пробуем разные форматы даты
            $formats = ['d.m.Y', 'Y-m-d'];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date !== false) {
                    $today = new \DateTime();
                    $today->setTime(0, 0, 0);

                    $tomorrow = clone $today;
                    $tomorrow->add(new \DateInterval('P1D'));

                    if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
                        return "Сегодня (" . $date->format('d.m.Y') . ")";
                    } elseif ($date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                        return "Завтра (" . $date->format('d.m.Y') . ")";
                    } else {
                        $dayOfWeek = $this->getDayOfWeek($date->format('N'));
                        return $dayOfWeek . " (" . $date->format('d.m.Y') . ")";
                    }
                }
            }

            return $dateString;
        } catch (\Exception $e) {
            return $dateString;
        }
    }

    /**
     * Получить день недели на русском
     */
    private function getDayOfWeek($dayNumber)
    {
        $days = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье'
        ];

        return $days[$dayNumber] ?? '';
    }
}