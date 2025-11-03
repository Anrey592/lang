<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<div id="chat-video-conference"
     data-signed-parameters="<?= htmlspecialcharsbx($arResult['SIGNED_PARAMETERS']) ?>"
     data-voximplant-account-id="<?= htmlspecialcharsbx($arResult['VOXIMPLANT_ACCOUNT_ID']) ?>"
     data-voximplant-app-id="<?= htmlspecialcharsbx($arResult['VOXIMPLANT_APP_ID']) ?>"
     data-user-id="<?= $arResult['USER_ID'] ?>">

    <?php if (!$arResult['IS_AUTHORIZED']): ?>
        <div class="alert alert-warning">
            Для использования видеоконференций необходимо авторизоваться.
        </div>
    <?php else: ?>

        <!-- Создание комнаты -->
        <div id="room-creation" <?= $arResult['ROOM_HASH'] ? 'style="display: none;"' : '' ?>>
            <h3>Создать видеоконференцию</h3>
            <div class="form-group">
                <label>Название комнаты:</label>
                <input type="text" id="room-name" class="form-control" placeholder="Введите название комнаты"
                       value="Моя видеоконференция">
            </div>
            <div class="form-group">
                <label>Максимум участников:</label>
                <input type="number" id="max-participants" class="form-control"
                       value="<?= $arResult['MAX_PARTICIPANTS'] ?>" min="2" max="50">
            </div>
            <button id="create-room-btn" class="btn btn-primary">Создать комнату</button>
        </div>

        <!-- Ссылка на комнату -->
        <div id="room-link-container" style="display: none; margin-top: 20px;">
            <label>Ссылка для приглашения:</label>
            <div class="input-group">
                <input type="text" id="room-link" class="form-control" readonly>
                <div class="input-group-append">
                    <button onclick="copyRoomLink()" class="btn btn-secondary">Копировать</button>
                </div>
            </div>
        </div>

        <!-- Интерфейс конференции -->
        <div id="conference-interface" style="display: none; margin-top: 20px;">
            <h3>Видеоконференция</h3>

            <div id="video-container"
                 style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; margin-bottom: 20px;">
                <video id="local-video" autoplay muted
                       style="width: 100%; border: 2px solid #007bff; border-radius: 5px;"></video>
                <div id="remote-videos"></div>
            </div>

            <div class="conference-controls" style="text-align: center;">
                <button id="toggle-audio" class="btn btn-primary">🔇 Выкл/Вкл Аудио</button>
                <button id="toggle-video" class="btn btn-primary">📹 Выкл/Вкл Видео</button>
                <button id="leave-room" class="btn btn-danger">🚪 Покинуть комнату</button>
            </div>

            <div id="conference-info" style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                <strong>Информация о комнате:</strong>
                <div id="room-info"></div>
            </div>
        </div>

        <!-- Статус загрузки -->
        <div id="loading-status" style="display: none; text-align: center;">
            <div class="spinner-border" role="status">
                <span class="sr-only">Загрузка...</span>
            </div>
            <p>Подключение к видеоконференции...</p>
        </div>

    <?php endif; ?>
</div>

<script>
    function copyRoomLink() {
        const roomLink = document.getElementById('room-link');
        if (!roomLink) return;

        roomLink.select();
        roomLink.setSelectionRange(0, 99999);

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                alert('Ссылка скопирована в буфер обмена');
            }
        } catch (err) {
            console.error('Failed to copy: ', err);
        }
    }

    // Показываем статус загрузки при создании/присоединении к комнате
    function showLoading() {
        const loading = document.getElementById('loading-status');
        if (loading) {
            loading.style.display = 'block';
        }
    }

    function hideLoading() {
        const loading = document.getElementById('loading-status');
        if (loading) {
            loading.style.display = 'none';
        }
    }
</script>
