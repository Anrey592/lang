<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Расписание");
?>
<?
global $USER;
if (!$USER->IsAuthorized()) {
    localRedirect('/auth/');
}
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
                <?php
                $APPLICATION->IncludeComponent(
                    "xillix:schedule",
                    "",
                    array(
                        "USER_ID" => $USER->GetID(),
                        "DEFAULT_DAY_ONLY" => "Y"
                    )
                );
                ?>
            </div>
        </div>
    </section>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>