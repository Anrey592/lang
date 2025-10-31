<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Личный кабинет");
?>
<?
global $USER;
if (!$USER->IsAuthorized()) {
    localRedirect('/auth/');
}

localRedirect('/personal/raspisanie/');
?>

    <section class="personal container">
        <div class="personal-content">
            <div class="personal-left">
                <? $APPLICATION->IncludeComponent(
                    "bitrix:menu",
                    "personal",
                    array(
                        "ALLOW_MULTI_SELECT" => "N",
                        "CHILD_MENU_TYPE" => "left",
                        "DELAY" => "N",
                        "MAX_LEVEL" => "1",
                        "MENU_CACHE_GET_VARS" => array(""),
                        "MENU_CACHE_TIME" => "360000",
                        "MENU_CACHE_TYPE" => "A",
                        "MENU_CACHE_USE_GROUPS" => "Y",
                        "ROOT_MENU_TYPE" => "left",
                        "USE_EXT" => "N"
                    )
                ); ?>
            </div>
            <div class="personal-right">

            </div>
        </div>
    </section>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>