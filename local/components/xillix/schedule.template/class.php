<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Xillix\TeacherScheduleManager;

class XillixScheduleTemplateComponent extends CBitrixComponent implements Controllerable
{
    /**
     * Конфигурация AJAX действий
     */
    public function configureActions()
    {
        return [
            'getTemplate' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new Bitrix\Main\Engine\ActionFilter\Authentication(),
                ],
            ],
            'saveTemplate' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new Bitrix\Main\Engine\ActionFilter\Authentication(),
                ],
            ],
            'clearTemplate' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new Bitrix\Main\Engine\ActionFilter\Authentication(),
                ],
            ],
            'saveTimezone' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new Bitrix\Main\Engine\ActionFilter\Authentication(),
                ],
            ],
        ];
    }

    /**
     * Получить шаблон расписания
     */
    public function getTemplateAction($dayOnly = true)
    {
        $teacherId = $this->getTeacherId();

        if (!$teacherId) {
            return ['success' => false, 'error' => 'Invalid teacher ID'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        $template = TeacherScheduleManager::getTeacherScheduleTemplate($teacherId, $dayOnly);

        return [
            'success' => true,
            'template' => $template
        ];
    }

    /**
     * Сохранить шаблон расписания
     */
    public function saveTemplateAction($slots)
    {
        $teacherId = $this->getTeacherId();

        if (!$teacherId) {
            return ['success' => false, 'error' => 'Invalid teacher ID'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        $result = TeacherScheduleManager::saveTeacherScheduleTemplate($teacherId, $slots);

        if ($result) {
            return ['success' => true, 'message' => 'Шаблон расписания сохранен'];
        } else {
            return ['success' => false, 'error' => 'Ошибка сохранения'];
        }
    }

    /**
     * Очистить шаблон расписания
     */
    public function clearTemplateAction()
    {
        $teacherId = $this->getTeacherId();

        if (!$teacherId) {
            return ['success' => false, 'error' => 'Invalid teacher ID'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        $result = TeacherScheduleManager::clearTeacherScheduleTemplate($teacherId);

        if ($result) {
            return ['success' => true, 'message' => 'Шаблон расписания очищен'];
        } else {
            return ['success' => false, 'error' => 'Ошибка очистки'];
        }
    }

    /**
     * Получить ID преподавателя с проверкой прав
     */
    private function getTeacherId()
    {
        global $USER;

        $teacherId = (int)($this->arParams['TEACHER_ID'] ?? $USER->GetID());

        if ($teacherId <= 0) {
            return false;
        }

        // Проверяем что пользователь имеет доступ к этому расписанию
        if ($USER->GetID() != $teacherId && !$USER->IsAdmin()) {
            return false;
        }

        return $teacherId;
    }

    /**
     * Сохранить часовой пояс
     */
    public function saveTimezoneAction($timezone)
    {
        $teacherId = $this->getTeacherId();

        if (!$teacherId) {
            return ['success' => false, 'error' => 'Invalid teacher ID'];
        }

        if (empty($timezone)) {
            return ['success' => false, 'error' => 'Timezone is required'];
        }

        if (!Loader::includeModule('xillix')) {
            return ['success' => false, 'error' => 'Module xillix not installed'];
        }

        $result = TeacherScheduleManager::setTeacherTimezone($teacherId, $timezone);

        return ['success' => (bool)$result];
    }

    /**
     * Стандартный метод выполнения компонента
     */
    public function executeComponent()
    {
        // Подключаем модуль
        if (!Loader::includeModule('xillix')) {
            ShowError('Module xillix not installed');
            return;
        }

        // Инициализация параметров
        $this->arParams['TEACHER_ID'] = (int)$this->arParams['TEACHER_ID'];
        $this->arParams['DEFAULT_DAY_ONLY'] = $this->arParams['DEFAULT_DAY_ONLY'] !== 'N';

        if ($this->arParams['TEACHER_ID'] <= 0) {
            global $USER;
            $this->arParams['TEACHER_ID'] = $USER->GetID();
        }

        if ($this->arParams['TEACHER_ID'] <= 0) {
            ShowError('Invalid teacher ID');
            return;
        }

        // Проверяем доступ
        global $USER;
        if ($USER->GetID() != $this->arParams['TEACHER_ID'] && !$USER->IsAdmin()) {
            ShowError('Access denied');
            return;
        }

        // Подготавливаем результат
        $this->arResult = [
            'TEACHER_ID' => $this->arParams['TEACHER_ID'],
            'DEFAULT_DAY_ONLY' => $this->arParams['DEFAULT_DAY_ONLY'],
            'SIGNED_PARAMS' => $this->getSignedParameters(),
            'TIMEZONES_SORTED' => TeacherScheduleManager::getTimezonesSorted(),
            'CURRENT_TIMEZONE' => TeacherScheduleManager::getTeacherTimezone($this->arParams['TEACHER_ID']),
        ];

        $this->IncludeComponentTemplate();
    }
}