<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */

$this->addExternalCSS($this->GetFolder() . '/style.css');
$this->addExternalJS($this->GetFolder() . '/script.js');

?>

<div class="student-detail">
    <?php if ($arResult['CURRENT_STUDENT']): ?>
        <div class="student-detail-header">
            <a href="<?= $arParams['LIST_URL'] ?>" class="back-link">
                ← Назад к списку
            </a>
        </div>

        <div class="student-detail-content">
            <div class="student-main-info">
                <div class="student-avatar-large">
                    <?php
                    $avatar = CFile::GetPath($arResult['CURRENT_STUDENT']['PERSONAL_PHOTO'] ?? '');
                    if (!$avatar) {
                        $avatar = SITE_TEMPLATE_PATH . '/img/no photo.png';
                    }
                    ?>
                    <img src="<?= $avatar ?>"
                         alt="<?= htmlspecialcharsbx($arResult['CURRENT_STUDENT']['STUDENT_NAME']) ?>" width="120"
                         height="120">
                </div>

                <div class="student-info-detail">
                    <h1><?= htmlspecialcharsbx($arResult['CURRENT_STUDENT']['STUDENT_NAME']) ?></h1>
                    <div class="student-contacts">
                    </div>
                </div>
            </div>

            <div class="student-notes-section">
                <h3>Заметки о ученике</h3>
                <form id="studentNotesForm" class="notes-form">
                    <?= bitrix_sessid_post() ?>
                    <input type="hidden" name="student_id" value="<?= $arResult['CURRENT_STUDENT']['STUDENT_ID'] ?>">

                    <div class="form-group">
                        <textarea
                                id="studentNotes"
                                name="notes"
                                placeholder="Добавьте заметки об ученике..."
                                rows="8"
                        ><?= htmlspecialcharsbx($arResult['CURRENT_STUDENT']['NOTES']) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="saveNotesBtn">
                            Сохранить заметки
                        </button>
                        <span id="saveStatus" class="save-status"></span>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="student-not-found">
            <h1>Ученик не найден</h1>
            <p>Запрошенный ученик не существует или у вас нет к нему доступа.</p>
            <a href="<?= $arParams['DETAIL_URL'] ?>" class="btn btn-primary">
                Вернуться к списку
            </a>
        </div>
    <?php endif; ?>
</div>