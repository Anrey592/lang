<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult))
    return;
?>

<div class="menu">
    <ul>
        <?php
        global $APPLICATION;
        $currentPage = $APPLICATION->GetCurPage();
        $previousLevel = 0;
        foreach($arResult as $arItem): ?>
        <?
            $isActive = false;
            if(str_contains($currentPage, $arItem['LINK'])) {
                $isActive = true;
            }
        ?>

        <?php if ($previousLevel && $arItem["DEPTH_LEVEL"] < $previousLevel): ?>
            <?php echo str_repeat("</ul></li>", ($previousLevel - $arItem["DEPTH_LEVEL"])); ?>
        <?php endif; ?>

        <?php if ($arItem["IS_PARENT"]): ?>

        <?php if ($arItem["DEPTH_LEVEL"] == 1): ?>
        <li class="<?=$isActive ? 'active' : ''?>">
            <a href="<?=$arItem["LINK"]?>" class="parent">
                <span><?=$arItem["TEXT"]?></span>
                <span class="btn-open"></span>
            </a>
            <ul class="child-items">
                <?php else: ?>
                <li class="parent <?=$isActive ? 'active' : ''?>">
                    <a href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?> <span class="btn-open"></span></a>
                    <ul class="child-items">
                        <?php endif; ?>

                        <?php else: ?>

                            <?php if ($arItem["PERMISSION"] > "D"): ?>

                                <?php if ($arItem["DEPTH_LEVEL"] == 1): ?>
                                    <li class="<?=$isActive ? 'active' : ''?>"><a href="<?=$arItem["LINK"]?>" class=""><?=$arItem["TEXT"]?></a></li>
                                <?php else: ?>
                                    <li class="<?=$isActive ? 'active' : ''?>"><a href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a></li>
                                <?php endif; ?>

                            <?php endif; ?>

                        <?php endif; ?>

                        <?php $previousLevel = $arItem["DEPTH_LEVEL"]; ?>

                        <?php endforeach; ?>

                        <?php if ($previousLevel > 1)://close last item tags?>
                            <?php echo str_repeat("</ul></li>", ($previousLevel-1) );?>
                        <?php endif;?>

                    </ul>
</div>
