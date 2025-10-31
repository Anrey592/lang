document.addEventListener('DOMContentLoaded', function () {
    maskPhone('input[type="tel"]');

    if (document.querySelector('.burger')) {
        document.querySelector('.burger').addEventListener('click', () => {
            document.querySelector('.mobile-menu').classList.remove('hidden');
            document.body.classList.add('no-scroll');
        });
    }

    if (document.querySelector('.btn-close')) {
        document.querySelector('.btn-close').addEventListener('click', () => {
            document.querySelector('.mobile-menu').classList.add('hidden');
            document.body.classList.remove('no-scroll');
        });
    }

    if (document.querySelectorAll('.mobile-items .btn-open')) {
        document.querySelectorAll('.mobile-items .btn-open').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const li = item.closest('li');
                const items = li.querySelector('.child-items');
                if (items.classList.contains('hidden')) {
                    items.classList.remove('hidden');
                    item.classList.add('open');
                } else {
                    items.classList.add('hidden');
                    item.classList.remove('open');
                }
            });
        });
    }

    if (document.querySelectorAll('.more')) {
        const moreLinks = document.querySelectorAll('.more');

        moreLinks.forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                const textElement = this.parentNode.querySelector('.text');
                const isExpanded = textElement.classList.contains('expanded');

                if (isExpanded) {
                    textElement.classList.remove('expanded');
                    this.textContent = 'Читать весь отзыв';
                } else {
                    textElement.classList.add('expanded');
                    this.textContent = 'Свернуть';
                }
            });
        });
    }

    switchBurger();
});

function switchBurger() {
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileMenuWrapper = document.querySelector('.mobile-menu-wrapper');
    const btnClose = document.querySelector('.btn-close-wrapper');

    let touchStartY = 0;
    let touchEndY = 0;

    btnClose.addEventListener('touchstart', (e) => {
        touchStartY = e.changedTouches[0].clientY;
    });

    btnClose.addEventListener('touchend', (e) => {
        e.preventDefault();
        e.stopPropagation();
        touchEndY = e.changedTouches[0].clientY;
        checkSwipeDirection(touchEndY, touchStartY, mobileMenuWrapper, mobileMenu);
    });

    btnClose.addEventListener('touchcancel', (e) => {
        console.log('Touch Cancelled');
    });

    document.querySelector('.mobile-menu').addEventListener('click', (e) => {
        if (!e.target.closest('.mobile-menu-wrapper')) {
            mobileMenu.classList.add('hidden');
            mobileMenuWrapper.classList.remove('full-height');
            document.body.classList.remove('no-scroll');
        }
    });
}

function checkSwipeDirection(touchEndY, touchStartY, mobileMenuWrapper, mobileMenu) {
    const swipeDistance = touchEndY - touchStartY;

    if (swipeDistance < -50) {
        mobileMenuWrapper.classList.add('full-height');
    } else if (swipeDistance > 15) {
        mobileMenu.classList.add('hidden');
        mobileMenuWrapper.classList.remove('full-height');
        document.body.classList.remove('no-scroll');
    }
}

function showLoader() {
    document.querySelector('.loader').classList.remove('hidden');
}

function hideLoader() {
    document.querySelector('.loader').classList.add('hidden');
}

function loadMore() {
    const showMoreButton = document.querySelector('.btn-more');

    if (showMoreButton) {
        showMoreButton.addEventListener('click', async function (event) {
            event.preventDefault();
            showLoader();

            const nextPage = this.dataset.next;

            if (nextPage) {
                try {
                    const formData = new FormData();
                    const currentUrl = new URL(window.location.href);
                    formData.append('PAGEN_1', nextPage);

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }

                    const data = await response.text();

                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;

                    const itemsElement = tempDiv.querySelector('.items-container');
                    const newBtnMore = tempDiv.querySelector('.btn-more');
                    const pagenav = tempDiv.querySelector('.pagenav');

                    if (itemsElement) {
                        const itemsHTML = itemsElement.innerHTML;

                        const itemsContainer = document.querySelector('.items-container');

                        if (itemsContainer) {
                            itemsContainer.insertAdjacentHTML('beforeend', itemsHTML);

                            this.dataset.next = parseInt(nextPage) + 1;
                            if (!newBtnMore) {
                                this.remove();
                            }

                            if(pagenav) {
                                currentUrl.searchParams.set('PAGEN_1', nextPage);
                                document.querySelector('.pagenav').innerHTML = pagenav.innerHTML;
                            }
                            window.history.pushState({}, '', currentUrl.toString());
                        } else {
                            console.warn('Контейнер .items не найден на странице.');
                        }
                    } else {
                        console.warn('В ответе от сервера отсутствует поле .items.');
                    }

                    hideLoader();
                } catch (error) {
                    hideLoader();
                    console.error('Ошибка AJAX-запроса:', error);
                }
            } else {
                console.warn('Атрибут data-next не найден у кнопки "Показать ещё".');
            }
        });
    }
}