<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */

$this->addExternalCSS($this->GetFolder() . '/style.css');
?>

<div class="teacher-students">
    <div class="students-header">
        <h1>Мои ученики</h1>
        <div class="students-stats">
            <span class="students-count">Всего учеников: <?= $arResult['STUDENTS_COUNT'] ?></span>
        </div>
    </div>

    <?php if (empty($arResult['STUDENTS'])): ?>
        <div class="students-empty">
            <p>У вас пока нет учеников</p>
        </div>
    <?php else: ?>
        <div class="students-list">
            <?php foreach ($arResult['STUDENTS'] as $student): ?>
                <div class="student-card" data-student-id="<?= $student['STUDENT_ID'] ?>">
                    <div class="student-avatar">
                        <?php
                        $avatar = CFile::GetPath($student['PERSONAL_PHOTO'] ?? '');
                        if (!$avatar) {
                            $avatar = SITE_TEMPLATE_PATH . '/img/no photo.png';
                        }
                        ?>
                        <img src="<?= $avatar ?>" alt="<?= htmlspecialcharsbx($student['STUDENT_NAME']) ?>" width="60"
                             height="60">
                    </div>

                    <div class="student-info">
                        <h3 class="student-name"><?= htmlspecialcharsbx($student['STUDENT_NAME']) ?></h3>
                        <p class="student-email"><?= htmlspecialcharsbx($student['STUDENT_EMAIL']) ?></p>
                        <?php if ($student['STUDENT_PHONE']): ?>
                            <p class="student-phone"><?= htmlspecialcharsbx($student['STUDENT_PHONE']) ?></p>
                        <?php endif; ?>
                        <p class="student-added">
                            Добавлен: <?= FormatDate('d.m.Y', MakeTimeStamp($student['CREATED_AT'])) ?></p>
                    </div>

                    <div class="student-actions">
                        <?php if ($arParams['DETAIL_URL']): ?>
                            <a href="<?= $arParams['DETAIL_URL'] ?>?student_id=<?= $student['STUDENT_ID'] ?>"
                               class="btn btn-primary">
                                Подробнее
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>