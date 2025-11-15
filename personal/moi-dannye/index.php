<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Мои данные");
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

                <?
                $GLOBALS['filterFaqPersonal']['!=PROPERTY_PERSONAL'] = false;
                $APPLICATION->IncludeComponent(
                    "bitrix:news.list",
                    "faq-personal",
                    array(
                        "ACTIVE_DATE_FORMAT" => "d.m.Y",
                        "ADD_SECTIONS_CHAIN" => "N",
                        "AJAX_MODE" => "N",
                        "AJAX_OPTION_ADDITIONAL" => "",
                        "AJAX_OPTION_HISTORY" => "N",
                        "AJAX_OPTION_JUMP" => "N",
                        "AJAX_OPTION_STYLE" => "N",
                        "CACHE_FILTER" => "N",
                        "CACHE_GROUPS" => "Y",
                        "CACHE_TIME" => "36000000",
                        "CACHE_TYPE" => "A",
                        "CHECK_DATES" => "Y",
                        "DETAIL_URL" => "",
                        "DISPLAY_BOTTOM_PAGER" => "N",
                        "DISPLAY_DATE" => "N",
                        "DISPLAY_NAME" => "Y",
                        "DISPLAY_PICTURE" => "N",
                        "DISPLAY_PREVIEW_TEXT" => "N",
                        "DISPLAY_TOP_PAGER" => "N",
                        "FIELD_CODE" => array("", ""),
                        "FILTER_NAME" => "filterFaqPersonal",
                        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
                        "IBLOCK_ID" => "1",
                        "IBLOCK_TYPE" => "content",
                        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                        "INCLUDE_SUBSECTIONS" => "N",
                        "MESSAGE_404" => "",
                        "NEWS_COUNT" => "20",
                        "PAGER_BASE_LINK_ENABLE" => "N",
                        "PAGER_DESC_NUMBERING" => "N",
                        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
                        "PAGER_SHOW_ALL" => "N",
                        "PAGER_SHOW_ALWAYS" => "N",
                        "PAGER_TEMPLATE" => ".default",
                        "PAGER_TITLE" => "Новости",
                        "PARENT_SECTION" => "",
                        "PARENT_SECTION_CODE" => "",
                        "PREVIEW_TRUNCATE_LEN" => "",
                        "PROPERTY_CODE" => array("", ""),
                        "SET_BROWSER_TITLE" => "N",
                        "SET_LAST_MODIFIED" => "N",
                        "SET_META_DESCRIPTION" => "N",
                        "SET_META_KEYWORDS" => "N",
                        "SET_STATUS_404" => "N",
                        "SET_TITLE" => "N",
                        "SHOW_404" => "N",
                        "SORT_BY1" => "ACTIVE_FROM",
                        "SORT_BY2" => "SORT",
                        "SORT_ORDER1" => "DESC",
                        "SORT_ORDER2" => "ASC",
                        "STRICT_SECTION_CHECK" => "N"
                    )
                ); ?>
            </div>
            <div class="personal-right">
                <?
                $APPLICATION->IncludeComponent(
	"xillix:user.profile", 
	".default", 
	[
		"FIELDS" => [
			0 => "NAME",
			1 => "LAST_NAME",
			2 => "PERSONAL_PHONE",
			3 => "PERSONAL_BIRTHDAY",
			4 => "PERSONAL_PHOTO",
		],
		"UF_FIELDS" => [
			0 => "UF_TRUECONF_LOGIN",
			1 => "UF_TRUECONF_PASSWORD",
		],
		"ALLOW_EDIT" => "Y",
		"SHOW_AVATAR" => "Y",
		"COMPONENT_TEMPLATE" => ".default",
		"READONLY_FIELDS" => [
		],
		"IBLOCK_ID" => "3",
		"ELEMENT_FIELDS" => [
			1 => "PREVIEW_TEXT",
		],
		"ELEMENT_PROPERTIES" => "WORK_EXP, ABOUT, VIDEO, VOICE",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600"
	],
	false
);?>
            </div>
        </div>
    </section>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>