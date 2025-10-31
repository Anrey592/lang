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


<section class="teachers">
    <div class="teachers-container">
        <h2>Репетиторы -<br>залог вашего успеха</h2>
        <div class="teachers-desc">
            Вам не нужно переживать о квалификации педагогов — все учителя на онлайн-курсах английского для детей и
            подростков прошли строгий трехэтапный отбор.
        </div>
        <div class="teachers-content">
            <? $stringSwiper = randString(
                5,
                [
                    "abcdefghijklnmopqrstuvwxyz",
                    "ABCDEFGHIJKLNMOPQRSTUVWXYZ",
                    "0123456789"
                ]
            ); ?>
            <div class="swiper swiper-<?= $stringSwiper ?>">
                <div class="swiper-wrapper">
                    <? foreach ($arResult["ITEMS"] as $arItem) { ?>
                        <?
                        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                        $backPhoto = SITE_TEMPLATE_PATH . '/img/teachers/violet.png';
                        if (!empty($arItem['PROPERTIES']['BACK_PHOTO']['VALUE'])) {
                            $backPhoto = CFile::GetPath($arItem['PROPERTIES']['BACK_PHOTO']['VALUE']);
                        }
                        ?>
                        <div class="swiper-slide" id="<?= $this->GetEditAreaId($arItem['ID']); ?>">
                            <div class="teachers-img" style='background-image: url("<?=$backPhoto?>")'>
                                <a href="<?=$arItem['DETAIL_PAGE_URL']?>">
                                    <img src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" alt="<?=$arItem['NAME']?>">
                                </a>
                            </div>
                            <div class="teachers-name">
                                <a href="<?=$arItem['DETAIL_PAGE_URL']?>">
                                    <?=$arItem['NAME']?>
                                </a>
                            </div>
                            <div class="teachers-info">
                                <?=pluralizeYears($arItem['PROPERTIES']['WORK_EXP']['VALUE'])?> опыта
                            </div>
                            <button class="btn">Вводный урок</button>
                        </div>
                    <? } ?>
                </div>
                <div class="swiper-button">
                    <div class="swiper-button-prev swiper-<?= $stringSwiper ?>"
                    ">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 7L13 7M1 7L7 1M1 7L7 13" stroke="#1E2137" stroke-width="1.5"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                </div>
                <div class="swiper-button-next swiper-<?= $stringSwiper ?>"
                ">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 7L13 7M1 7L7 1M1 7L7 13" stroke="#1E2137" stroke-width="1.5" stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </div>

    <script>
        const swiper<?=$stringSwiper?> = new Swiper(".swiper.swiper-<?=$stringSwiper?>", {
            slidesPerView: "auto",
            spaceBetween: 12,
            navigation: {
                nextEl: ".swiper-button-next.swiper-<?=$stringSwiper?>",
                prevEl: ".swiper-button-prev.swiper-<?=$stringSwiper?>",
            }, breakpoints: {
                620: {
                    slidesPerView: 2,
                    spaceBetween: 20
                },
                840: {
                    slidesPerView: 3,
                    spaceBetween: 20
                },
                1200: {
                    slidesPerView: 4,
                    spaceBetween: 20
                },
                1500: {
                    slidesPerView: 5,
                    spaceBetween: 20
                },
            }
        });
    </script>
    </div>
    </div>
</section>
