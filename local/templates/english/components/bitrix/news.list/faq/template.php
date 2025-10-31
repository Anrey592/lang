<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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

<section class="faq container">
    <h2>Отвечаем на частые вопросы</h2>
    <div class="faq-content">
        <div class="faq-questions">
            <?foreach($arResult["ITEMS"] as $arItem){?>
                <?
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                ?>
                <details id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                    <summary><?=$arItem["NAME"]?></summary>
                    <p><?=$arItem["PREVIEW_TEXT"]?></p>
                </details>
            <?}?>
        </div>
        <div class="faq-contact">
            <p>Не нашли ответа на свой вопрос? Напишите нам в чат Телеграм или во ВКонтакте</p>
            <div class="soc">
                <a href="" class="tg"></a>
                <a href="" class="vk"></a>
            </div>
        </div>
    </div>
</section>
