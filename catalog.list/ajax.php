<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Intranet\Invitation;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\Item\UserToGroup;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Ui;
use Bitrix\All4it\FavoritesTable;
use Bitrix\Main\ORM\Query\Query;

\Bitrix\Main\Loader::includeModule('sale');


class CatalogListController extends \Bitrix\Main\Engine\Controller
{
    use \All4it\IblockHelper;


    // Обязательный метод
    public function configureActions()
    {
        return [
            'addToBasket' => [
                'prefilters' => [],
            ],
            'setSort' => [
                'prefilters' => [],
            ],
            'setFavorite' => [
                'prefilters' => [],
            ],
            'setCompare' => [
                'prefilters' => [],
            ],
            'getCartTotalQuantity' => [
                'prefilters' => [],
            ],
            'getFavoritesList' => [
                'prefilters' => [],
            ],
            'getCompareList' => [
                'prefilters' => [],
            ],
            'getFavoritesCount' => [
                'prefilters' => [],
            ],
            'getCompareCount' => [
                'prefilters' => [],
            ],
            'setPagination' => [
                'prefilters' => [],
            ],
            'setFilterFlags' => [
                'prefilters' => [],
            ],
            'reduceNumberItems' => [
                'prefilters' => [],
            ],
        ];
    }

    public function addToBasketAction(int $id): array
    {
        $productId = $id;
        $count = 1;
        $product = $this->getProductByID($productId);
        $price = 0;
        $name = '';
        $quantity = 1;

        if ($product) {
            $price = $product[0]['PRICE'];
            $name = $product[0]['NAME'];
        }

        if (!Loader::includeModule('sale')) {
            return ['success' => 1, 'message' => 'sale not loaded'];
        }

        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),
            \Bitrix\Main\Context::getCurrent()->getSite());
        $existingItem = $basket->getExistsItem('catalog', $productId);
        if ($existingItem) {
            $quantity = $existingItem->getQuantity();

            if ($quantity < 20) {
                $existingItem->setField('QUANTITY', $quantity + $count);
                $basket->save();
                return ['success' => 1, 'count' => $quantity + $count];
            } else {
                return ['error' => 1, 'count' => $quantity, 'message' => "Нельзя добавить больше 20 товаров"];
            }


        } else {
            $item = $basket->createItem('catalog', $productId);
            $item->setFields([
                'QUANTITY' => $count,
                'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                'PRICE' => $price,
                'CUSTOM_PRICE' => 'Y',
                'PRODUCT_PROVIDER_CLASS' => '',
                'NAME' => $name,
            ]);
            $basket->save();

            return ['success' => 2, 'count' => $quantity];
        }
    }

    public function reduceNumberItemsAction(int $id): array
    {
        $productId = $id;

        if (!Loader::includeModule('sale')) {
            return ['success' => 1, 'message' => 'sale not loaded'];
        }

        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),
            \Bitrix\Main\Context::getCurrent()->getSite());
        $existingItem = $basket->getExistsItem('catalog', $productId);
        if (!$existingItem) {
            return ['success' => 2, 'message' => 'Товар не найден'];
        }
        if ($existingItem->getQuantity() > 1) {
            $existingItem->setField('QUANTITY', $existingItem->getQuantity() - 1);
            $basket->save();
            return ['success' => 1, 'count' => $existingItem->getQuantity()];
        } else {
            $existingItem->delete();
            $basket->save();
            return ['success' => 2, 'message' => 'Товар удален'];
        }
    }

    private function getProductByID(int $id): array
    {
        \CBitrixComponent::includeComponentClass("all4it:catalog.list");
        $this->initIblockHelper(tableObj: new \Bitrix\Iblock\Elements\ElementCatalog1CTable);
        $res = $this->getDataFormTable(
            select: [
                'PRICE' => 'PRICE_LIST.PRICE',
                'NAME',
            ],
            filter: ['ID' => $id],
            limit: 1,
            runtime: [
                [
                    'NAME' => 'PRODUCT_TABLE',
                    'DATA_TYPE' => \Bitrix\Catalog\ProductTable::class,
                    'REFERENCE' => ['ID', 'ID'],
                    'JOIN_TYPE' => 'left'
                ],
                [
                    'NAME' => 'PRICE_LIST',
                    'DATA_TYPE' => \Bitrix\Catalog\PriceTable::class,
                    'REFERENCE' => ['PRODUCT_TABLE.ID', 'PRODUCT_ID'],
                    'JOIN_TYPE' => 'left'
                ]
            ],
        );
        return $res;
    }


    public function setSortAction(array $sortArray = []): array
    {
        if (empty($sortArray)) {
            $_SESSION['CATALOG_SORT'] = [];
            return ['success' => 1, 'message' => 'сортировка очищена'];
        }

        $_SESSION['CATALOG_SORT'] = $sortArray;

        // Возвращаем успешный результат
        return ['success' => 1, 'message' => 'sort has been set successfully'];
    }


    public function setFilterFlagsAction(array $finalFilter): array
    {
        if ($finalFilter[0] == 0) {
            $_SESSION['FLAGS_FILTER'] = [];
            return ['success' => 1, 'message' => 'Пустые фильтры'];
        }

        $_SESSION['FLAGS_FILTER'] = $finalFilter;

        return ['success' => 1, 'message' => 'filter has been set successfully'];
    }


    /**
     * @param int $id
     * @return array
     */
    public function setFavoriteAction(int $id): array
    {
        // Проверка наличия параметров
        if (empty($id)) {
            return ['success' => 0, 'message' => 'Ошибка при добавлении в избранное'];
        }

        $isAlreadyInFavorites = $this->checkIfItemIsInFavorites($id);

        if (!$isAlreadyInFavorites) {
            $this->addToFavorites($id);
            return ['success' => 1, 'message' => 'Товар успешно добавлен в избранное'];
        } else {
            $this->deleteItemFromFavorites($id);
            return ['success' => 2, 'message' => 'Товар успешно удален из избранного'];
        }
    }

    public function setCompareAction(int $id): array
    {
        // Проверка наличия параметров
        if (empty($id)) {
            return ['success' => 0, 'message' => 'Ошибка при добавлении в список сравнения'];
        }

        $isAlreadyInCompareList = $this->checkIfItemIsInCompareList($id);

        if (!$isAlreadyInCompareList) {
            $this->addToCompareList($id);
            return ['success' => 1, 'message' => 'Товар успешно добавлен в список сравнения'];
        } else {
            $this->deleteItemFromCompareList($id);
            return ['success' => 2, 'message' => 'Товар успешно удален из списка сравнения'];
        }
    }


    /**
     * Функция для проверки, добавлен ли товар в избранное текущим пользователем
     * @param int $itemId
     * @return bool
     */
    private function checkIfItemIsInFavorites(int $itemId): bool
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }

        $result = \All4it\Model\FavoritesTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=ITEM_ID' => $itemId,
            ],
        ]);

        return (bool)$result->fetch();
    }

    private function checkIfItemIsInCompareList(int $itemId): bool
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }

        $result = \All4it\Model\CompareTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=ITEM_ID' => $itemId,
            ],
        ]);

        return (bool)$result->fetch();
    }

    /**
     * @param int $itemId
     * @return bool
     */
    private function addToFavorites(int $itemId)
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }


        $result = \All4it\Model\FavoritesTable::add([
            'USER_ID' => $userId,
            'ITEM_ID' => $itemId,
        ]);

        return $result->isSuccess();
    }

    private function addToCompareList(int $itemId)
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }


        $result = \All4it\Model\CompareTable::add([
            'USER_ID' => $userId,
            'ITEM_ID' => $itemId,
        ]);

        return $result->isSuccess();
    }

    /**
     * Функция для удаления товара из избранного текущим пользователем
     * @param int $itemId
     * @return bool
     */
    private function deleteItemFromFavorites(int $itemId): bool
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }

        $result = \All4it\Model\FavoritesTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=ITEM_ID' => $itemId,
            ],
        ]);

        $favoriteItem = $result->fetch();

        if (!$favoriteItem) {
            return false;
        }

        $deleteResult = \All4it\Model\FavoritesTable::delete($favoriteItem['ID']);

        return $deleteResult->isSuccess();
    }

    private function deleteItemFromCompareList(int $itemId): bool
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }

        $result = \All4it\Model\CompareTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=ITEM_ID' => $itemId,
            ],
        ]);

        $compareItem = $result->fetch();

        if (!$compareItem) {
            return false;
        }

        $deleteResult = \All4it\Model\CompareTable::delete($compareItem['ID']);

        return $deleteResult->isSuccess();
    }

    public function getFavoritesCountAction(): array
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }

        $result = \All4it\Model\FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => ['=USER_ID' => $userId]
        ]);

        return ['success' => 1, 'favoriteCount' => $result->getSelectedRowsCount()];
    }

    public function getCompareCountAction(): array
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }

        $result = \All4it\Model\CompareTable::getList([
            'select' => ['ID'],
            'filter' => ['=USER_ID' => $userId]
        ]);

        return ['success' => 1, 'compareCount' => $result->getSelectedRowsCount()];
    }

    public function getFavoritesListAction(): array
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }

        $result = \All4it\Model\FavoritesTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
            ],
            'select' => ['ITEM_ID'],
        ]);

        $favoritesList = [];
        while ($row = $result->fetch()) {
            $favoritesList[] = $row['ITEM_ID'];
        }

        return ['success' => 1, 'favoritesList' => $favoritesList];
    }

    public function getCompareListAction(): array
    {
        if (\All4it\Helper::isAuthorized()) {
            global $USER;
            $userId = $USER->GetID();
        } else {
            $userId = $_SESSION['fixed_session_id'];
        }

        $result = \All4it\Model\CompareTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
            ],
            'select' => ['ITEM_ID'],
        ]);

        $compareList = [];
        while ($row = $result->fetch()) {
            $compareList[] = $row['ITEM_ID'];
        }

        return ['success' => 1, 'compareList' => $compareList];
    }

    public function getCartTotalQuantityAction(): array
    {
        return ['success' => 1, 'itemCounter' => \All4it\Helper::getCartTotalQuantity()];
    }

    public function setPaginationAction($PAGE, $arParams) {

        \CBitrixComponent::includeComponentClass("all4it:catalog.list");
        $catalogListComponent = new CatalogListComponent;
        $catalogListComponent->newsAjaxPrepareParams($arParams);
        $catalogListComponent->fetchEnumProperties();
        $res = $catalogListComponent->fetchDataFromTable($PAGE);
        $catalogListComponent->processFlags($res);
        $catalogListComponent->formatItemsPrice($res);
        $catalogListComponent->checkInBasket($res);
        $catalogListComponent->prepareResultArray($res);
        return $this->createLayoutSection($res);
    }

    protected function createLayoutSection($res): string
    {
        $html = '';

        foreach ($res as $key => $item) {
            $html .= '
            <div class="card-product">
                <div class="badges-container">';

            foreach ($item['FLAGS_INFO'] as $flag) {
                $html .= '
                    <div class="badge ' . $flag['FLAGS_BADGE'] . '">' . $flag['FLAG_DATA']['VALUE'] . '</div>';
            }

            $html .= '
                </div>
                <div class="card-product__inner">
                    <div class="card-product__head" data-card-action-bar>
                        <a class="card-product__action card-product__action--favorite" data-card-action-btn data-item-id="' . $item['ID'] . '">
                            <svg class="svg ">
                                <use xlink:href="' . SITE_TEMPLATE_PATH . '/svg/sprite.svg#favorite-2"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="card-product__body">
                        <a href="' . $item['DETAIL_PAGE_URL'] . '" class="card-product__img">
                            <img src="' . $item['PREVIEW_PICTURE'] . '" alt=""/>
                        </a>
                        <div class="card-product__info">
                            <a href="' . $item['DETAIL_PAGE_URL'] . '" class="card-product__name" title="' . $item['NAME'] . '">' . $item['NAME'] . '</a>
                            <div class="card-product__descr">' . $item['PREVIEW_TEXT'] . '</div>
                        </div>
                    </div>
                    <div class="card-product__footer">
                        <div class="card-product__price-wrapper">
                            <div class="card-product__warranty">
                                <svg width="16" height="18" viewBox="0 0 16 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5.3 8.45439L7.2184 10.3728L10.6542 6.93698M7.97712 0.899902L1.25 3.69293V7.08823C1.25 11.4823 3.90889 15.4394 7.97712 17.0999C12.0453 15.4394 14.7042 11.4823 14.7042 7.08823V3.69293L7.97712 0.899902Z" stroke="currentColor" stroke-width="1.6" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                ' . $item['GARANTIYA'] . ' ' . ($item['GARANTIYA'] == 24 || $item['GARANTIYA'] == 3 ? 'месяца' : 'месяцев') . '
                            </div>
                            <div class="card-product__price">' . $item['FORMATTED_PRICE'] . '</div>';

            if (!empty($item['NDS'])) {
                $html .= '
                            <div class="card-product__tax">в том числе НДС ' . $item['NDS'] . '%</div>';
            }

            $html .= '
                        </div>
                        <div class="card-product__buttons">
                            <a class="card-product__button card-product__button--plus-decor ' . ($item['IN_BASKET'] ? 'hidden' : '') . '" data-option="' . $item['ID'] . '" id="basket-button">
                                <span>
                                    <svg class="svg cart">
                                        <use xlink:href="' . SITE_TEMPLATE_PATH . '/svg/sprite.svg#cart"></use>
                                    </svg>
                                    <svg class="svg plus">
                                        <use xlink:href="' . SITE_TEMPLATE_PATH . '/svg/sprite.svg#plus"></use>
                                    </svg>
                                </span>
                            </a>
                            <div class="product-config-amount ' . ($item['IN_BASKET'] ? '' : 'hidden') . '" id="panel" data-option="' . $item['ID'] . '">
                                <button class="product-config-amount__button product-config-amount__button_minus" id="minus" type="button" data-option="' . $item['ID'] . '">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="2" viewBox="0 0 14 2" fill="none">
                                        <path d="M12.7926 1H1" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <input class="product-config-amount__control" id="amount" type="number" min="1" max="20" value="' . ($item['BASKET_COUNT'] ?? 1) . '" readonly data-option="' . $item['ID'] . '">
                                <button class="product-config-amount__button product-config-amount__button_plus" id="plus" type="button" data-option="' . $item['ID'] . '">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                        <path d="M12.9996 6.84253H1.20703" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7.10156 1V12.687" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        }

        return $html;
    }





}