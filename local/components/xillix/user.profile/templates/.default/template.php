<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */

/** @var UserProfileComponent $component */

use Bitrix\Main\Localization\Loc;

// Подключаем языковые файлы шаблона
Loc::loadMessages(__FILE__);

?>
<div class="user-profile">
    <?php if ($arResult['ERROR']): ?>
        <div class="user-profile-error">
            <?= $arResult['ERROR'] ?>
        </div>
    <?php endif; ?>

    <?php if ($arResult['SUCCESS']): ?>
        <div class="user-profile-success">
            <?= $arResult['SUCCESS'] ?>
        </div>
    <?php endif; ?>

    <!-- Режим просмотра -->
    <div class="user-profile-view" id="userProfileView" <?= $arResult['IS_EDIT_MODE'] ? 'style="display:none;"' : '' ?>>
        <div class="user-profile-fields">
            <?php if ($arResult['SHOW_AVATAR'] && isset($arResult['FIELDS']['PERSONAL_PHOTO'])): ?>
                <div class="user-profile-field user-profile-field-photo">
                    <span class="user-profile-label"><?= $arResult['FIELDS']['PERSONAL_PHOTO'] ?></span>
                    <div class="user-profile-photo-container">
                        <?php if (!empty($arResult['USER_DATA']['PERSONAL_PHOTO_SRC'])): ?>
                            <img src="<?= $arResult['USER_DATA']['PERSONAL_PHOTO_SRC'] ?>"
                                 alt="<?= Loc::getMessage('XILLIX_USER_PROFILE_AVATAR_ALT') ?>"
                                 class="user-profile-photo">
                        <?php else: ?>
                            <div class="user-profile-photo-placeholder">
                                <?= Loc::getMessage('XILLIX_USER_PROFILE_NO_PHOTO') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($arResult['FIELDS'] as $fieldCode => $fieldName): ?>
                <?php if ($fieldCode === 'PERSONAL_PHOTO') continue; ?>

                <div class="user-profile-field">
                    <span class="user-profile-label"><?= $fieldName ?></span>
                    <div class="user-profile-value">
                        <span class="user-profile-text">
                            <?php
                            $value = $arResult['USER_DATA'][$fieldCode] ?? '';
                            if ($fieldCode === 'PERSONAL_GENDER') {
                                echo $arResult['USER_DATA']['PERSONAL_GENDER_TEXT'] ?? '';
                            } elseif ($fieldCode === 'PERSONAL_BIRTHDAY' && !empty($value)) {
                                echo $arResult['USER_DATA']['PERSONAL_BIRTHDAY_FORMATTED'] ?? '';
                            } else {
                                echo $value ?: Loc::getMessage('XILLIX_USER_PROFILE_NOT_SPECIFIED');
                            }
                            ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($arResult['UF_FIELDS'] as $fieldCode => $fieldInfo): ?>
                <div class="user-profile-field">
                    <span class="user-profile-label"><?= $fieldInfo['NAME'] ?></span>
                    <div class="user-profile-value">
                        <span class="user-profile-text">
                            <?= $component->getUfFieldDisplayValue($fieldInfo, $arResult['USER_DATA'][$fieldCode] ?? '') ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($arResult['ALLOW_EDIT']): ?>
            <div class="user-profile-actions">
                <button type="button" class="btn user-profile-edit-btn" onclick="toggleEditMode(true)">
                    <?= Loc::getMessage('XILLIX_USER_PROFILE_EDIT_BUTTON') ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Режим редактирования -->
    <?php if ($arResult['ALLOW_EDIT']): ?>
        <div class="user-profile-edit"
             id="userProfileEdit" <?= !$arResult['IS_EDIT_MODE'] ? 'style="display:none;"' : '' ?>>
            <form method="post" enctype="multipart/form-data" class="user-profile-form">
                <?= bitrix_sessid_post() ?>

                <div class="user-profile-fields">
                    <?php if ($arResult['SHOW_AVATAR'] && isset($arResult['FIELDS']['PERSONAL_PHOTO'])): ?>
                        <div class="user-profile-field user-profile-field-photo">
                            <span class="user-profile-label"><?= $arResult['FIELDS']['PERSONAL_PHOTO'] ?></span>
                            <div class="user-profile-photo-container">
                                <?php if (!empty($arResult['USER_DATA']['PERSONAL_PHOTO_SRC'])): ?>
                                    <img src="<?= $arResult['USER_DATA']['PERSONAL_PHOTO_SRC'] ?>"
                                         alt="<?= Loc::getMessage('XILLIX_USER_PROFILE_AVATAR_ALT') ?>"
                                         class="user-profile-photo">
                                <?php else: ?>
                                    <div class="user-profile-photo-placeholder">
                                        <?= Loc::getMessage('XILLIX_USER_PROFILE_NO_PHOTO') ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$component->isFieldReadonly('PERSONAL_PHOTO')): ?>
                                    <input type="file" name="PERSONAL_PHOTO" class="user-profile-file-input">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($arResult['FIELDS'] as $fieldCode => $fieldName): ?>
                        <?php if ($fieldCode === 'PERSONAL_PHOTO') continue; ?>

                        <div class="user-profile-field">
                            <span class="user-profile-label"><?= $fieldName ?></span>
                            <div class="user-profile-value">
                                <?php if ($component->isFieldReadonly($fieldCode)): ?>
                                    <!-- Поле только для чтения -->
                                    <div class="user-profile-readonly-value">
                                        <?php
                                        $value = $arResult['USER_DATA'][$fieldCode] ?? '';
                                        if ($fieldCode === 'PERSONAL_GENDER') {
                                            echo $arResult['USER_DATA']['PERSONAL_GENDER_TEXT'] ?? '';
                                        } elseif ($fieldCode === 'PERSONAL_BIRTHDAY' && !empty($value)) {
                                            echo $arResult['USER_DATA']['PERSONAL_BIRTHDAY_FORMATTED'] ?? '';
                                        } else {
                                            echo $value ?: Loc::getMessage('XILLIX_USER_PROFILE_NOT_SPECIFIED');
                                        }
                                        ?>
                                    </div>
                                    <input type="hidden" name="<?= $fieldCode ?>"
                                           value="<?= htmlspecialcharsbx($arResult['USER_DATA'][$fieldCode] ?? '') ?>">
                                <?php else: ?>
                                    <!-- Редактируемое поле -->
                                    <?php if ($fieldCode === 'PERSONAL_GENDER'): ?>
                                        <select name="<?= $fieldCode ?>"
                                                class="user-profile-input <?= isset($arResult['FORM_ERRORS'][$fieldCode]) ? 'user-profile-input-error' : '' ?>">
                                            <option value=""><?= Loc::getMessage('XILLIX_USER_PROFILE_NOT_SELECTED') ?></option>
                                            <option value="M" <?= ($arResult['USER_DATA'][$fieldCode] ?? '') === 'M' ? 'selected' : '' ?>>
                                                <?= Loc::getMessage('XILLIX_USER_PROFILE_GENDER_M') ?>
                                            </option>
                                            <option value="F" <?= ($arResult['USER_DATA'][$fieldCode] ?? '') === 'F' ? 'selected' : '' ?>>
                                                <?= Loc::getMessage('XILLIX_USER_PROFILE_GENDER_F') ?>
                                            </option>
                                        </select>
                                    <?php elseif ($fieldCode === 'PERSONAL_BIRTHDAY'): ?>
                                        <input type="date"
                                               name="<?= $fieldCode ?>"
                                               value="<?= $arResult['USER_DATA'][$fieldCode] ?? '' ?>"
                                               class="user-profile-input <?= isset($arResult['FORM_ERRORS'][$fieldCode]) ? 'user-profile-input-error' : '' ?>">
                                    <?php elseif ($fieldCode === 'EMAIL'): ?>
                                        <input type="email"
                                               name="<?= $fieldCode ?>"
                                               value="<?= $arResult['USER_DATA'][$fieldCode] ?? '' ?>"
                                               class="user-profile-input <?= isset($arResult['FORM_ERRORS'][$fieldCode]) ? 'user-profile-input-error' : '' ?>"
                                            <?= in_array($fieldCode, ['NAME', 'EMAIL']) ? 'required' : '' ?>>
                                    <?php else: ?>
                                        <input type="text"
                                               name="<?= $fieldCode ?>"
                                               value="<?= $arResult['USER_DATA'][$fieldCode] ?? '' ?>"
                                               class="user-profile-input <?= isset($arResult['FORM_ERRORS'][$fieldCode]) ? 'user-profile-input-error' : '' ?>"
                                            <?= $fieldCode === 'NAME' ? 'required' : '' ?>>
                                    <?php endif; ?>

                                    <?php if (isset($arResult['FORM_ERRORS'][$fieldCode])): ?>
                                        <div class="user-profile-field-error"><?= $arResult['FORM_ERRORS'][$fieldCode] ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($arResult['UF_FIELDS'] as $fieldCode => $fieldInfo): ?>
                        <div class="user-profile-field">
                            <span class="user-profile-label"><?= $fieldInfo['NAME'] ?></span>
                            <div class="user-profile-value">
                                <?php if ($component->isFieldReadonly($fieldCode)): ?>
                                    <!-- UF-поле только для чтения -->
                                    <div class="user-profile-readonly-value">
                                        <?= $component->getUfFieldDisplayValue($fieldInfo, $arResult['USER_DATA'][$fieldCode] ?? '') ?>
                                    </div>
                                    <input type="hidden" name="<?= $fieldCode ?>"
                                           value="<?= htmlspecialcharsbx($arResult['USER_DATA'][$fieldCode] ?? '') ?>">
                                <?php else: ?>
                                    <!-- Редактируемое UF-поле -->
                                    <?= $component->getUfFieldInput($fieldInfo, $arResult['USER_DATA'][$fieldCode] ?? '', $fieldCode) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="user-profile-actions">
                    <button type="submit" class="btn user-profile-submit">
                        <?= Loc::getMessage('XILLIX_USER_PROFILE_SAVE_BUTTON') ?>
                    </button>
                    <button type="button" class="btn btn-white user-profile-cancel" onclick="toggleEditMode(false)">
                        <?= Loc::getMessage('XILLIX_USER_PROFILE_CANCEL_BUTTON') ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleEditMode(showEdit) {
        const viewMode = document.getElementById('userProfileView');
        const editMode = document.getElementById('userProfileEdit');

        if (showEdit) {
            viewMode.style.display = 'none';
            if (editMode) editMode.style.display = 'block';
        } else {
            if (editMode) editMode.style.display = 'none';
            viewMode.style.display = 'block';

            // Сбрасываем ошибки при выходе из режима редактирования
            const errorInputs = document.querySelectorAll('.user-profile-input-error');
            errorInputs.forEach(input => {
                input.classList.remove('user-profile-input-error');
            });
        }
    }

    <?php if ($arResult['IS_EDIT_MODE']): ?>
    // Если есть ошибки, показываем режим редактирования
    document.addEventListener('DOMContentLoaded', function () {
        toggleEditMode(true);
    });
    <?php endif; ?>
</script>