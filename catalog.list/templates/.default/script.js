document.addEventListener("DOMContentLoaded", function () {
    const CatalogListInstance = new CatalogList();

    CatalogListInstance.activateFavoriteButtons();
    CatalogListInstance.activateCompareButtons();
    CatalogListInstance.buttonsSort();
    CatalogListInstance.buttonsFilter();
    setTimeout(function () {
        CatalogListInstance.addToBasket();
    }, 1000)
    CatalogListInstance.clickOnFavorite();
    CatalogListInstance.clickOnCompare();
    CatalogListInstance.setCatalogView();
    // CatalogListInstance.sortReset();
    CatalogListInstance.pagination();
});

document.querySelectorAll('.select').forEach(item => {
    item.addEventListener('click', () => {
        item.classList.toggle('open');
    });
});


class CatalogList {

    constructor() {
    }


    addToBasket() {
        const parentObject = document.querySelector('.catalog');
        parentObject.addEventListener('click', function (event) {
            const button = event.target.closest('.card-product__button');
            const plus = event.target.closest('.product-config-amount__button_plus');
            const minus = event.target.closest('.product-config-amount__button_minus');
            if (button) {
                const productId = button.getAttribute('data-option');
                CatalogList.addToBasket(productId);
                CatalogList.showOrderPanel(productId,1);
            } else
            if (plus) {
                const productId = plus.getAttribute('data-option');
                CatalogList.addToBasket(productId);
            } else
            if (minus) {
                const productId = minus.getAttribute('data-option');
                CatalogList.reduceCountAjax(productId);
            }


        });
    }

    sortReset() {
        const button = document.querySelector('[data-sort-reset]');
        button.addEventListener('click', function (event) {
            CatalogList.ajaxGetByCategory({});
        });
    }

    static showOrderPanel(id,status) {
        const basketButton = document.querySelector(`.card-product__button[data-option="${id}"]`);
        const panel = document.querySelector(`.product-config-amount[data-option="${id}"]`);


        if (status === 1) {
            basketButton.classList.add('hidden');
            panel.classList.remove('hidden');
        }

        if (status === 0) {
            basketButton.classList.remove('hidden');
            panel.classList.add('hidden');
        }

    }
    static addToBasket(productId) {
        CatalogList.setOverlay(1);
        let inputElement = document.querySelector(`input[data-option="${productId}"]`);
        let plusButton = document.querySelector(`.product-config-amount__button_plus[data-option="${productId}"]`);


        const request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'addToBasket',
            {
                mode: 'ajax',
                data: {
                    id: productId,
                }
            }
        );

        request.then(function (response) {
            if (response.data.success === 1 || response.data.success === 2) {
                inputElement.value = response.data.count;
                if (response.data.count === 20) {
                    plusButton.disabled = true;
                }
                CatalogList.updateCartCounter();
                CatalogList.setOverlay(0);
            } else {
                CatalogList.setOverlay(0);
                console.log(response.data.message);
            }
        });

        request.catch(
            function (response) {
                console.log(response);
                CatalogList.setOverlay(0);
            });
    }
    static reduceCountAjax(productId) {
        CatalogList.setOverlay(1)
        let inputElement = document.querySelector(`input[data-option="${productId}"]`);
        let plusButton = document.querySelector(`.product-config-amount__button_plus[data-option="${productId}"]`);
        const request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'reduceNumberItems',
            {
                mode: 'ajax',
                data: {
                    id: productId,
                }
            }
        );

        request.then(function (response) {
            if (response.data.success === 1) {
                CatalogList.updateCartCounter();
                CatalogList.setOverlay(0)
                inputElement.value = response.data.count;
                plusButton.disabled = false;
            } else if (response.data.success === 2) {
                CatalogList.showOrderPanel(productId,0);
                CatalogList.updateCartCounter();
                CatalogList.setOverlay(0);
                plusButton.disabled = false;
            } else {
                CatalogList.setOverlay(0);
            }
        });

        request.catch(
            function (response) {
                console.log(response);
            });
        CatalogList.setOverlay(0)
    }


    clickOnFavorite() {

        const parentObject = document.querySelector('.catalog');
        parentObject.addEventListener('click', function (event) {
            const button = event.target.closest('.card-product__action--favorite');
            if (button) {

                const productId = button.getAttribute('data-item-id');
                CatalogList.ajaxToggleFavorite(productId);
            }
        });
    }

    clickOnCompare() {

        const parentObject = document.querySelector('.catalog');
        parentObject.addEventListener('click', function (event) {
            const button = event.target.closest('.card-product__action--compare');
            if (button) {

                const productId = button.getAttribute('data-item-id');
                CatalogList.ajaxToggleCompare(productId);
            }
        });
    }


    static ajaxGetByCategory(checkedItemsDataKeysArray) {
        CatalogList.setOverlay(1);

        let request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'setSort',
            {
                mode: 'ajax',
                data: {sortArray: checkedItemsDataKeysArray}
            }
        );

        request.then(function (response) {
            if (response.data.success === 1) {
                location.reload();
                console.log(response);
            } else {
                console.log(response);
            }

            CatalogList.setOverlay(0);
        });

        request.catch(
            function (response) {
                console.log(response)
                CatalogList.setOverlay(0);
            });
    }

    static getByFiltersAjax(filterArray) {
        CatalogList.setOverlay(1);

        let request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'setFilterFlags',
            {
                mode: 'ajax',
                data: {finalFilter: filterArray}
            }
        );

        request.then(function (response) {
            location.reload();
            console.log(response);
            CatalogList.setOverlay(0);
        });

        request.catch(
            function (response) {
                console.log(response)
                CatalogList.setOverlay(0);
            });
    }

    static ajaxToggleFavorite(productId) {
        CatalogList.setOverlay(1);
        const elements = document.querySelectorAll('.card-product__action.card-product__action--favorite[data-item-id="' + productId + '"]');

        let request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'setFavorite',
            {
                mode: 'ajax',
                data: {id: productId},
                componentParams: {id: productId}
            }
        );

        request.then(function (response) {
                if (response.data.success === 1) {
                    CatalogList.setOverlay(0);
                    elements.forEach(function (element) {
                        element.classList.add('active');
                    });
                } else if (response.data.success === 2) {
                    CatalogList.setOverlay(0);
                    elements.forEach(function (element) {
                        element.classList.remove('active');
                    });
                } else {
                    CatalogList.setOverlay(0);
                    alert('Ошибка при добавлении товара в избранное');
                    console.log(response);
                }
                CatalogList.updateFavoritesCounter();
            }
        )

        request.catch(function (response) {
            console.log(response);
            CatalogList.setOverlay(0);

        });


    }

    static ajaxToggleCompare(productId) {
        CatalogList.setOverlay(1);
        const elements = document.querySelectorAll('.card-product__action.card-product__action--compare[data-item-id="' + productId + '"]');

        let request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'setCompare',
            {
                mode: 'ajax',
                data: {id: productId},
                componentParams: {id: productId}
            }
        );

        request.then(function (response) {
                if (response.data.success === 1) {
                    CatalogList.setOverlay(0);
                    elements.forEach(function (element) {
                        element.classList.add('active');
                    });
                    alert('Товар успешно добавлен в список сравнения');
                } else if (response.data.success === 2) {
                    CatalogList.setOverlay(0);
                    elements.forEach(function (element) {
                        element.classList.remove('active');
                    });
                    alert('Товар удалён из списка сравнения');
                } else {
                    CatalogList.setOverlay(0);
                    alert('Ошибка при добавлении товара в список сравнения');
                    console.log(response);
                }
                CatalogList.updateCompareCounter();
            }
        )

        request.catch(function (response) {
            console.log(response);
            CatalogList.setOverlay(0);

        });


    }

    static updateCartCounter() {
        const cartCounter = document.querySelectorAll('.product-counter');
        if (cartCounter) {
            const request = BX.ajax.runComponentAction(
                'all4it:catalog.list',
                'getCartTotalQuantity',
                {
                    mode: 'ajax'
                }
            );

            request.then(function (response) {
                cartCounter.forEach(function (elem) {
                    elem.setAttribute('data-number',  response.data.itemCounter);
                })
            });

            request.catch(function (response) {
                console.log(response);
            });
        }
    }

    static updateFavoritesCounter() {
        const counterElement = document.querySelectorAll('.favorites-counter');
        const request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'getFavoritesCount',
            {
                mode: 'ajax'
            }
        );
        request.then(function (response) {
            counterElement.forEach(function (elem) {
                elem.setAttribute('data-number',  response.data.favoriteCount);

            })
        });

    }

    static updateCompareCounter() {
        const counterElement = document.querySelector('.compare-counter');
        const request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'getCompareCount',
            {
                mode: 'ajax'
            }
        );
        request.then(function (response) {
            counterElement.innerText = response.data.compareCount;
        });

    }

    setCatalogView() {
        const viewButtons = document.querySelectorAll('.catalog-header-view-buttons__item');
        const catalogContainer = document.querySelector('.catalog-page .catalog');
        if (!catalogContainer.classList.contains('catalog_configurators') && CatalogList.isDesktop()) {

            let currentView = sessionStorage.getItem('catalogView');
            updateCatalogContainerClass();
            function updateViewMode(mode) {
                sessionStorage.setItem('catalogView', mode);
            }

            function updateCatalogContainerClass() {
                if (currentView === null && CatalogList.isDesktop()) {
                    catalogContainer.classList.remove('catalog_list', 'catalog_grid');
                    catalogContainer.classList.add('catalog_grid');
                } else if (currentView === null && !CatalogList.isDesktop()) {
                    catalogContainer.classList.remove('catalog_list', 'catalog_grid');
                    catalogContainer.classList.add('catalog_grid');
                } else {
                    catalogContainer.classList.remove('catalog_list', 'catalog_grid');
                    catalogContainer.classList.add('catalog_' + currentView);
                }
            }

            function setActiveButton() {
                viewButtons.forEach(function (btn) {
                    btn.classList.remove('catalog-header-view-buttons__item_active');
                });

                var activeButtonList = document.querySelector('.catalog-header-view-buttons__item.catalog-header-view-buttons__item.catalog-header-view-buttons__item_list');
                var activeButtonGrid = document.querySelector('.catalog-header-view-buttons__item.catalog-header-view-buttons__item.catalog-header-view-buttons__item_grid');
                let catalogList = document.querySelector('.catalog.catalog_list');
                let catalogGrid = document.querySelector('.catalog.catalog_grid');
                if (catalogList) {
                    activeButtonList.classList.add('catalog-header-view-buttons__item_active');
                } else if (catalogGrid) {
                    activeButtonGrid.classList.add('catalog-header-view-buttons__item_active');
                }
            }

            viewButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    viewButtons.forEach(function (btn) {
                        btn.classList.remove('catalog-header-view-buttons__item_active');
                    });

                    button.classList.add('catalog-header-view-buttons__item_active');

                    currentView = button.classList.contains('catalog-header-view-buttons__item_list') ? 'list' : 'grid';
                    updateViewMode(currentView);

                    updateCatalogContainerClass();
                });
            });

            if (currentView === null) {
                currentView = 'grid';
                updateViewMode(currentView);
            }


            setActiveButton();
        }
    }

    pagination() {
        const totalPages = paginationQuantity;
        const paginationContainer = document.getElementById('pagination-container');
        const prevButton = document.getElementById('button-prev');
        const nextButton = document.getElementById('button-next');

        for (let i = 1; i <= totalPages; i++) {
            const pageLink = document.createElement('a');
            pageLink.classList.add('catalog-pagination__item');
            pageLink.href = '#';
            pageLink.textContent = i;
            pageLink.id = `${i}`;
            if (i === 1) {
                pageLink.classList.add('catalog-pagination__item_active');
            }
            paginationContainer.insertBefore(pageLink, nextButton);
        }

        hideAllPages();
        if (totalPages <= 5) {
            showPages(1, totalPages)
            prevButton.classList.add('hidden')
            nextButton.classList.add('hidden')
        } else {
            showPages(1, 5);
        }

        const end = findVisiblePageIndex();
        const start = Math.max(0, end - 4);
        if (start === 0) {
            prevButton.classList.add('hidden');
        }

        const pages = document.querySelectorAll('.catalog-pagination__item');
        pages.forEach(page => {
            page.addEventListener('click', function (event) {

                event.preventDefault();
                pages.forEach(otherPage => {
                    otherPage.classList.remove('catalog-pagination__item_active');
                });
                this.classList.add('catalog-pagination__item_active');
                const pageId = this.id;
                ajaxPagination(pageId);
            });
        });

        nextButton.addEventListener('click', function (event) {
            event.preventDefault();
            showNextPages();
        });

        prevButton.addEventListener('click', function (event) {
            event.preventDefault();
            showPrevPages();
        });

        function hideAllPages() {
            const pages = document.querySelectorAll('.catalog-pagination__item');
            pages.forEach(page => page.classList.add('hidden'));
        }

        function showPages(start, end) {
            const pages = document.querySelectorAll('.catalog-pagination__item');
            for (let i = start - 1; i < end; i++) {
                pages[i].classList.remove('hidden');
            }
        }

        function findVisiblePageIndex() {
            const pages = document.querySelectorAll('.catalog-pagination__item');
            const visiblePages = [];
            for (let i = 0; i < pages.length; i++) {
                if (!pages[i].classList.contains('hidden')) {
                    visiblePages.push(i);
                }
            }
            return visiblePages[visiblePages.length - 1];
        }

        function showNextPages() {
            const visiblePageIndex = findVisiblePageIndex();
            const start = visiblePageIndex + 1;
            const end = start + 4;
            hideAllPages()
            if (end < totalPages) {
                showPages(start, end);
            } else {
                showPages(start, totalPages);
            }
            if (end >= totalPages) {
                nextButton.classList.add('hidden');
            }
            if (start !== 0) {
                prevButton.classList.remove('hidden');
            }
        }

        function showPrevPages() {
            const end = findVisiblePageIndex();
            const start = Math.max(0, end - 4);
            hideAllPages()
            if (start <= 4) {
                prevButton.classList.add('hidden');
            }
            if (end < totalPages) {
                nextButton.classList.remove('hidden');
            }
            if (start < 5) {
                showPages(1, 5);
            } else {
                showPages(start - 3, end - 3);
            }


        }

        function ajaxPagination(page) {
            const requestData = {
                PAGE: page ?? 1,
                arParams: JSON.parse(arParams)
            };
            updateQueryStringParameter('PAGEN', parseInt(page));

            BX.ajax.runComponentAction(
                'all4it:catalog.list',
                'setPagination',
                {
                    mode: 'ajax',
                    data: requestData
                }
            )
                .then(function (response) {
                    const html = response.data ?? '';
                    const wrapper = document.querySelector('.catalog__items');
                    wrapper.innerHTML = html;
                    activateFavoriteButtons();
                    activateCompareButtons();
                })
                .catch(function (response) {
                    console.log(response);
                })
        }

        function updateQueryStringParameter(key, page) {
            const url = new URL(window.location.href);
            const searchParams = new URLSearchParams(url.search);

            if (page === 1 || page === undefined) {
                searchParams.delete(key);
            } else {
                searchParams.set(key, page);
            }

            url.search = searchParams.toString();
            window.history.pushState({path: url.href}, '', url.href);
        }

        function checkPageUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentPage = parseInt(urlParams.get('PAGEN'), 10) || 1;
            if (currentPage > 1 && currentPage < paginationQuantity) {
                const firstPage = document.querySelector(`.catalog-pagination__item[id="${1}"]`);
                const activePage = document.querySelector(`.catalog-pagination__item[id="${currentPage}"]`);
                if (activePage) {
                    firstPage.classList.remove('catalog-pagination__item_active');
                    activePage.classList.add('catalog-pagination__item_active');
                    ajaxPagination(currentPage)
                    if (currentPage <= 5) {
                        updatePagination(1, 5);
                    } else {
                        const start = currentPage - 2;
                        const end = currentPage + 2;
                        updatePagination(start, end);
                    }

                }
            } else {
                updateQueryStringParameter('PAGEN', 1);

            }
        }

        function updatePagination(start, end) {
            const prevButton = document.getElementById('button-prev');
            const nextButton = document.getElementById('button-next');
            const totalPages = paginationQuantity;
            const pages = document.querySelectorAll('.catalog-pagination__item');
            pages.forEach(page => page.classList.add('hidden'));
            for (let i = start - 1; i < end; i++) {
                if (pages[i]) {
                    pages[i].classList.remove('hidden');
                }
            }
            if (end >= totalPages) {
                nextButton.classList.add('hidden');
            }
            if (start > 1) {
                prevButton.classList.remove('hidden');
            }
        }

        checkPageUrl();

    }

    static setOverlay(status) {
        let overlay = document.querySelector('.overlay');

        if (!overlay && status === 1) {
            overlay = document.createElement('div');
            overlay.classList.add('overlay');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(0, 0, 0, 0)';
            overlay.style.transition = 'background-color 0.3s ease-in-out';
            overlay.style.zIndex = '1000';
            const catalogPage = document.querySelector('.catalog-page');
            catalogPage.appendChild(overlay);

            setTimeout(() => {
                overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.3)';
            }, 10);
        }

        if(overlay) {
            overlay.style.backgroundColor = status === 1 ? 'rgba(0, 0, 0, 0.3)' : 'rgba(0, 0, 0, 0)';
        }

        if (status === 0 && overlay && overlay.parentNode) {
            overlay.style.backgroundColor = 'rgba(0, 0, 0, 0)';
            setTimeout(() => {
                if(overlay) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 300);
        }
    }

    activateFavoriteButtons() {
        const favoriteIcons = document.querySelectorAll('.catalog .card-product__action--favorite');

        const request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'getFavoritesList',
            {
                mode: 'ajax'
            }
        );

        request.then(function (response) {
            const favoritesList = response.data.favoritesList;

            favoriteIcons.forEach((icon) => {
                const productId = icon.getAttribute('data-item-id');

                const isFavorite = favoritesList.includes(String(productId));

                if (isFavorite) {
                    icon.classList.add('active');
                } else {
                    icon.classList.remove('active');
                }
            });
        });

        request.catch(function (response) {
            console.log(response);
        });
    }

    activateCompareButtons() {
        const favoriteIcons = document.querySelectorAll('.catalog .card-product__action--compare');

        const request = BX.ajax.runComponentAction(
            'all4it:catalog.list',
            'getCompareList',
            {
                mode: 'ajax'
            }
        );

        request.then(function (response) {
            const compareList = response.data.compareList;

            favoriteIcons.forEach((icon) => {
                const productId = icon.getAttribute('data-item-id');

                const isCompare = compareList.includes(String(productId));

                if (isCompare) {
                    icon.classList.add('active');
                } else {
                    icon.classList.remove('active');
                }
            });
        });

        request.catch(function (response) {
            console.log(response);
        });
    }


    buttonsSort() {

        document.querySelectorAll('.button-select.sort').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelectorAll('.button-select').forEach(function (otherButton) {
                    if (otherButton !== this) {
                        otherButton.classList.remove('active');
                        const arrowIcon = otherButton.querySelector('.arrow-icon');
                        if (arrowIcon && arrowIcon.classList.contains('flip')) {
                            arrowIcon.classList.remove('flip');
                            location.reload();
                        }
                    }
                }.bind(this));

                if (!this.classList.contains('active')) {
                    this.classList.add('active');
                }

                const arrowIcon = this.querySelector('.arrow-icon');
                if (arrowIcon) {
                    arrowIcon.classList.toggle('flip');
                }

                if (this.classList.contains('active')) {
                    this.setAttribute('data-sort', this.getAttribute('data-sort') === "DESC" ? "ASC" : "DESC");
                }

                let sortKey = this.getAttribute('data-key');
                let sortOrder = this.getAttribute('data-sort');
                let sortData = {};
                sortData[sortKey] = sortOrder;


                CatalogList.ajaxGetByCategory(sortData);
            });
        });

    }

    buttonsFilter() {

        let buttons = document.querySelectorAll('.button-select.filter');

        if (buttons.length > 0) {
            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    this.classList.toggle('active');


                    let filterData = [];

                    buttons.forEach(function (item) {
                        if (item.classList.contains('active')) {
                            let dataKey = item.getAttribute('data-key');
                            filterData.push(dataKey);
                        }
                    });
                    if (filterData.length === 0) {
                        filterData.push(0)

                    }
                    CatalogList.getByFiltersAjax(filterData);
                });
            });
        } else {
            console.log('let buttons = document.querySelectorAll(\'.button-select.filter\'); erorr');
        }

    }
    static isDesktop() {
        return window.matchMedia("(min-width: 768px)").matches;
    }
}





