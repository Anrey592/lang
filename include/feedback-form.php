<section class="feedback container">
    <div class="feedback-content">
        <div class="feedback-info">
            <h2>Начните изучать язык с пробного урока</h2>
            <p>Проведем диагностику знаний, подберем программу обучения и расскажем, как проходят занятия для детей
                в нашей школе английского.</p>
            <div class="feedback-phone">
                <p>или свяжитесь с нами</p>
                <? $APPLICATION->IncludeComponent(
                    "bitrix:main.include",
                    "",
                    array(
                        "AREA_FILE_SHOW" => "file",
                        "AREA_FILE_SUFFIX" => "inc",
                        "EDIT_TEMPLATE" => "",
                        "PATH" => "/include/phone.php"
                    )
                ); ?>
            </div>
        </div>
        <div class="feedback-forms">
            <form id="feedback">
                <div class="info">
                    Мы перезвоним с 8:00 до 22:00 по МСК,
                    ответим на вопросы
                </div>
                <div class="fields">
                    <input type="text" name="name" placeholder="Ваше имя">
                    <input type="tel" name="phone" placeholder="+7 (___) ___-__-__">
                    <textarea name="" id="" cols="30" rows="8" placeholder="Комментарий"></textarea>
                </div>
                <button type="submit" class="btn">Оставить заявку</button>
                <div class="form-offer">
                    <input type="checkbox" name="policy" id="topFormPolicyFeedback">
                    <label for="topFormPolicyFeedback">Принимаю <a href="/politika-obrabotki-personalnykh-dannykh/">политики конфиденциальности</a></label>
                    <input type="checkbox" name="offer" id="topFormOfferFeedback">
                    <label for="topFormOfferFeedback">Даю согласие <a href="/soglasie-na-obrabotku-personalnykh-dannykh/">на обработку персональных данных</a></label>
                </div>
            </form>
            <div class="feedback-img"></div>
        </div>
    </div>
</section>
