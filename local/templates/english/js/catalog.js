// Функции для работы с фильтром
function toggleChildSections(parentCheckbox, parentId) {
    const childCheckboxes = document.querySelectorAll(`.child-checkbox[data-parent="${parentId}"]`);

    if (parentCheckbox.checked) {
        childCheckboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
    } else {
        childCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    updateParentSection(parentId);
}

function updateParentSection(parentId) {
    const parentCheckbox = document.querySelector(`.parent-checkbox[value="${parentId}"]`);
    const childCheckboxes = document.querySelectorAll(`.child-checkbox[data-parent="${parentId}"]`);

    let allChecked = true;
    let someChecked = false;

    childCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            someChecked = true;
        } else {
            allChecked = false;
        }
    });

    if (allChecked && childCheckboxes.length > 0) {
        parentCheckbox.checked = true;
        parentCheckbox.indeterminate = false;
    } else if (someChecked) {
        parentCheckbox.checked = false;
        parentCheckbox.indeterminate = true;
    } else {
        parentCheckbox.checked = false;
        parentCheckbox.indeterminate = false;
    }
}

// Функция для обновления URL параметров
function updateUrlParams(selectedSections) {
    const url = new URL(window.location);

    // Удаляем старые параметры SECTION_ID
    url.searchParams.delete('SECTION_ID[]');

    // Добавляем новые параметры
    selectedSections.forEach(sectionId => {
        url.searchParams.append('SECTION_ID[]', sectionId);
    });

    // Обновляем URL без перезагрузки страницы
    window.history.pushState({}, '', url.toString());
}

// Основная функция фильтрации через AJAX
async function applyFilter() {
    try {
        // Показываем loader
        if (typeof showLoader === 'function') {
            showLoader();
        }

        // Собираем выбранные разделы
        const selectedSections = [];
        const checkboxes = document.querySelectorAll('.section-filter-checkbox:checked');
        checkboxes.forEach(checkbox => {
            selectedSections.push(checkbox.value);
        });

        // Обновляем URL с новыми параметрами
        updateUrlParams(selectedSections);

        // Формируем параметры запроса
        const params = new URLSearchParams();
        selectedSections.forEach(sectionId => {
            params.append('SECTION_ID[]', sectionId);
        });

        // Добавляем параметр для идентификации AJAX запроса
        params.append('AJAX_FILTER', 'Y');

        // Выполняем AJAX запрос
        const response = await fetch(window.location.href, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Ajax-Filter': 'true'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const html = await response.text();

        // Парсим HTML и извлекаем контент с классом .items
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContent = doc.querySelector('.repetitory .items');

        if (newContent) {
            // Заменяем только содержимое .items
            const currentItems = document.querySelector('.repetitory .items');
            if (currentItems) {
                currentItems.innerHTML = newContent.innerHTML;
            }

            // Инициализируем кнопку "Показать ещё" если она есть
            initLoadMoreButton();
        }

    } catch (error) {
        console.error('Ошибка при фильтрации:', error);
        alert('Произошла ошибка при фильтрации. Пожалуйста, попробуйте еще раз.');
    } finally {
        // Скрываем loader
        if (typeof hideLoader === 'function') {
            hideLoader();
        }

        loadMore();
    }
}

// Функция сброса фильтра
async function resetFilter() {
    try {
        // Снимаем все выделения
        const checkboxes = document.querySelectorAll('.section-filter-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.indeterminate = false;
        });

        // Обновляем состояние родительских чекбоксов
        const parentCheckboxes = document.querySelectorAll('.parent-checkbox');
        parentCheckboxes.forEach(checkbox => {
            updateParentSection(checkbox.value);
        });

        // Очищаем URL параметры
        const url = new URL(window.location);
        url.searchParams.delete('SECTION_ID[]');
        window.history.pushState({}, '', url.toString());

        // Применяем фильтр (пустой)
        await applyFilter();

    } catch (error) {
        console.error('Ошибка при сбросе фильтра:', error);
    }
}

// Функция для синхронизации чекбоксов с URL параметрами
function syncCheckboxesWithUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const sectionIds = urlParams.getAll('SECTION_ID[]');

    // Сбрасываем все чекбоксы
    const checkboxes = document.querySelectorAll('.section-filter-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.indeterminate = false;
    });

    // Устанавливаем выбранные чекбоксы из URL
    sectionIds.forEach(sectionId => {
        const checkbox = document.querySelector(`.section-filter-checkbox[value="${sectionId}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });

    // Обновляем состояние родительских чекбоксов
    const parentCheckboxes = document.querySelectorAll('.parent-checkbox');
    parentCheckboxes.forEach(checkbox => {
        updateParentSection(checkbox.value);
    });
}

// Инициализация кнопки "Показать ещё"
function initLoadMoreButton() {
    const loadMoreBtn = document.querySelector('.btn-more');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            const nextPage = this.getAttribute('data-next');
            loadMoreItems(nextPage);
        });
    }
}

// Загрузка дополнительных элементов (если нужно)
async function loadMoreItems(page) {
    try {
        if (typeof showLoader === 'function') {
            showLoader();
        }

        // ... код для подгрузки элементов ...

    } catch (error) {
        console.error('Ошибка при загрузке:', error);
    } finally {
        if (typeof hideLoader === 'function') {
            hideLoader();
        }
    }
}

// Автоматическое применение фильтра при изменении чекбоксов
function initAutoFilter() {
    const checkboxes = document.querySelectorAll('.section-filter-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            // Небольшая задержка для баунсинга
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(applyFilter, 500);
        });
    });
}

function initMobileFilter() {
    const filterBtn = document.querySelector('.btn-filter');
    const filterSection = document.querySelector('.section-filter');
    const closeFilterBtn = document.querySelector('.btn-close-filter');
    const filterOverlay = document.querySelector('.filter-overlay');
    const filterButtons = document.querySelectorAll('.filter-buttons .btn');

    // Открытие фильтра
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            filterSection.classList.add('active');
            filterOverlay.classList.add('active');
            document.body.classList.add('no-scroll');
        });
    }

    // Закрытие фильтра
    function closeFilter() {
        filterSection.classList.remove('active');
        filterOverlay.classList.remove('active');
        document.body.classList.remove('no-scroll');
    }

    // Закрытие по кнопке
    if (closeFilterBtn) {
        closeFilterBtn.addEventListener('click', closeFilter);
    }

    // Закрытие по оверлею
    if (filterOverlay) {
        filterOverlay.addEventListener('click', closeFilter);
    }

    // Закрытие по кнопкам фильтра
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (window.innerWidth <= 1099) {
                closeFilter();
            }
        });
    });

    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && filterSection.classList.contains('active')) {
            closeFilter();
        }
    });
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function () {
    // Синхронизируем чекбоксы с URL параметрами
    syncCheckboxesWithUrl();

    // Инициализация автоматического применения фильтра
    initAutoFilter();

    // Инициализация кнопки "Показать ещё"
    initLoadMoreButton();

    // Обработка истории браузера
    window.addEventListener('popstate', function () {
        syncCheckboxesWithUrl();
        applyFilter();
    });

    initMobileFilter();
});

// Применяем фильтр если в URL есть параметры при загрузке
if (window.location.search.includes('SECTION_ID[]')) {
    document.addEventListener('DOMContentLoaded', function () {
        applyFilter();
    });
}