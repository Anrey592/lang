<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<div class="auth-popup modal" id="authPopup" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Вход в систему</h3>

        <form id="authPopupForm">
            <?= bitrix_sessid_post() ?>
            <input type="hidden" name="AUTH_FORM" value="Y">
            <input type="hidden" name="TYPE" value="AUTH">

            <div class="form-group">
                <label for="popup_phone">Телефон</label>
                <input type="tel" id="popup_phone" name="USER_PHONE" required
                       placeholder="+7 (___) ___-__-__">
            </div>

            <div class="form-group">
                <label for="popup_password">Пароль</label>
                <div class="password-field">
                    <input type="password" id="popup_password" name="USER_PASSWORD" required
                           placeholder="Введите пароль">
                    <button type="button" class="password-toggle" id="popup_password_toggle">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 4C4 4 1 10 1 10C1 10 4 16 10 16C16 16 19 10 19 10C19 10 16 4 10 4Z"
                                  stroke="currentColor" stroke-width="2"/>
                            <circle cx="10" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Войти</button>

            <div class="auth-popup-links">
                <?php
                $currentUrl = $APPLICATION->GetCurPage();
                $encodedUrl = str_replace(
                    ['/', '?', '=', '&', '#', '.', ':', '%', '+', '-'],
                    ['__', '_Q_', '_E_', '_A_', '_H_', '_D_', '_C_', '_P_', '_PL_', '_M_'],
                    $currentUrl
                );
                ?>
                <a href="#" class="register-link" id="showPoliciesLink">Регистрация через Telegram</a>

                <div class="policies-container" id="policiesContainer" style="display: none;">
                    <div class="policy-checkbox">
                        <input type="checkbox" id="policy1" name="policy1" class="policy-input">
                        <label for="policy1" class="policy-label">
                            Я согласен с <a href="/politika-obrabotki-personalnykh-dannykh/" target="_blank"
                                            class="policy-link">политикой обработки персональных данных</a>
                        </label>
                    </div>
                    <div class="policy-checkbox">
                        <input type="checkbox" id="policy2" name="policy2" class="policy-input">
                        <label for="policy2" class="policy-label">
                            Я даю <a href="/soglasie-na-obrabotku-personalnykh-dannykh/" target="_blank"
                                     class="policy-link">согласие на обработку персональных данных</a>
                        </label>
                    </div>
                    <button type="button" class="btn btn-telegram" data-url="<?= $encodedUrl ?>"
                            id="telegramRegisterBtn" disabled>
                        Перейти в Telegram
                    </button>

                    <div class="auth-qr-tg hidden">
                        <p>или</p>
                        <img src="<?=SITE_TEMPLATE_PATH?>/img/qr-tg.svg" alt="">
                    </div>
                </div>
            </div>
        </form>

        <div id="authPopupMessage" class="message"></div>
    </div>
</div>
