<?php

namespace Xillix\Videoconf;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class VideoconfLessonsComponent extends \CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        return $params;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('xillix') || !Loader::includeModule('highloadblock')) {
            ShowError(Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_MODULE_MISSING'));
            return;
        }

        global $USER;
        if (!$USER->IsAuthorized()) {
            ShowError(Loc::getMessage('XILLIX_VIDEOCONF_LESSONS_NEED_AUTH'));
            return;
        }

        $userId = $USER->GetID();
        $mode = $this->getUserMode($userId);
        if (!$mode) {
            $this->arResult['LESSONS'] = [];
            $this->IncludeComponentTemplate();
            return;
        }

        $lessons = $this->getLessonsWithRecordings($userId, $mode);

        $this->arResult = [
            'LESSONS' => $lessons,
            'MODE' => $mode,
            'DETAIL' => !empty($_REQUEST['lesson_id']),
            'LESSON_ID' => (int)$_REQUEST['lesson_id']
        ];

        if ($this->arResult['DETAIL']) {
            $this->SetTemplateName('detail');
        }

        $this->IncludeComponentTemplate();
    }

    private function getUserMode($userId)
    {
        $entity = \Xillix\TeacherScheduleManager::getEntity();
        $hasLessons = $entity::getList(['filter' => ['=UF_STUDENT_ID' => $userId], 'limit' => 1])->fetch();
        if ($hasLessons) return 'student';

        $hasSlots = $entity::getList(['filter' => ['=UF_TEACHER_ID' => $userId], 'limit' => 1])->fetch();
        return $hasSlots ? 'teacher' : false;
    }

    private function getLessonsWithRecordings($userId, $mode)
    {
        $entity = \Xillix\TeacherScheduleManager::getEntity();
        if (!$entity) return [];

        $filter = [
            '!UF_VIDEO_LESSON_LINK' => false,
            '!=UF_VIDEO_LESSON_LINK' => ''
        ];

        if ($mode === 'student') {
            $filter['=UF_STUDENT_ID'] = $userId;
        } else {
            $filter['=UF_TEACHER_ID'] = $userId;
        }

        $lessons = $entity::getList([
            'filter' => $filter,
            'select' => ['ID', 'UF_DATE', 'UF_START_TIME', 'UF_SUBJECT', 'UF_TEACHER_ID', 'UF_STUDENT_ID', 'UF_VIDEO_LESSON_LINK'],
            'order' => ['UF_DATE' => 'DESC', 'UF_START_TIME' => 'DESC']
        ])->fetchAll();

        foreach ($lessons as &$lesson) {
            $userField = ($mode === 'student') ? 'UF_TEACHER_ID' : 'UF_STUDENT_ID';
            $user = UserTable::getList([
                'filter' => ['ID' => $lesson[$userField]],
                'select' => ['NAME', 'LAST_NAME', 'LOGIN']
            ])->fetch();
            $lesson['PERSON_NAME'] = trim($user['NAME'] . ' ' . $user['LAST_NAME']) ?: $user['LOGIN'];
        }

        return $lessons;
    }
}
