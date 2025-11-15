<?php

namespace Xillix\Videoconf;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

class Agent
{
    /**
     * Агент: проверяет новые записи в TrueConf и обновляет UF_VIDEO_LESSON_LINK
     */
    public static function processRecordings()
    {
        if (!Loader::includeModule('xillix') || !Loader::includeModule('xillix.videoconf')) {
            return 'Xillix\Videoconf\Agent::processRecordings();';
        }

        try {
            $tc = new TrueConfManager();
            $recordings = $tc->getRecordingsList();

            if (empty($recordings['list'])) {
                return 'Xillix\Videoconf\Agent::processRecordings();';
            }

            foreach ($recordings['list'] as $rec) {
                $conferenceId = $rec['conference_id'];
                $recordingId = $rec['id'];

                // Обновляем HL-блок: сохраняем ссылку с ID записи '/bitrix/tools/xillix.videoconf/recording_proxy.php?id='
                $publicUrl = $recordingId;
                self::updateHLRecordingLink($conferenceId, $publicUrl);
            }

        } catch (\Exception $e) {
            \CEventLog::Add([
                'SEVERITY' => 'ERROR',
                'AUDIT_TYPE_ID' => 'TRUECONF_RECORDING_AGENT',
                'MODULE_ID' => 'xillix.videoconf',
                'DESCRIPTION' => 'Ошибка агента обработки записей: ' . $e->getMessage()
            ]);
        }

        return 'Xillix\Videoconf\Agent::processRecordings();';
    }

    private static function updateHLRecordingLink($conferenceId, $videoUrl)
    {
        $entity = \Xillix\TeacherScheduleManager::getEntity();
        if (!$entity) return;

        $slots = $entity::getList([
            'filter' => [
                'LOGIC' => 'OR',
                [
                    '=UF_SCHEDULED_LESSON' => '%/webrtc/' . $conferenceId,
                ],
                [
                    '=UF_SCHEDULED_LESSON' => '%/c/' . $conferenceId,
                ],
            ],
            'select' => ['ID']
        ])->fetchAll();

        foreach ($slots as $slot) {
            $entity::update($slot['ID'], [
                'UF_VIDEO_LESSON_LINK' => $videoUrl
            ]);
        }
    }
}
