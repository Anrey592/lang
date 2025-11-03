<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

defined('B_PROLOG_INCLUDED') or die();

Loc::loadMessages(__FILE__);

$module_id = 'xillix.chatvideo';

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('XILLIX_CHATVIDEO_ACCESS_DENIED'));
}

// Обработка тестирования VoxImplant
if ($_REQUEST['test_voximplant'] && check_bitrix_sessid()) {
    $testAccountId = $_POST['voximplant_account_id'] ?? $voximplant_account_id;
    $testAppId = $_POST['voximplant_app_id'] ?? $voximplant_app_id;
    $testApiKey = $_POST['voximplant_api_key'] ?? $voximplant_api_key;

    $testResult = testVoximplantConnection($testAccountId, $testAppId, $testApiKey);

    if ($testResult['success']) {
        echo '<div style="color: green; margin: 10px 0; padding: 10px; background: #f0fff0; border: 1px solid #00ff00;">✅ ' . $testResult['message'] . '</div>';
    } else {
        echo '<div style="color: red; margin: 10px 0; padding: 10px; background: #fff0f0; border: 1px solid #ff0000;">❌ ' . $testResult['message'] . '</div>';
    }
}

// Функция тестирования подключения к VoxImplant
function testVoximplantConnection($accountId, $appId, $apiKey)
{
    if (empty($accountId) || empty($appId)) {
        return [
            'success' => false,
            'message' => 'Account ID и App ID обязательны для тестирования'
        ];
    }

    // Проверяем базовые требования
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'Требуется расширение cURL для тестирования API'
        ];
    }

    $apiUrl = "https://api.voximplant.com/platform_api/GetAccountInfo/";

    $postData = [
        'account_id' => $accountId,
        'api_key' => $apiKey
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'message' => 'Ошибка cURL: ' . $curlError
        ];
    }

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => "HTTP ошибка: {$httpCode}. Проверьте Account ID и API Key"
        ];
    }

    $data = json_decode($response, true);

    if (isset($data['error'])) {
        return [
            'success' => false,
            'message' => "Ошибка VoxImplant API: {$data['error']['msg']} (код: {$data['error']['code']})"
        ];
    }

    if (isset($data['result'])) {
        return [
            'success' => true,
            'message' => "Успешное подключение! Аккаунт: {$data['result']['account_name']}, ID: {$data['result']['account_id']}"
        ];
    }

    return [
        'success' => false,
        'message' => 'Неизвестный ответ от API VoxImplant'
    ];
}

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid()) {
    if (isset($_POST['apply']) || isset($_POST['save'])) {
        Option::set($module_id, 'voximplant_account_id', $_POST['voximplant_account_id'] ?? '');
        Option::set($module_id, 'voximplant_app_id', $_POST['voximplant_app_id'] ?? '');
        Option::set($module_id, 'voximplant_api_key', $_POST['voximplant_api_key'] ?? '');
        Option::set($module_id, 'max_participants_default', $_POST['max_participants_default'] ?? '10');
        Option::set($module_id, 'allow_guest_access', $_POST['allow_guest_access'] ?? 'N');
        Option::set($module_id, 'auto_create_room', $_POST['auto_create_room'] ?? 'Y');
        Option::set($module_id, 'default_room_duration', $_POST['default_room_duration'] ?? '60');
        Option::set($module_id, 'enable_recording', $_POST['enable_recording'] ?? 'N');
        Option::set($module_id, 'enable_screen_sharing', $_POST['enable_screen_sharing'] ?? 'Y');
        Option::set($module_id, 'enable_chat', $_POST['enable_chat'] ?? 'Y');

        if (isset($_POST['apply'])) {
            LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . urlencode(LANGUAGE_ID) . '&apply=Y');
        } else {
            LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . urlencode(LANGUAGE_ID) . '&save=Y');
        }
    }

    if (isset($_POST['default'])) {
        Option::delete($module_id);
    }
}

// Получаем текущие настройки
$voximplant_account_id = Option::get($module_id, 'voximplant_account_id', '');
$voximplant_app_id = Option::get($module_id, 'voximplant_app_id', '');
$voximplant_api_key = Option::get($module_id, 'voximplant_api_key', '');
$max_participants_default = Option::get($module_id, 'max_participants_default', '10');
$allow_guest_access = Option::get($module_id, 'allow_guest_access', 'N');
$auto_create_room = Option::get($module_id, 'auto_create_room', 'Y');
$default_room_duration = Option::get($module_id, 'default_room_duration', '60');
$enable_recording = Option::get($module_id, 'enable_recording', 'N');
$enable_screen_sharing = Option::get($module_id, 'enable_screen_sharing', 'Y');
$enable_chat = Option::get($module_id, 'enable_chat', 'Y');

// Формируем форму
$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('XILLIX_CHATVIDEO_TAB_MAIN'),
        'TITLE' => Loc::getMessage('XILLIX_CHATVIDEO_TAB_MAIN_TITLE'),
    ],
    [
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('XILLIX_CHATVIDEO_TAB_VOXIMPLANT'),
        'TITLE' => Loc::getMessage('XILLIX_CHATVIDEO_TAB_VOXIMPLANT_TITLE'),
    ],
    [
        'DIV' => 'edit3',
        'TAB' => Loc::getMessage('XILLIX_CHATVIDEO_TAB_FEATURES'),
        'TITLE' => Loc::getMessage('XILLIX_CHATVIDEO_TAB_FEATURES_TITLE'),
    ],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<?php $tabControl->Begin(); ?>

<form method="post"
      action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%"><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_MAX_PARTICIPANTS_DEFAULT') ?>:</td>
        <td width="60%">
            <input type="number"
                   name="max_participants_default"
                   value="<?= htmlspecialcharsbx($max_participants_default) ?>"
                   min="2"
                   max="50"
                   class="typeinput"/>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_ALLOW_GUEST_ACCESS') ?>:</td>
        <td>
            <input type="checkbox"
                   name="allow_guest_access"
                   value="Y"
                <?= $allow_guest_access == 'Y' ? 'checked' : '' ?> />
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_AUTO_CREATE_ROOM') ?>:</td>
        <td>
            <input type="checkbox"
                   name="auto_create_room"
                   value="Y"
                <?= $auto_create_room == 'Y' ? 'checked' : '' ?> />
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_DEFAULT_ROOM_DURATION') ?>:</td>
        <td>
            <input type="number"
                   name="default_room_duration"
                   value="<?= htmlspecialcharsbx($default_room_duration) ?>"
                   min="5"
                   max="1440"
                   class="typeinput"/> <?= Loc::getMessage('XILLIX_CHATVIDEO_MINUTES') ?>
        </td>
    </tr>

    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%"><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_VOXIMPLANT_ACCOUNT_ID') ?>:</td>
        <td width="60%">
            <input type="text"
                   name="voximplant_account_id"
                   value="<?= htmlspecialcharsbx($voximplant_account_id) ?>"
                   size="50"
                   class="typeinput"/>
            <br><small>Пример: 1234567</small>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_VOXIMPLANT_APP_ID') ?>:</td>
        <td>
            <input type="text"
                   name="voximplant_app_id"
                   value="<?= htmlspecialcharsbx($voximplant_app_id) ?>"
                   size="50"
                   class="typeinput"/>
            <br><small>Пример: 7654321</small>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_VOXIMPLANT_API_KEY') ?>:</td>
        <td>
            <input type="password"
                   name="voximplant_api_key"
                   value="<?= htmlspecialcharsbx($voximplant_api_key) ?>"
                   size="50"
                   class="typeinput"
                   autocomplete="off"/>
            <br><small>Ключ API из manage.voximplant.com</small>
        </td>
    </tr>
    <tr>
        <td>Тестирование подключения:</td>
        <td>
            <button type="submit"
                    name="test_voximplant"
                    value="Y"
                    class="adm-btn-save"
                    style="background: #28a745; border-color: #28a745;">
                Тестировать подключение VoxImplant
            </button>
            <br><small>Проверит корректность Account ID и API Key</small>
        </td>
    </tr>

    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%"><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_ENABLE_RECORDING') ?>:</td>
        <td width="60%">
            <input type="checkbox"
                   name="enable_recording"
                   value="Y"
                <?= $enable_recording == 'Y' ? 'checked' : '' ?> />
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_ENABLE_SCREEN_SHARING') ?>:</td>
        <td>
            <input type="checkbox"
                   name="enable_screen_sharing"
                   value="Y"
                <?= $enable_screen_sharing == 'Y' ? 'checked' : '' ?> />
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('XILLIX_CHATVIDEO_OPTION_ENABLE_CHAT') ?>:</td>
        <td>
            <input type="checkbox"
                   name="enable_chat"
                   value="Y"
                <?= $enable_chat == 'Y' ? 'checked' : '' ?> />
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>

    <input type="submit"
           name="apply"
           value="<?= Loc::getMessage('XILLIX_CHATVIDEO_APPLY') ?>"
           class="adm-btn-save"/>
    <input type="submit"
           name="default"
           value="<?= Loc::getMessage('XILLIX_CHATVIDEO_DEFAULT') ?>"/>

    <?php $tabControl->End(); ?>
</form>