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

// Получаем ID профиля преподавателя
$teacherId = $arResult['PROPERTIES']['PROFILE_ID']['VALUE'] ?? 0;
$video = $arResult['PROPERTIES']['VIDEO']['VALUE'] ?? '';
$voice = $arResult['PROPERTIES']['VOICE']['VALUE'] ?? '';
$previewPicture = $arResult['PREVIEW_PICTURE'] ?? $arResult['DETAIL_PICTURE'];
?>

<div class="repetitory-detail">
    <div class="teacher-profile">
        <div class="teacher-main-info">
            <div class="teacher-media">
                <? if ($video): ?>
                    <div class="teacher-video">
                        <video
                                controls
                                poster="<?= $previewPicture['SRC'] ?? '' ?>"
                                class="teacher-media-element"
                        >
                            <source src="<?= CFile::GetPath($video) ?>" type="video/mp4">
                            Ваш браузер не поддерживает видео.
                        </video>
                    </div>
                <? elseif ($previewPicture): ?>
                    <div class="teacher-photo">
                        <img
                                src="<?= $previewPicture['SRC'] ?>"
                                alt="<?= $previewPicture['ALT'] ?>"
                                title="<?= $previewPicture['TITLE'] ?>"
                                class="teacher-media-element"
                        >
                    </div>
                <? endif; ?>

                <? if ($voice): ?>
                    <div class="teacher-voice">
                        <audio controls class="teacher-audio">
                            <source src="<?= CFile::GetPath($voice) ?>" type="audio/mpeg">
                            Ваш браузер не поддерживает аудио.
                        </audio>
                    </div>
                <? endif; ?>

                <div class="lesson-booking">
                    <button class="btn-book-lesson" id="bookLessonBtn">
                        Записаться на урок
                    </button>
                </div>
            </div>

            <div class="teacher-info">
                <? if ($arParams["DISPLAY_DATE"] != "N" && $arResult["DISPLAY_ACTIVE_FROM"]): ?>
                    <span class="teacher-date"><?= $arResult["DISPLAY_ACTIVE_FROM"] ?></span>
                <? endif; ?>

                <!-- Свойства преподавателя -->
                <div class="teacher-properties">
                    <? foreach ($arResult["DISPLAY_PROPERTIES"] as $pid => $arProperty): ?>
                        <? if ($pid == 'ABOUT' && !empty($arProperty['VALUE'])) { ?>
                            <div class="teacher-property">
                                <strong><?= $arProperty["NAME"] ?>:</strong>
                                <ul>
                                    <? foreach ($arProperty['VALUE'] as $val) { ?>
                                        <li><?= $val ?></li>
                                    <? } ?>
                                </ul>
                            </div>
                        <? } ?>
                        <? if ($pid != 'VIDEO' && $pid != 'VOICE' && $pid != 'PROFILE_ID' && $pid != 'ABOUT'): ?>
                            <div class="teacher-property">
                                <strong><?= $arProperty["NAME"] ?>:</strong>
                                <span class="property-value">
                                    <? if (is_array($arProperty["DISPLAY_VALUE"])): ?>
                                        <?= implode("&nbsp;/&nbsp;", $arProperty["DISPLAY_VALUE"]); ?>
                                    <? else: ?>
                                        <?= $arProperty["DISPLAY_VALUE"]; ?>
                                    <? endif ?>
                                </span>
                            </div>
                        <? endif; ?>
                    <? endforeach; ?>
                </div>

                <? if ($arResult["DETAIL_TEXT"] <> ''): ?>
                    <div class="teacher-detail-text">
                        <? echo $arResult["DETAIL_TEXT"]; ?>
                    </div>
                <? elseif ($arResult["PREVIEW_TEXT"]): ?>
                    <div class="teacher-detail-text">
                        <? echo $arResult["PREVIEW_TEXT"]; ?>
                    </div>
                <? endif ?>
            </div>
        </div>

        <div class="teacher-schedule-container" id="scheduleContainer" style="display: none;">
            <div class="schedule-header">
                <h2>Запись на урок</h2>
                <button class="btn-close-schedule" id="closeScheduleBtn">Закрыть</button>
            </div>

            <?if($teacherId):?>
                <?$APPLICATION->IncludeComponent(
                    "xillix:schedule.booking",
                    "",
                    array(
                        "TEACHER_ID" => $teacherId,
                        "TEACHER_NAME" => $arResult['NAME'],
                        "DEFAULT_DAY_ONLY" => "Y",
                    ),
                    $component
                );?>
            <?else:?>
                <p class="error-message">ID преподавателя не указан</p>
            <?endif;?>
        </div>
    </div>
</div>