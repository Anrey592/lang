<?
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Главная');
?>
    <section class="for-whom container">
        <h2>Английский для <span class="color-accent">любого возраста</span> с фокусом на знаниях</h2>
        <div class="for-whom-content">
            <ul>
                <li>
                    <h3>Малышам</h3>
                    <div class="for-whom-items">
                        <ul>
                            <li><a href="">Английский для детей с нуля</a></li>
                            <li><a href="">Английский для малышей от 3 лет</a></li>
                            <li><a href="">Групповые занятия для детей</a></li>
                        </ul>
                    </div>
                    <a href="" class="btn btn-white">
                        <span class="btn-text">Подробнее</span>
                        <span class="btn-arrow"></span>
                    </a>
                </li>
                <li>
                    <h3>Школьникам</h3>
                    <div class="for-whom-items">
                        <ul>
                            <li><a href="">Английский с нуля</a></li>
                            <li><a href="">Подготовка к ОГЭ</a></li>
                            <li><a href="">Подготовка к ЕГЭ</a></li>
                            <li><a href="">Английский для подростков</a></li>
                        </ul>
                    </div>
                    <a href="" class="btn btn-white">
                        <span class="btn-text">Подробнее</span>
                        <span class="btn-arrow"></span>
                    </a>
                </li>
                <li>
                    <h3>Студентам</h3>
                    <div class="for-whom-items">
                        <ul>
                            <li><a href="">Английский для вуза и сессии</a></li>
                            <li><a href="">Английский для будущей карьеры</a></li>
                            <li><a href="">Международные экзамены</a></li>
                            <li><a href="">Английский для IT</a></li>
                            <li><a href="">Технический английский</a></li>
                        </ul>
                    </div>
                    <a href="" class="btn btn-white">
                        <span class="btn-text">Подробнее</span>
                        <span class="btn-arrow"></span>
                    </a>
                </li>
                <li>
                    <h3>Взрослым</h3>
                    <div class="for-whom-items">
                        <ul>
                            <li><a href="">Английский для начинающих</a></li>
                            <li><a href="">Бизнес-английский</a></li>
                            <li><a href="">Разговорный английский</a></li>
                            <li><a href="">Английский для менеджеров</a></li>
                            <li><a href="">Интенсивные курсы английского языка</a></li>
                        </ul>
                    </div>
                    <a href="" class="btn btn-white">
                        <span class="btn-text">Подробнее</span>
                        <span class="btn-arrow"></span>
                    </a>
                </li>
            </ul>
        </div>
    </section>
    <section class="format container">
        <h2>Выберите <span class="color-accent">формат</span> обучения</h2>
        <div class="format-content">
            <ul>
                <li>
                    <h3>Индивидуальные занятия</h3>
                    <p>Это персонализированный подход — уроки, проводятся один на один с преподавателем, полностью
                        ориентированные на ваши цели, уровень и темпы обучения.</p>
                    <a href="" class="btn btn-white">
                        <span class="btn-text">Подробнее</span>
                        <span class="btn-arrow"></span>
                    </a>
                </li>
                <li>
                    <h3>Групповые занятия</h3>
                    <p>Это занятия, где одновременно обучаются от 2 до 8 человек. Такой формат позволяет учиться в
                        компании и развивать навыки коммуникации.</p>
                    <a href="" class="btn btn-white">
                        <span class="btn-text">Подробнее</span>
                        <span class="btn-arrow"></span>
                    </a>
                </li>
                <li>
                    <h3>С носителем языка</h3>
                    <p>Погружение в языковую среду и постановка произношения</p>
                    <a href="" class="btn btn-white">
                        <span class="btn-text">Подробнее</span>
                        <span class="btn-arrow"></span>
                    </a>
                </li>
            </ul>
        </div>
    </section>
<?
$GLOBALS['filterSliderRepetitory'] = ['!=PREVIEW_PICTURE' => false];

$APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "slider",
    [
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "ADD_SECTIONS_CHAIN" => "N",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "AJAX_OPTION_HISTORY" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "CACHE_FILTER" => "N",
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "DISPLAY_BOTTOM_PAGER" => "N",
        "DISPLAY_DATE" => "N",
        "DISPLAY_NAME" => "Y",
        "DISPLAY_PICTURE" => "Y",
        "DISPLAY_PREVIEW_TEXT" => "N",
        "DISPLAY_TOP_PAGER" => "N",
        "FIELD_CODE" => [
            0 => "",
            1 => "",
        ],
        "FILTER_NAME" => "filterSliderRepetitory",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "IBLOCK_ID" => "3",
        "IBLOCK_TYPE" => "catalog",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "INCLUDE_SUBSECTIONS" => "N",
        "MESSAGE_404" => "",
        "NEWS_COUNT" => "50",
        "PAGER_BASE_LINK_ENABLE" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
        "PAGER_SHOW_ALL" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => ".default",
        "PAGER_TITLE" => "Новости",
        "PARENT_SECTION" => "",
        "PARENT_SECTION_CODE" => "",
        "PREVIEW_TRUNCATE_LEN" => "",
        "PROPERTY_CODE" => [
            0 => "WORK_EXP",
            1 => "",
        ],
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_STATUS_404" => "N",
        "SET_TITLE" => "N",
        "SHOW_404" => "N",
        "SORT_BY1" => "SORT",
        "SORT_BY2" => "ACTIVE_FROM",
        "SORT_ORDER1" => "ASC",
        "SORT_ORDER2" => "DESC",
        "STRICT_SECTION_CHECK" => "N",
        "COMPONENT_TEMPLATE" => "slider"
    ],
    false
); ?>
    <section class="about container">
        <div class="about-content">
            <div class="about-info">
                <div class="about-info-top">
                    <h2>Школа, созданная <span class="color-accent">преподавателями</span></h2>
                    <div class="about-desc">Работаем с 2011 года и знаем все об обучении английскому. Помогаем детям и
                        подросткам сохранять мотивацию и достигать результатов без стресса.
                    </div>
                </div>
                <div class="about-info-numbers">
                    <ul>
                        <li>
                            <div class="about-info-title">14 лет</div>
                            <p>преподаем английский язык</p>
                        </li>
                        <li>
                            <div class="about-info-title">34 000</div>
                            <p>студентов прошли обучение</p>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="about-img" style='background-image: url("<?= SITE_TEMPLATE_PATH ?>/img/about.png")'></div>
        </div>
    </section>
<? $APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "reviews-slider",
    array(
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "ADD_SECTIONS_CHAIN" => "N",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "AJAX_OPTION_HISTORY" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "CACHE_FILTER" => "N",
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "DISPLAY_BOTTOM_PAGER" => "N",
        "DISPLAY_DATE" => "N",
        "DISPLAY_NAME" => "Y",
        "DISPLAY_PICTURE" => "Y",
        "DISPLAY_PREVIEW_TEXT" => "Y",
        "DISPLAY_TOP_PAGER" => "N",
        "FIELD_CODE" => array("", ""),
        "FILTER_NAME" => "",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "IBLOCK_ID" => "5",
        "IBLOCK_TYPE" => "catalog",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "INCLUDE_SUBSECTIONS" => "N",
        "MESSAGE_404" => "",
        "NEWS_COUNT" => "50",
        "PAGER_BASE_LINK_ENABLE" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
        "PAGER_SHOW_ALL" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => ".default",
        "PAGER_TITLE" => "Новости",
        "PARENT_SECTION" => "",
        "PARENT_SECTION_CODE" => "",
        "PREVIEW_TRUNCATE_LEN" => "",
        "PROPERTY_CODE" => array("COURSE", ""),
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_STATUS_404" => "N",
        "SET_TITLE" => "N",
        "SHOW_404" => "N",
        "SORT_BY1" => "SORT",
        "SORT_BY2" => "ACTIVE_FROM",
        "SORT_ORDER1" => "ASC",
        "SORT_ORDER2" => "ASC",
        "STRICT_SECTION_CHECK" => "N"
    )
); ?>
    <section class="info container">
        <div class="info-container">
            <h2>С нами достигают результатов</h2>
            <div class="info-content">
                <div>
                    <div class="info-text">
                        <h3>Английский язык <br>на практике</h3>
                        <p>Мы делаем ставку на живое общение, разговорную практику в реальных ситуациях и программу,
                            которую учитель адаптирует лично под вас.</p>
                    </div>
                    <div class="info-img"
                         style='background-image: url("<?= SITE_TEMPLATE_PATH ?>/img/info/info1.jpg")'></div>
                </div>
                <div>
                    <div class="info-img contain"
                         style='background-image: url("<?= SITE_TEMPLATE_PATH ?>/img/info/info2.png")'></div>
                    <div class="info-text">
                        <h3>Говорите по-английски уверенно</h3>
                        <p>Учим красивому и правильному языку, чтобы ваши собеседники улыбались искренне, а не из-за
                            смешного английского.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
<? $APPLICATION->IncludeComponent(
    "bitrix:main.include",
    "",
    array(
        "AREA_FILE_SHOW" => "file",
        "AREA_FILE_SUFFIX" => "inc",
        "EDIT_TEMPLATE" => "",
        "PATH" => "/include/feedback-form.php"
    )
); ?>
<? $APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "faq",
    array(
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "ADD_SECTIONS_CHAIN" => "N",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "AJAX_OPTION_HISTORY" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "CACHE_FILTER" => "N",
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "DISPLAY_BOTTOM_PAGER" => "N",
        "DISPLAY_DATE" => "N",
        "DISPLAY_NAME" => "Y",
        "DISPLAY_PICTURE" => "N",
        "DISPLAY_PREVIEW_TEXT" => "Y",
        "DISPLAY_TOP_PAGER" => "N",
        "FIELD_CODE" => array("", ""),
        "FILTER_NAME" => "",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "IBLOCK_ID" => $_REQUEST["ID"],
        "IBLOCK_TYPE" => "content",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "INCLUDE_SUBSECTIONS" => "N",
        "MESSAGE_404" => "",
        "NEWS_COUNT" => "50",
        "PAGER_BASE_LINK_ENABLE" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
        "PAGER_SHOW_ALL" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => ".default",
        "PAGER_TITLE" => "Новости",
        "PARENT_SECTION" => "",
        "PARENT_SECTION_CODE" => "",
        "PREVIEW_TRUNCATE_LEN" => "",
        "PROPERTY_CODE" => array("", ""),
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_STATUS_404" => "N",
        "SET_TITLE" => "N",
        "SHOW_404" => "N",
        "SORT_BY1" => "ACTIVE_FROM",
        "SORT_BY2" => "SORT",
        "SORT_ORDER1" => "DESC",
        "SORT_ORDER2" => "ASC",
        "STRICT_SECTION_CHECK" => "N"
    )
); ?>
<?
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
?>