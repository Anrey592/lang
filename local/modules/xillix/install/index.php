<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

class xillix extends CModule
{
    public $MODULE_ID = "xillix";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = "Y";

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("XILLIX_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("XILLIX_MODULE_DESC");
        $this->PARTNER_NAME = Loc::getMessage("XILLIX_COMPANY");
        $this->PARTNER_URI = 'https://xillix.ru/';
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if (CheckVersion(ModuleManager::getVersion('main'), '14.00.00')) {
            ModuleManager::registerModule($this->MODULE_ID);
            if (Loader::includeModule($this->MODULE_ID)) {
                $this->installFiles();
                $this->installDB();
                $this->installEvents();
                $this->installAgents();
            } else {
                throw new SystemException(Loc::getMessage("VASOFT_LIKEIT_MODULE_REGISTER_ERROR"));
            }
        } else {
            CAdminMessage::showMessage(
                Loc::getMessage('XILLIX_INSTALL_ERROR')
            );
            return;
        }

        $APPLICATION->includeAdminFile(
            Loc::getMessage('XILLIX_INSTALL_TITLE') . ' «' . Loc::getMessage('XILLIX_NAME') . '»',
            __DIR__ . '/step1.php'
        );
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $saveData = trim($request->get('savedata')) === 'Y';

        Loader::includeModule($this->MODULE_ID);

        if (!$saveData) {
            $this->unInstallDB();
        }
        $this->uninstallFiles();
        $this->uninstallEvents();
        $this->uninstallAgents();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->includeAdminFile(
            Loc::getMessage('XILLIX_UNINSTALL_TITLE') . ' «' . Loc::getMessage('XILLIX_NAME') . '»',
            __DIR__ . '/step2.php'
        );
    }

    public function uninstallAgents()
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    public function installAgents()
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);

        \CAgent::AddAgent(
            "Xillix\\ReminderAgent::sendLessonReminders();",
            "xillix",
            "N",
            3600, // каждые час
            "",
            "Y",
            ""
        );
    }


    public function InstallDB()
    {
        // Подключаем необходимые модули
        if (!Loader::includeModule('highloadblock')) {
            $GLOBALS['errors'] = 'Модуль Highloadblock не установлен';
            return false;
        }

        // Создание Highload-блока для расписания
        $hlblockData = [
            'NAME' => 'XillixSchedule',
            'TABLE_NAME' => 'b_hlbd_xillix_schedule',
        ];

        $result = Bitrix\Highloadblock\HighloadBlockTable::add($hlblockData);

        if ($result->isSuccess()) {
            $hlblockId = $result->getId();

            // Создание полей Highload-блока
            $userTypeEntity = new CUserTypeEntity();

            $fields = [
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_TEACHER_ID',
                    'USER_TYPE_ID' => 'integer',
                    'XML_ID' => 'TEACHER_ID',
                    'SORT' => 100,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'ID преподавателя', 'en' => 'Teacher ID'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_DATE',
                    'USER_TYPE_ID' => 'date',
                    'XML_ID' => 'DATE',
                    'SORT' => 200,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Дата занятия', 'en' => 'Lesson Date'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_START_TIME',
                    'USER_TYPE_ID' => 'datetime',
                    'XML_ID' => 'START_TIME',
                    'SORT' => 300,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Время начала', 'en' => 'Start Time'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_END_TIME',
                    'USER_TYPE_ID' => 'datetime',
                    'XML_ID' => 'END_TIME',
                    'SORT' => 400,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Время окончания', 'en' => 'End Time'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_SUBJECT',
                    'USER_TYPE_ID' => 'string',
                    'XML_ID' => 'SUBJECT',
                    'SORT' => 500,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'Y',
                    'EDIT_FORM_LABEL' => ['ru' => 'Предмет', 'en' => 'Subject'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_STATUS',
                    'USER_TYPE_ID' => 'enumeration',
                    'XML_ID' => 'STATUS',
                    'SORT' => 600,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Статус', 'en' => 'Status'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_STUDENT_ID',
                    'USER_TYPE_ID' => 'integer',
                    'XML_ID' => 'STUDENT_ID',
                    'SORT' => 700,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'ID студента', 'en' => 'Student ID'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_NOTES',
                    'USER_TYPE_ID' => 'text',
                    'XML_ID' => 'NOTES',
                    'SORT' => 800,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'N',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Заметки', 'en' => 'Notes'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_REMINDER_SENT',
                    'USER_TYPE_ID' => 'boolean',
                    'XML_ID' => 'REMINDER_SENT',
                    'SORT' => 1000,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Напоминание отправлено', 'en' => 'Reminder sent'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                    'FIELD_NAME' => 'UF_TIMEZONE',
                    'USER_TYPE_ID' => 'string',
                    'XML_ID' => 'RECORD_TIMEZONE',
                    'SORT' => 900,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Часовой пояс записи', 'en' => 'Record Timezone'],
                ]
            ];

            foreach ($fields as $field) {
                $userTypeEntity->Add($field);
            }

            $enumField = CUserTypeEntity::GetList([], [
                'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                'FIELD_NAME' => 'UF_STATUS'
            ])->Fetch();

            if ($enumField) {
                $enum = new CUserFieldEnum();
                $enum->SetEnumValues($enumField['ID'], [
                    'n0' => [
                        'XML_ID' => 'free',
                        'VALUE' => 'Открыт',
                        'DEF' => 'Y',
                        'SORT' => 100
                    ],
                    'n1' => [
                        'XML_ID' => 'blocked',
                        'VALUE' => 'Заблокирован',
                        'DEF' => 'N',
                        'SORT' => 200
                    ],
                    'n2' => [
                        'XML_ID' => 'canceled',
                        'VALUE' => 'Отменён',
                        'DEF' => 'N',
                        'SORT' => 300
                    ]
                ]);
            }
        } else {
            $GLOBALS['errors'] = implode(', ', $result->getErrorMessages());
            return false;
        }

        // Добавление пользовательского поля для часового пояса преподавателя
        $userTypeEntity = new CUserTypeEntity();

        $userTypeEntity->Add([
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_TIMEZONE',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => 'TEACHER_TIMEZONE',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'I',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'EDIT_FORM_LABEL' => ['ru' => 'Часовой пояс пользователя', 'en' => 'User Timezone'],
        ]);

        // Создание Highload-блока для шаблонов расписания
        $hlblockTemplateData = [
            'NAME' => 'XillixScheduleTemplate',
            'TABLE_NAME' => 'b_hlbd_xillix_schedule_template',
        ];

        $result = Bitrix\Highloadblock\HighloadBlockTable::add($hlblockTemplateData);

        if ($result->isSuccess()) {
            $hlblockTemplateId = $result->getId();

            // Создание полей Highload-блока для шаблонов
            $templateFields = [
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTemplateId,
                    'FIELD_NAME' => 'UF_TEACHER_ID',
                    'USER_TYPE_ID' => 'integer',
                    'XML_ID' => 'TEACHER_ID',
                    'SORT' => 100,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'ID преподавателя', 'en' => 'Teacher ID'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTemplateId,
                    'FIELD_NAME' => 'UF_DAY_OF_WEEK',
                    'USER_TYPE_ID' => 'integer',
                    'XML_ID' => 'DAY_OF_WEEK',
                    'SORT' => 200,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'День недели', 'en' => 'Day of week'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTemplateId,
                    'FIELD_NAME' => 'UF_START_TIME',
                    'USER_TYPE_ID' => 'string',
                    'XML_ID' => 'START_TIME',
                    'SORT' => 300,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Время начала', 'en' => 'Start Time'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTemplateId,
                    'FIELD_NAME' => 'UF_END_TIME',
                    'USER_TYPE_ID' => 'string',
                    'XML_ID' => 'END_TIME',
                    'SORT' => 400,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Время окончания', 'en' => 'End Time'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTemplateId,
                    'FIELD_NAME' => 'UF_IS_ACTIVE',
                    'USER_TYPE_ID' => 'boolean',
                    'XML_ID' => 'IS_ACTIVE',
                    'SORT' => 500,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Активен', 'en' => 'Is Active'],
                ]
            ];

            foreach ($templateFields as $field) {
                $userTypeEntity->Add($field);
            }
        }

        $hlblockTeacherStudentData = [
            'NAME' => 'XillixTeacherStudent',
            'TABLE_NAME' => 'b_hlbd_xillix_teacher_student',
        ];

        $result = Bitrix\Highloadblock\HighloadBlockTable::add($hlblockTeacherStudentData);

        if ($result->isSuccess()) {
            $hlblockTeacherStudentId = $result->getId();

            // Создание полей Highload-блока для связей
            $teacherStudentFields = [
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTeacherStudentId,
                    'FIELD_NAME' => 'UF_TEACHER_ID',
                    'USER_TYPE_ID' => 'integer',
                    'XML_ID' => 'TEACHER_ID',
                    'SORT' => 100,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'ID преподавателя', 'en' => 'Teacher ID'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTeacherStudentId,
                    'FIELD_NAME' => 'UF_STUDENT_ID',
                    'USER_TYPE_ID' => 'integer',
                    'XML_ID' => 'STUDENT_ID',
                    'SORT' => 200,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'ID ученика', 'en' => 'Student ID'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTeacherStudentId,
                    'FIELD_NAME' => 'UF_IS_ACTIVE',
                    'USER_TYPE_ID' => 'boolean',
                    'XML_ID' => 'IS_ACTIVE',
                    'SORT' => 300,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Активна', 'en' => 'Is Active'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTeacherStudentId,
                    'FIELD_NAME' => 'UF_CREATED_AT',
                    'USER_TYPE_ID' => 'datetime',
                    'XML_ID' => 'CREATED_AT',
                    'SORT' => 400,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Дата создания', 'en' => 'Created At'],
                ],
                [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlblockTeacherStudentId,
                    'FIELD_NAME' => 'UF_NOTES',
                    'USER_TYPE_ID' => 'string',
                    'XML_ID' => 'NOTES',
                    'SORT' => 500,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'N',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => 'Заметки о ученике', 'en' => 'Student Notes'],
                ]
            ];

            foreach ($teacherStudentFields as $field) {
                $userTypeEntity->Add($field);
            }
        }

        return true;
    }

    public function UnInstallDB()
    {
        // Удаление Highload-блока
        if (Loader::includeModule('highloadblock')) {
            $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList([
                'filter' => ['=NAME' => 'XillixSchedule']
            ])->fetch();

            if ($hlblock) {
                Bitrix\Highloadblock\HighloadBlockTable::delete($hlblock['ID']);
            }

            $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList([
                'filter' => ['=NAME' => 'XillixScheduleTemplate']
            ])->fetch();

            if ($hlblock) {
                Bitrix\Highloadblock\HighloadBlockTable::delete($hlblock['ID']);
            }
        }

        // Удаление пользовательского поля
        $userTypeEntity = new CUserTypeEntity();
        $field = $userTypeEntity->GetList([], ['FIELD_NAME' => 'UF_TIMEZONE'])->Fetch();

        if ($field) {
            $userTypeEntity->Delete($field['ID']);
        }

        if (Loader::includeModule('highloadblock')) {
            $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList([
                'filter' => ['=NAME' => 'XillixTeacherStudent']
            ])->fetch();

            if ($hlblock) {
                Bitrix\Highloadblock\HighloadBlockTable::delete($hlblock['ID']);
            }
        }

        // Удаление настроек модуля
        Option::delete($this->MODULE_ID);

        return true;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . "/components",
            $_SERVER["DOCUMENT_ROOT"] . "/local/components/",
            true, true
        );

        CopyDirFiles(
            __DIR__ . "/admin",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/",
            true, true
        );
        return true;
    }

    public
    function UnInstallFiles()
    {
        DeleteDirFilesEx("/local/components/xillix");
        DeleteDirFilesEx("/bitrix/admin/xillix");
//        DeleteDirFilesEx("/bitrix/modules/" . $this->MODULE_ID . "/options.php");
        return true;
    }
}