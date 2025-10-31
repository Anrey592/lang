<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Xillix\TeacherScheduleManager;

class TeacherStudentsComponent extends CBitrixComponent implements Controllerable
{
    public function configureActions()
    {
        return [
            'updateStudentNotes' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod(['POST']),
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams["TEACHER_ID"] = (int)$arParams["TEACHER_ID"];
        if ($arParams["TEACHER_ID"] <= 0) {
            global $USER;
            $arParams["TEACHER_ID"] = $USER->GetID();
        }

        $arParams["DETAIL_URL"] = trim($arParams["DETAIL_URL"]);
        $arParams["SET_TITLE"] = $arParams["SET_TITLE"] !== "N";

        return $arParams;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('xillix')) {
            ShowError('Модуль Xillix не установлен');
            return;
        }

        global $USER;

        // Проверяем, что текущий пользователь - преподаватель
        if ($USER->GetID() != $this->arParams["TEACHER_ID"]) {
            ShowError('Доступ запрещен');
            return;
        }

        // Определяем режим отображения
        $this->arResult['DISPLAY_MODE'] = $this->arParams['MODE'];

        if ($this->arParams['MODE'] == 'detail') {
            // Получаем ID ученика из URL
            $studentId = $this->request->get("student_id");
            if ($studentId) {
                $this->arResult["CURRENT_STUDENT"] = $this->getStudentDetails($studentId);
            }
        } else {
            // Получаем учеников преподавателя
            $this->arResult["STUDENTS"] = TeacherScheduleManager::getTeacherStudentsDetailed($this->arParams["TEACHER_ID"]);
            $this->arResult["STUDENTS_COUNT"] = count($this->arResult["STUDENTS"]);

            // Устанавливаем заголовок
            if ($this->arParams["SET_TITLE"]) {
                global $APPLICATION;
                $APPLICATION->SetTitle("Мои ученики (" . $this->arResult["STUDENTS_COUNT"] . ")");
            }
        }

        $this->IncludeComponentTemplate();
    }

    private function getStudentDetails($studentId)
    {
        $student = TeacherScheduleManager::getTeacherStudentDetailed($this->arParams["TEACHER_ID"], $studentId);

        if ($student) {
            // Получаем заметки о ученике
            $student["NOTES"] = TeacherScheduleManager::getStudentNotes(
                $this->arParams["TEACHER_ID"],
                $studentId
            );
            return $student;
        }

        return null;
    }

    public function updateStudentNotesAction($studentId, $notes)
    {
        global $USER;

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module not installed'];
        }

        $teacherId = $USER->GetID();
        $result = TeacherScheduleManager::updateStudentNotes($teacherId, $studentId, $notes);

        return [
            'success' => $result,
            'message' => $result ? 'Заметки сохранены' : 'Ошибка сохранения'
        ];
    }
}