<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */

$lesson = null;
foreach ($arResult['LESSONS'] as $item) {
    if ($item['ID'] == $arResult['LESSON_ID']) {
        $lesson = $item;
        break;
    }
}

if (!$lesson): ?>
    <div class="schedule-table-container">
        <div class="no-lessons-message">
            <?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_NOT_FOUND') ?>
        </div>
        <p>
            <a href="<?= $APPLICATION->GetCurPage() ?>">&larr; <?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_BACK') ?></a>
        </p>
    </div>
<?php else: ?>
    <div class="schedule-table-container lesson-detail-container">
        <h2><?= htmlspecialcharsbx($lesson['UF_SUBJECT']) ?></h2>
        <p><strong><?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_DATE') ?>:</strong>
            <?= \Bitrix\Main\Type\DateTime::createFromPhp(
                new DateTime($lesson['UF_DATE'] . ' ' . substr($lesson['UF_START_TIME'], -8))
            )->toString() ?>
        </p>
        <p><strong><?= $arResult['MODE'] === 'student' ?
                    Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_TEACHER') :
                    Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_STUDENT') ?>:</strong>
            <?= htmlspecialcharsbx($lesson['PERSON_NAME']) ?>
        </p>

        <div class="video-container">
            <video controls width="100%">
                <source src="<?= htmlspecialcharsbx($lesson['UF_VIDEO_LESSON_LINK']) ?>" type="video/mp4">
                <?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_VIDEO_NOT_SUPPORTED') ?>
            </video>
        </div>

        <p>
            <a href="<?= $APPLICATION->GetCurPage() ?>">&larr; <?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_BACK') ?></a>
        </p>
    </div>
<?php endif; ?>

