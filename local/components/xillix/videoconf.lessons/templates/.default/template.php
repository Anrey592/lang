<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Main\Localization\Loc;

if (empty($arResult['LESSONS'])): ?>
    <div class="schedule-table-container">
        <div class="no-lessons-message">
            <?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_NO_LESSONS') ?>
        </div>
    </div>
<?php else: ?>
    <div class="schedule-table-container">
        <table class="schedule-table lessons-list-table">
            <thead>
            <tr>
                <th><?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_DATE') ?></th>
                <th><?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_SUBJECT') ?></th>
                <th><?= Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_PERSON') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($arResult['LESSONS'] as $lesson): ?>
                <tr>
                    <td>
                        <?= \Bitrix\Main\Type\DateTime::createFromPhp(
                            new DateTime($lesson['UF_DATE'] . ' ' . substr($lesson['UF_START_TIME'], -8))
                        )->toString() ?>
                    </td>
                    <td>
                        <a href="?lesson_id=<?= $lesson['ID'] ?>"><?= htmlspecialcharsbx($lesson['UF_SUBJECT']) ?></a>
                    </td>
                    <td><?= htmlspecialcharsbx($lesson['PERSON_NAME']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>