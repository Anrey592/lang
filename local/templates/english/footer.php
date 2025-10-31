<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();
?>

</main>
<footer>
    <div class="footer-top container">
        <div class="footer-phone">
            <ul>
                <li class="footer-phone-tel">
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
                    <p>Записаться на бесплатное занятие</p>
                </li>
                <li class="footer-phone-tel">
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
                    <p>Поддержка учеников и преподавателей</p>
                </li>
                <li class="footer-phone-mail">
                    <? $APPLICATION->IncludeComponent(
                        "bitrix:main.include",
                        "",
                        array(
                            "AREA_FILE_SHOW" => "file",
                            "AREA_FILE_SUFFIX" => "inc",
                            "EDIT_TEMPLATE" => "",
                            "PATH" => "/include/mail.php"
                        )
                    ); ?>
                </li>
            </ul>
        </div>
        <div class="footer-menu">
            <p class="footer-menu-title">Курсы</p>

            <?$APPLICATION->IncludeComponent(
                "bitrix:menu",
                "bottom",
                Array(
                    "ALLOW_MULTI_SELECT" => "N",
                    "CHILD_MENU_TYPE" => "left",
                    "DELAY" => "N",
                    "MAX_LEVEL" => "1",
                    "MENU_CACHE_GET_VARS" => array(""),
                    "MENU_CACHE_TIME" => "360000",
                    "MENU_CACHE_TYPE" => "A",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "ROOT_MENU_TYPE" => "bottom_courses",
                    "USE_EXT" => "N"
                )
            );?>
        </div>
        <div class="footer-menu">
            <p class="footer-menu-title">О школе</p>

            <?$APPLICATION->IncludeComponent(
                "bitrix:menu",
                "bottom",
                Array(
                    "ALLOW_MULTI_SELECT" => "N",
                    "CHILD_MENU_TYPE" => "left",
                    "DELAY" => "N",
                    "MAX_LEVEL" => "1",
                    "MENU_CACHE_GET_VARS" => array(""),
                    "MENU_CACHE_TIME" => "360000",
                    "MENU_CACHE_TYPE" => "A",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "ROOT_MENU_TYPE" => "bottom_about_school",
                    "USE_EXT" => "N"
                )
            );?>
        </div>
        <div class="footer-menu">
            <p class="footer-menu-title">Дополнительно</p>

            <?$APPLICATION->IncludeComponent(
                "bitrix:menu",
                "bottom",
                Array(
                    "ALLOW_MULTI_SELECT" => "N",
                    "CHILD_MENU_TYPE" => "left",
                    "DELAY" => "N",
                    "MAX_LEVEL" => "1",
                    "MENU_CACHE_GET_VARS" => array(""),
                    "MENU_CACHE_TIME" => "360000",
                    "MENU_CACHE_TYPE" => "A",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "ROOT_MENU_TYPE" => "bottom_additionally",
                    "USE_EXT" => "N"
                )
            );?>
        </div>
        <div class="footer-menu">
            <ul>
                <li class="footer-menu-title">Соцсети</li>
                <li>
                    <div class="soc">
                        <a href="" class="tg"></a>
                        <a href="" class="vk"></a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom container">
        <div class="footer-policy">
            <a href="/politika-obrabotki-personalnykh-dannykh/">Политика обработки персональных данных</a>
            <a href="/soglasie-na-obrabotku-personalnykh-dannykh/">Согласие на обработку персональных данных</a>
            <a href="/svedeniya-ob-obrazovatelnoy-organizatsii/">Сведения об образовательной организации</a>
        </div>
        <span>&copy; MultiLang, <?=date('Y')?></span>
    </div>
</footer>
</div>

<div class="loader hidden"></div>

</body>
</html>
