document.addEventListener('DOMContentLoaded', function () {
    const showMoreButton = document.querySelector('.btn-more');

    if (showMoreButton) {
        console.log(showMoreButton)
        showMoreButton.addEventListener('click', async function (event) {
            event.preventDefault();

            const nextPage = this.dataset.next;

            if (nextPage) {
                try {
                    // Создаем объект FormData
                    const formData = new FormData();
                    formData.append('PAGEN_1', nextPage);

                    // Отправляем AJAX-запрос с async/await
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }

                    // Получаем текстовый ответ
                    const data = await response.text();

                    // Создаем временный DOM-элемент для парсинга
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;

                    // Находим элемент .items во временном DOM-элементе
                    const itemsElement = tempDiv.querySelector('.items');

                    if (itemsElement) {
                        // Получаем верстку из .items
                        const itemsHTML = itemsElement.innerHTML;

                        // Находим элемент .items на текущей странице
                        const itemsContainer = document.querySelector('.items');

                        if (itemsContainer) {
                            // Вставляем верстку в .items на текущей странице
                            itemsContainer.insertAdjacentHTML('beforeend', itemsHTML);

                            // Увеличиваем data-next для следующего запроса
                            this.dataset.next = parseInt(nextPage) + 1;
                        } else {
                            console.warn('Контейнер .items не найден на странице.');
                        }
                    } else {
                        console.warn('В ответе от сервера отсутствует поле .items.');
                    }

                } catch (error) {
                    console.error('Ошибка AJAX-запроса:', error);
                    // Обработка ошибок (например, показ сообщения пользователю)
                }
            } else {
                console.warn('Атрибут data-next не найден у кнопки "Показать ещё".');
            }
        });
    }
});
