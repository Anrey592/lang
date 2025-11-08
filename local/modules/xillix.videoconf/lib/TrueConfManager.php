<?php

namespace Xillix\Videoconf;

use Bitrix\Main\Config\Option;
use Bitrix\Main\SystemException;

class TrueConfManager
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $defaultOwner;
    protected $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(Option::get('xillix.videoconf', 'server_domain', ''), '/');
        $this->clientId = Option::get('xillix.videoconf', 'client_id', '');
        $this->clientSecret = Option::get('xillix.videoconf', 'client_secret', '');
        $this->defaultOwner = Option::get('xillix.videoconf', 'default_owner', 'tcadmin');

        if (empty($this->baseUrl) || empty($this->clientId) || empty($this->clientSecret)) {
            throw new SystemException('TrueConf: не заданы настройки модуля (домен, client_id, client_secret)');
        }
    }

    protected function getAccessToken()
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $url = $this->baseUrl . '/oauth2/v1/token';
        $postData = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new SystemException("TrueConf: ошибка получения токена (HTTP $httpCode): " . $response);
        }

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            throw new SystemException('TrueConf: access_token не получен');
        }

        $this->accessToken = $data['access_token'];
        return $this->accessToken;
    }

    protected function makeRequest(string $method, string $path, ?array $data = null)
    {
        $url = $this->baseUrl . $path . '?access_token=' . $this->getAccessToken();
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($data !== null) {
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new SystemException("TrueConf API error ($httpCode): " . $response);
        }

        return json_decode($response, true);
    }

    /**
     * Создаёт локального пользователя в TrueConf Server
     *
     * @param string $login
     * @param string $password
     * @param string $name
     * @return array
     * @throws SystemException
     */
    public function createUser(string $login, string $password, string $name = ''): array
    {
        return $this->makeRequest('POST', '/api/v3.8/users', [
            'id' => $login,
            'password' => $password,
            'name' => $name ?: $login,
            'type' => 0, // 0 = local user
            'email' => ''
        ]);
    }

    /**
     * Создаёт конференцию с автоматической записью
     *
     * @param string $topic
     * @param string|null $owner
     * @param int $maxParticipants
     * @return array
     * @throws SystemException
     */
    public function createConference(
        string  $topic,
        ?string $owner = null,
        int     $maxParticipants = 20
    ): array
    {
        $owner = $owner ?: $this->defaultOwner;

        return $this->makeRequest('POST', '/api/v3.8/conferences', [
            'topic' => 'Конференция с сайта',
            'type' => 0,
            'recording' => 1,
            'auto_invite' => 1,
            'max_participants' => $maxParticipants,
            'schedule' => ['type' => -1],
            'owner' => $owner,
            'allow_guests' => true,
            "invitations" => [
                [
                    "id" => $owner,
                    "display_name" => null
                ],
            ],
        ]);
    }

    /**
     * Возвращает список всех конференций
     *
     * @return array
     * @throws SystemException
     */
    public function getConferences(): array
    {
        return $this->makeRequest('GET', '/api/v3.8/conferences');
    }

    /**
     * Получает информацию о конкретной конференции
     *
     * @param string $conferenceId
     * @return array
     * @throws SystemException
     */
    public function getConference(string $conferenceId): array
    {
        return $this->makeRequest('GET', '/api/v3.8/conferences/' . urlencode($conferenceId));
    }

    /**
     * Запускает конференцию по её ID
     *
     * @param string $conferenceId
     * @return array
     * @throws SystemException
     */
    public function runConference(string $conferenceId): array
    {
        return $this->makeRequest('POST', '/api/v3.8/conferences/' . urlencode($conferenceId) . '/run');
    }

    /**
     * Приглашает участника в запущенную конференцию
     *
     * @param string $conferenceId
     * @param string $userId — логин пользователя в TrueConf Server
     * @return array
     * @throws SystemException
     */
    public function inviteParticipant(string $conferenceId, string $userId): array
    {
        return $this->makeRequest('POST', '/api/v3.8/conferences/' . urlencode($conferenceId) . '/participants', [
            'id' => $userId
        ]);
    }

    /**
     * Создаёт пользователя в TrueConf Server на основе данных Bitrix
     *
     * @param array $bitrixFields — поля из $user->Add()
     * @return array|null — ответ TrueConf или null, если не удалось
     * @throws SystemException
     */
    public function createTrueConfUser(array $bitrixFields): ?array
    {
        // Получаем номер телефона
        $phone = trim($bitrixFields['PERSONAL_PHONE'] ?? '');
        if (!$phone) {
            // Нет телефона — не создаём пользователя в TrueConf
            return null;
        }

        // Очищаем телефон от всех символов, оставляем только цифры
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Приводим к формату 7XXXXXXXXXX
        if (strlen($phone) === 11 && strpos($phone, '8') === 0) {
            $phone = '7' . substr($phone, 1);
        } elseif (strlen($phone) === 10) {
            $phone = '7' . $phone;
        }

        if (strlen($phone) !== 11 || strpos($phone, '7') !== 0) {
            throw new SystemException('Некорректный номер телефона для TrueConf: ' . $phone);
        }

        // Генерация пароля
        $password = substr(bin2hex(random_bytes(10)), 0, 12); // 12-символьный пароль

        // Имя и фамилия
        $firstName = trim($bitrixFields['NAME'] ?? '');
        $lastName = trim($bitrixFields['LAST_NAME'] ?? '');
        $displayName = trim(($firstName . ' ' . $lastName));
        if (empty($displayName)) {
            $displayName = $phone;
        }

        // Получаем Server ID из настроек
        $serverId = Option::get('xillix.videoconf', 'server_id', 'ru4skl');
        $email = $phone . '@' . $serverId . '.trueconf.name';

        // Создаём пользователя
        $response = $this->makeRequest('POST', '/api/v3.8/users', [
            'id' => $phone,
            'login_name' => $phone,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'password' => $password,
            'name' => $displayName,
            'email' => $email,
            'type' => 0
        ]);

        // Добавляем пароль в ответ для сохранения в Bitrix
        $response['user']['password'] = $password;
        return $response;
    }
}