<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
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

<div class="repetitory-content container">
    <div class="items">
        <div class="items-container">
            <? foreach ($arResult["ITEMS"] as $arItem) { ?>
                <?
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

                $backPhoto = '';
                if ($arItem['PREVIEW_PICTURE']['SRC']) {
                    $backPhoto = SITE_TEMPLATE_PATH . '/img/teachers/violet.png';
                    if (!empty($arItem['PROPERTIES']['BACK_PHOTO']['VALUE'])) {
                        $backPhoto = CFile::GetPath($arItem['PROPERTIES']['BACK_PHOTO']['VALUE']);
                    }
                } else {
                    $arFile = CFile::MakeFileArray(SITE_TEMPLATE_PATH.'/img/no photo.png');
                    $fileId = CFile::SaveFile($arFile, 'main');
                    $file = CFile::ResizeImageGet($fileId, array('width' => 400, 'height' => 400), BX_RESIZE_IMAGE_PROPORTIONAL, true);
                    $arItem['PREVIEW_PICTURE']['SRC'] = $file['src'];
                }
                ?>
                <div class="item" id="<?= $this->GetEditAreaId($arItem['ID']); ?>">
                    <div class="teachers-img" style='background-image: url("<?= $backPhoto ?>")'>
                        <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>">
                            <img src="<?= $arItem['PREVIEW_PICTURE']['SRC'] ?>" alt="<?= $arItem['NAME'] ?>">
                        </a>
                    </div>
                    <div class="teachers-name">
                        <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>">
                            <?= $arItem['NAME'] ?>
                        </a>
                    </div>
                    <div class="teachers-info">
                        <?= pluralizeYears($arItem['PROPERTIES']['WORK_EXP']['VALUE']) ?> опыта
                    </div>
                    <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>" class="btn">Подробнее</a>
                </div>
            <? } ?>
        </div>
        <?if($arParams["SHOW_NAV"]):?>
            <?=$arResult["NAV_STRING"]?>
        <?endif;?>

        <? if ($arResult['NAV_RESULT']->NavPageCount > 1 && $arResult['NAV_RESULT']->NavPageNomer < $arResult['NAV_RESULT']->NavPageCount) { ?>
            <? $next = 1 + $arResult['NAV_RESULT']->NavPageNomer ?>
            <button class="btn btn-more" data-next="<?= $next ?>">Показать ещё</button>
        <? } ?>
    </div>
</div>
