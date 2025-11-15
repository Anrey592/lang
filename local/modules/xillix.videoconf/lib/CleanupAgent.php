<?php

namespace Xillix\Videoconf;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

class CleanupAgent
{
    /**
     * Удаляет конференции, завершившиеся более 12 часов назад
     *
     * @return string — возвращает свой же вызов для повторного запуска
     */
    public static function deleteOldConferences()
    {
        if (!Loader::includeModule('xillix.videoconf')) {
            return 'Xillix\Videoconf\CleanupAgent::deleteOldConferences();';
        }

        try {
            $tc = new TrueConfManager();
            $conferences = $tc->getConferences(); // получает все конференции

            if (empty($conferences['conferences'])) {
                return 'Xillix\Videoconf\CleanupAgent::deleteOldConferences();';
            }

            $twelveHoursAgo = time() - 12 * 3600;

            foreach ($conferences['conferences'] as $conf) {
                // Пропускаем активные или незапущенные
                if (!isset($conf['state']) || $conf['state'] !== 'stopped') {
                    continue;
                }

                // Пропускаем без записи (если не нужно удалять такие)
                // if (empty($conf['stream_recording_state'])) continue;

                // Получаем время завершения из created_at + duration
                $createdAt = $conf['created_at'] ?? 0;
                $duration = $conf['schedule']['duration'] ?? 0;
                $endTime = $createdAt + $duration;

                // Если конференция завершилась более 12 часов назад — удаляем
                if ($endTime > 0 && $endTime < $twelveHoursAgo) {
                    $tc->deleteConference($conf['id']);
                }
            }
        } catch (\Exception $e) {
            AddMessage2Log('Ошибка при удалении старых конференций: ', $e->getMessage());
        }

        return 'Xillix\Videoconf\CleanupAgent::deleteOldConferences();';
    }
}