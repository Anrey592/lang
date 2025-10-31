<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;

Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/catalog.js");
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/catalog.css");

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<?
// Получаем разделы для фильтра
$arFilter = array('IBLOCK_ID' => 3, 'ACTIVE' => 'Y');
$arSelect = array('ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID');
$rsSections = CIBlockSection::GetList(array('LEFT_MARGIN' => 'ASC'), $arFilter, false, $arSelect);
$arSections = array();
while ($arSection = $rsSections->GetNext()) {
    $arSections[] = $arSection;
}

// Формируем дерево разделов
function buildSectionTree($sections)
{
    $tree = array();
    foreach ($sections as $section) {
        if ($section['DEPTH_LEVEL'] == 1) {
            $tree[$section['ID']] = $section;
            $tree[$section['ID']]['CHILDREN'] = array();
        }
    }

    foreach ($sections as $section) {
        if ($section['DEPTH_LEVEL'] == 2 && isset($tree[$section['IBLOCK_SECTION_ID']])) {
            $tree[$section['IBLOCK_SECTION_ID']]['CHILDREN'][] = $section;
        }
    }

    return $tree;
}

$sectionTree = buildSectionTree($arSections);
$currentSectionIds = $_GET['SECTION_ID'] ?? array();
if (!is_array($currentSectionIds)) {
    $currentSectionIds = array($currentSectionIds);
}
?>

<div class="filter-overlay"></div>

<div class="repetitory-wrapper container">
    <div class="section-filter">
        <button type="button" class="btn-close-filter"></button>
        <div class="filter-title">Фильтр:</div>

        <div class="filter-sections">
            <?php foreach ($sectionTree as $parentSection): ?>
                <div class="filter-section-group">
                    <label class="parent-section">
                        <input type="checkbox"
                               name="SECTION_ID[]"
                               value="<?= $parentSection['ID'] ?>"
                               class="parent-checkbox section-filter-checkbox"
                            <?php if (in_array($parentSection['ID'], $currentSectionIds)) echo 'checked'; ?>
                               onchange="toggleChildSections(this, <?= $parentSection['ID'] ?>)">
                        <span class="checkbox-custom"></span>
                        <strong><?= $parentSection['NAME'] ?></strong>
                    </label>

                    <?php if (!empty($parentSection['CHILDREN'])): ?>
                        <div class="child-sections">
                            <?php foreach ($parentSection['CHILDREN'] as $childSection): ?>
                                <label class="child-section">
                                    <input type="checkbox"
                                           name="SECTION_ID[]"
                                           value="<?= $childSection['ID'] ?>"
                                           class="child-checkbox section-filter-checkbox"
                                           data-parent="<?= $parentSection['ID'] ?>"
                                        <?php if (in_array($childSection['ID'], $currentSectionIds)) echo 'checked'; ?>
                                           onchange="updateParentSection(<?= $parentSection['ID'] ?>)">
                                    <span class="checkbox-custom"></span>
                                    <?= $childSection['NAME'] ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="filter-buttons">
            <button type="button" class="btn btn-save" onclick="applyFilter()">Применить</button>
            <button type="button" class="btn btn-clear btn-reset" onclick="resetFilter()">Сбросить</button>
        </div>
    </div>

    <div class="repetitory">
        <div class="btn-filter">Фильтр</div>
        <?
        $GLOBALS['filterRepetitoryMain']['!=PREVIEW_PICTURE'] = false;
        if (!empty($currentSectionIds)) {
            $GLOBALS['filterRepetitoryMain'] = [
                'SECTION_ID' => $currentSectionIds,
                'INCLUDE_SUBSECTIONS' => 'Y',
            ];
        }

        $APPLICATION->IncludeComponent(
            "bitrix:news.list",
            "",
            [
                "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                "NEWS_COUNT" => $arParams["NEWS_COUNT"],
                "SORT_BY1" => $arParams["SORT_BY1"],
                "SORT_ORDER1" => $arParams["SORT_ORDER1"],
                "SORT_BY2" => $arParams["SORT_BY2"],
                "SORT_ORDER2" => $arParams["SORT_ORDER2"],
                "FIELD_CODE" => $arParams["LIST_FIELD_CODE"],
                "PROPERTY_CODE" => $arParams["LIST_PROPERTY_CODE"],
                "DETAIL_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["detail"],
                "SECTION_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["section"],
                "IBLOCK_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["news"],
                "SET_TITLE" => $arParams["SET_TITLE"],
                "SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
                "MESSAGE_404" => $arParams["MESSAGE_404"],
                "SET_STATUS_404" => $arParams["SET_STATUS_404"],
                "SHOW_404" => $arParams["SHOW_404"],
                "FILE_404" => $arParams["FILE_404"],
                "INCLUDE_IBLOCK_INTO_CHAIN" => $arParams["INCLUDE_IBLOCK_INTO_CHAIN"],
                "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                "CACHE_TIME" => $arParams["CACHE_TIME"],
                "CACHE_FILTER" => $arParams["CACHE_FILTER"],
                "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                "DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
                "DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
                "PAGER_TITLE" => $arParams["PAGER_TITLE"],
                "PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
                "PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
                "PAGER_DESC_NUMBERING" => $arParams["PAGER_DESC_NUMBERING"],
                "PAGER_DESC_NUMBERING_CACHE_TIME" => $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
                "PAGER_SHOW_ALL" => $arParams["PAGER_SHOW_ALL"],
                "PAGER_BASE_LINK_ENABLE" => $arParams["PAGER_BASE_LINK_ENABLE"],
                "PAGER_BASE_LINK" => $arParams["PAGER_BASE_LINK"],
                "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
                "DISPLAY_DATE" => $arParams["DISPLAY_DATE"],
                "DISPLAY_NAME" => "Y",
                "DISPLAY_PICTURE" => $arParams["DISPLAY_PICTURE"],
                "DISPLAY_PREVIEW_TEXT" => $arParams["DISPLAY_PREVIEW_TEXT"],
                "PREVIEW_TRUNCATE_LEN" => $arParams["PREVIEW_TRUNCATE_LEN"],
                "ACTIVE_DATE_FORMAT" => $arParams["LIST_ACTIVE_DATE_FORMAT"],
                "USE_PERMISSIONS" => $arParams["USE_PERMISSIONS"],
                "GROUP_PERMISSIONS" => $arParams["GROUP_PERMISSIONS"],
                "FILTER_NAME" => 'filterRepetitoryMain',
                "HIDE_LINK_WHEN_NO_DETAIL" => $arParams["HIDE_LINK_WHEN_NO_DETAIL"],
                "CHECK_DATES" => $arParams["CHECK_DATES"],
                "SHOW_NAV" => "Y",
            ],
            $component
        ); ?>
    </div>
</div>
