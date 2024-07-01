document.addEventListener("DOMContentLoaded", function () {
    const TopMenuItemInstance = new TopMenuItem();

    TopMenuItemInstance.activateFavoriteButtons();
    setTimeout(function () {
        TopMenuItemInstance.addToBasket();
    }, 1000)
    TopMenuItemInstance.clickOnFavorite();

});


class TopMenuItem {

    constructor() {
    }


    addToBasket() {
        const parentObject = document.querySelector('.catalog-popup');
        parentObject.addEventListener('click', function (event) {
            const button = event.target.closest('.card-product__button');
            if (button) {
                const productId = button.getAttribute('data-option');
                TopMenuItem.addToBasket(productId);
            }
        });
    }


    static addToBasket(productId) {
        TopMenuItem.setOverlay(1);


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
            if (response.data.success === 1) {
                TopMenuItem.updateCartCounter();
                TopMenuItem.setOverlay(0);
            } else {
                TopMenuItem.setOverlay(0);
                alert('Ошибка при добавлении товара в корзину');
                console.log(response);
            }
        });

        request.catch(
            function (response) {
                console.log(response);
                TopMenuItem.setOverlay(0);
            });
    }


    clickOnFavorite() {

        const parentObject = document.querySelector('.catalog-popup');
        parentObject.addEventListener('click', function (event) {
            const button = event.target.closest('.card-product__action--favorite');
            if (button) {

                const productId = button.getAttribute('data-item-id');
                TopMenuItem.ajaxToggleFavorite(productId);
            }
        });
    }


    

    static ajaxToggleFavorite(productId) {
        TopMenuItem.setOverlay(1);
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
                    TopMenuItem.setOverlay(0);
                    elements.forEach(function (element) {
                        element.classList.add('active');
                    });
                } else if (response.data.success === 2) {
                    TopMenuItem.setOverlay(0);
                    elements.forEach(function (element) {
                        element.classList.remove('active');
                    });
                } else {
                    TopMenuItem.setOverlay(0);
                    alert('Ошибка при добавлении товара в избранное');
                    console.log(response);
                }
                TopMenuItem.updateFavoritesCounter();
            }
        )

        request.catch(function (response) {
            console.log(response);
            TopMenuItem.setOverlay(0);

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
            overlay.style.zIndex = '5555';
            const catalogPage = document.querySelector('body');
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
        const favoriteIcons = document.querySelectorAll('.catalog-popup .card-product__action--favorite');

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
}





