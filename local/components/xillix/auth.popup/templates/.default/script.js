class AuthPopup {
    constructor() {
        this.popup = document.getElementById('authPopup');
        this.form = document.getElementById('authPopupForm');
        this.message = document.getElementById('authPopupMessage');
        this.showPoliciesLink = document.getElementById('showPoliciesLink');
        this.policiesContainer = document.getElementById('policiesContainer');
        this.telegramRegisterBtn = document.getElementById('telegramRegisterBtn');
        this.policyInputs = document.querySelectorAll('.policy-input');
        this.passwordToggle = document.getElementById('popup_password_toggle');
        this.passwordInput = document.getElementById('popup_password');
        this.qrTg = document.querySelectorAll('.auth-qr-tg');

        this.passwordToggle.addEventListener('click', this.togglePasswordVisibility.bind(this));

        this.init();
    }

    init() {
        // Открытие попапа при клике на кнопки входа
        document.querySelectorAll('.btn-login, .personal.auth, .btn[href*="auth"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.open();
            });
        });

        // Закрытие попапа
        this.popup.querySelector('.close').addEventListener('click', () => this.close());
        this.popup.addEventListener('click', (e) => {
            if (e.target === this.popup) this.close();
        });

        // Форматирование телефона
        const phoneInput = document.getElementById('popup_phone');
        phoneInput.addEventListener('input', this.formatPhone.bind(this));

        // Отправка формы
        this.form.addEventListener('submit', this.handleSubmit.bind(this));

        // Показать чекбоксы политик
        this.showPoliciesLink.addEventListener('click', (e) => {
            e.preventDefault();
            this.showPolicies();
        });

        // Проверка чекбоксов для активации кнопки Telegram
        this.policyInputs.forEach(input => {
            input.addEventListener('change', this.updateTelegramButton.bind(this));
        });

        this.telegramRegisterBtn.addEventListener('click', this.openTelegramBot.bind(this));
    }

    updateTelegramButton() {
        const allChecked = Array.from(this.policyInputs).every(input => input.checked);
        this.telegramRegisterBtn.disabled = !allChecked;

        this.qrTg.forEach(item => {
            item.classList.add('hidden');
            if (allChecked && window.screen.width > 1100) {
                item.classList.remove('hidden');
            }
        });
    }

    open() {
        this.popup.style.display = 'block';
        document.body.classList.add('no-scroll');
    }

    close() {
        this.popup.style.display = 'none';
        document.body.classList.remove('no-scroll');
        this.form.reset();
        this.hideMessage();
    }

    showPolicies() {
        this.policiesContainer.style.display = 'block';
        this.showPoliciesLink.style.display = 'none';
    }

    hidePolicies() {
        this.policiesContainer.style.display = 'none';
        this.showPoliciesLink.style.display = 'block';
        // Сбрасываем чекбоксы
        this.policyInputs.forEach(input => {
            input.checked = false;
        });
        this.telegramRegisterBtn.disabled = true;
    }

    formatPhone(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.startsWith('7') || value.startsWith('8')) {
            value = value.substring(1);
        }
        if (value.length > 0) {
            value = '+7 (' + value;
            if (value.length > 7) value = value.substring(0, 7) + ') ' + value.substring(7);
            if (value.length > 12) value = value.substring(0, 12) + '-' + value.substring(12);
            if (value.length > 15) value = value.substring(0, 15) + '-' + value.substring(15, 17);
        }
        e.target.value = value;
    }

    async handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData(this.form);

        try {
            const response = await fetch('/ajax/auth.php?login=yes', {
                method: 'POST',
                body: formData
            });

            const result = await response.text();

            if (response.ok) {
                this.showMessage('Успешный вход!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.showMessage('Ошибка авторизации. Проверьте телефон и пароль.', 'error');
            }
        } catch (error) {
            this.showMessage('Ошибка соединения', 'error');
        }
    }

    openTelegramBot(e) {
        const btn = e.target;
        const backUrl = btn.getAttribute('data-url');
        window.open('https://t.me/SaleMultiLangBot?start=backUrl_' + backUrl, '_blank');
        this.showMessage('Открыт Telegram бот. Выберите "Регистрация" для создания аккаунта.', 'info');
    }

    showMessage(text, type) {
        this.message.textContent = text;
        this.message.className = `message ${type}`;
        this.message.style.display = 'block';
    }

    hideMessage() {
        this.message.style.display = 'none';
    }

    togglePasswordVisibility() {
        if (this.passwordInput.type === 'password') {
            this.passwordInput.type = 'text';
            this.passwordToggle.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 2L18 18M8 8C7.46957 8.53043 7 9.42857 7 10C7 11.6569 8.34315 13 10 13C10.5714 13 11.4696 12.5304 12 12M10 4C14 4 17 10 17 10C17 10 16 11.5 14 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            `;
        } else {
            this.passwordInput.type = 'password';
            this.passwordToggle.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 4C4 4 1 10 1 10C1 10 4 16 10 16C16 16 19 10 19 10C19 10 16 4 10 4Z" stroke="currentColor" stroke-width="2"/>
                    <circle cx="10" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                </svg>
            `;
        }
    }

}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function () {
    new AuthPopup();
});