<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

use Bitrix\Main\Page\Asset;

CJSCore::Init();
global $USER;
global $APPLICATION;
//t.me/SaleMultiLangBot
//8453534744:AAFbz8szQTNuN5h9nPTTTWC1FruOxGrEYw4
?>

<? $APPLICATION->IncludeComponent(
    "xillix:auth.popup",
    "",
    array(),
    false
); ?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <? $APPLICATION->ShowHead(); ?>
    <title><? $APPLICATION->ShowTitle(); ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?
    Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/swiper-bundle.min.css");
    Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/style.css");
    Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/swiper-bundle.min.js");
    Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/maskPhone.js");
    Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/script.js");
    ?>
</head>
<body>
<div id="panel">
    <? $APPLICATION->ShowPanel(); ?>
</div>
<div class="additional container">
    <? /*<div class="logo"></div>*/ ?>
    <? $APPLICATION->IncludeComponent(
        "bitrix:menu",
        "top",
        array(
            "ALLOW_MULTI_SELECT" => "N",
            "CHILD_MENU_TYPE" => "left",
            "DELAY" => "N",
            "MAX_LEVEL" => "1",
            "MENU_CACHE_GET_VARS" => array(""),
            "MENU_CACHE_TIME" => "360000",
            "MENU_CACHE_TYPE" => "A",
            "MENU_CACHE_USE_GROUPS" => "Y",
            "ROOT_MENU_TYPE" => "top",
            "USE_EXT" => "N"
        )
    ); ?>
    <div class="additional-contacts">
        <div class="phone">
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
            <span href="" class="color-accent">Перезвоните мне</span>
        </div>
        <button class="btn">Записаться на урок</button>
        <a class="personal <?= !$USER->IsAuthorized() ? 'auth' : '' ?>" href="/personal/"></a>
    </div>
</div>
<div class="top container">
    <div class="top-menu-container">
        <? $APPLICATION->IncludeComponent(
            "bitrix:menu",
            "main",
            array(
                "ALLOW_MULTI_SELECT" => "N",
                "CHILD_MENU_TYPE" => "left",
                "DELAY" => "N",
                "MAX_LEVEL" => "2",
                "MENU_CACHE_GET_VARS" => array(""),
                "MENU_CACHE_TIME" => "3600",
                "MENU_CACHE_TYPE" => "A",
                "MENU_CACHE_USE_GROUPS" => "Y",
                "ROOT_MENU_TYPE" => "main",
                "USE_EXT" => "Y"
            )
        ); ?>
        <div class="burger"></div>
        <button class="btn">Записаться на урок</button>
        <a class="personal <?= !$USER->IsAuthorized() ? 'auth' : '' ?>" href="/personal/"></a>
    </div>
</div>
<div class="mobile-menu hidden">
    <div class="mobile-menu-wrapper">
        <div class="btn-close-wrapper">
            <div class="btn-close"></div>
        </div>
        <div class="mobile-menu-wrapper-container">
            <? $APPLICATION->IncludeComponent(
                "bitrix:menu",
                "main-mobile",
                array(
                    "ALLOW_MULTI_SELECT" => "N",
                    "CHILD_MENU_TYPE" => "left",
                    "DELAY" => "N",
                    "MAX_LEVEL" => "2",
                    "MENU_CACHE_GET_VARS" => array(""),
                    "MENU_CACHE_TIME" => "3600",
                    "MENU_CACHE_TYPE" => "A",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "ROOT_MENU_TYPE" => "main",
                    "USE_EXT" => "Y"
                )
            ); ?>
            <? $APPLICATION->IncludeComponent(
                "bitrix:menu",
                "top-mobile",
                array(
                    "ALLOW_MULTI_SELECT" => "N",
                    "CHILD_MENU_TYPE" => "left",
                    "DELAY" => "N",
                    "MAX_LEVEL" => "1",
                    "MENU_CACHE_GET_VARS" => array(""),
                    "MENU_CACHE_TIME" => "360000",
                    "MENU_CACHE_TYPE" => "A",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "ROOT_MENU_TYPE" => "top",
                    "USE_EXT" => "N"
                )
            ); ?>
            <div class="phone">
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
                <span href="" class="color-accent">Перезвоните мне</span>
            </div>
            <button class="btn">Записаться на урок</button>
            <div class="soc">
                <a href="" class="tg"></a>
                <a href="" class="vk"></a>
            </div>
        </div>
    </div>
</div>
<div class="wrapper">
    <header>
        <?
        $GLOBALS['filterTopBanner'] = ['PROPERTY_URL' => $APPLICATION->GetCurPage()];
        if ($APPLICATION->GetCurPage() == '/') {
            $GLOBALS['filterTopBanner'] = ['!=PROPERTY_SHOW_HOME' => false];
        }
        $APPLICATION->IncludeComponent(
            "bitrix:news.list",
            "top-banner",
            array(
                "ACTIVE_DATE_FORMAT" => "d.m.Y",
                "ADD_SECTIONS_CHAIN" => "N",
                "AJAX_MODE" => "N",
                "AJAX_OPTION_ADDITIONAL" => "",
                "AJAX_OPTION_HISTORY" => "N",
                "AJAX_OPTION_JUMP" => "N",
                "AJAX_OPTION_STYLE" => "Y",
                "CACHE_FILTER" => "N",
                "CACHE_GROUPS" => "Y",
                "CACHE_TIME" => "36000000",
                "CACHE_TYPE" => "A",
                "CHECK_DATES" => "Y",
                "DETAIL_URL" => "",
                "DISPLAY_BOTTOM_PAGER" => "N",
                "DISPLAY_DATE" => "N",
                "DISPLAY_NAME" => "N",
                "DISPLAY_PICTURE" => "Y",
                "DISPLAY_PREVIEW_TEXT" => "Y",
                "DISPLAY_TOP_PAGER" => "N",
                "FIELD_CODE" => array("", ""),
                "FILTER_NAME" => "filterTopBanner",
                "HIDE_LINK_WHEN_NO_DETAIL" => "N",
                "IBLOCK_ID" => "4",
                "IBLOCK_TYPE" => "promo",
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
                "PROPERTY_CODE" => array("URL", "SHOW_HOME", "SHOW_FORM", ""),
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
    </header>
    <main>
        <?
        if ($APPLICATION->GetCurPage() != '/') {
            $APPLICATION->IncludeComponent(
                "bitrix:breadcrumb",
                "eng",
                array(
                    "PATH" => "",
                    "SITE_ID" => "s1",
                    "START_FROM" => "0"
                )
            );
            ?>
            <h1 class="container"><? $APPLICATION->ShowTitle(false) ?></h1>
        <? } ?>
	
						