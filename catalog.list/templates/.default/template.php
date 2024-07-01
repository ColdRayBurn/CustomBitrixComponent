<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
?>


<?php if (!str_contains($_SERVER['REQUEST_URI'], 'konfigurator')) { ?>

    <div class="catalog catalog_grid">
        <div class="catalog-header">
            <div class="catalog-header-sort">
                <div class="catalog-header-sort__item">
                    <button class="select button button-select sort <?= $_SESSION['CATALOG_SORT']['DATE_CREATE'] ? 'active' : '' ?>"
                            id="sort-date" data-sort="<?= $_SESSION['CATALOG_SORT']['DATE_CREATE'] ?? 'ASC' ?>"
                            data-key="DATE_CREATE">Новинки
                        <svg width="11" height="7" viewBox="0 0 11 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path class="arrow-icon <?= $_SESSION['CATALOG_SORT']['DATE_CREATE'] == 'ASC' ? 'flip' : '' ?>"
                                  d="M1 0.75L5.5 5.25L10 0.75" stroke="#0F6DA1" stroke-width="1.4"/>
                        </svg>
                    </button>
                </div>
                <div class="catalog-header-sort__item">
                    <button class="select button button-select sort <?= $_SESSION['CATALOG_SORT']['PRICE_LIST.PRICE'] ? 'active' : '' ?>"
                            id="sort-price" data-sort="<?= $_SESSION['CATALOG_SORT']['PRICE_LIST.PRICE'] ?? 'ASC' ?>"
                            data-key="PRICE_LIST.PRICE">Цена
                        <svg width="11" height="7" viewBox="0 0 11 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path class="arrow-icon <?= $_SESSION['CATALOG_SORT']['PRICE_LIST.PRICE'] == 'ASC' ? 'flip' : '' ?>"
                                  d="M1 0.75L5.5 5.25L10 0.75" stroke="#0F6DA1" stroke-width="1.4"/>
                        </svg>
                    </button>
                </div>
                <div class="catalog-header-sort__item">
                    <button class="select button button-select sort <?= $_SESSION['CATALOG_SORT']['NAME'] ? 'active' : '' ?>"
                            id="sort-alphabet" data-sort="<?= $_SESSION['CATALOG_SORT']['NAME'] ?? 'ASC' ?>"
                            data-key="NAME">По алфавиту
                        <svg width="11" height="7" viewBox="0 0 11 7" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path class="arrow-icon <?= $_SESSION['CATALOG_SORT']['NAME'] == 'ASC' ? 'flip' : '' ?>"
                                  d="M1 0.75L5.5 5.25L10 0.75" stroke="#0F6DA1"
                                  stroke-width="1.4"/>
                        </svg>
                    </button>
                </div>

                <? foreach ($arResult['FLAGS_FILTER'] as $code => $value) {
                    $active = '';
                    if ($_SESSION['FLAGS_FILTER']) {
                        if (in_array($value['ID'], $_SESSION['FLAGS_FILTER']))
                            $active = 'active';
                    }

                    ?>
                    <div class="catalog-header-sort__item">
                        <button class="select button button-select filter <?= $active ?>"
                                data-key="<?= $value['ID'] ?>"><?= $value['VALUE'] ?></button>
                    </div>
                <? } ?>

            </div>

            <div class="catalog-header-view-buttons">
                <button class="catalog-header-view-buttons__item catalog-header-view-buttons__item_grid" type="button">
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="10" height="10" rx="3" fill="currenColor"/>
                        <rect y="12" width="10" height="10" rx="3" fill="currentColor"/>
                        <rect x="12" width="10" height="10" rx="3" fill="currentColor"/>
                        <rect x="12" y="12" width="10" height="10" rx="3" fill="currentColor"/>
                    </svg>
                </button>
                <button class="catalog-header-view-buttons__item catalog-header-view-buttons__item_list catalog-header-view-buttons__item_active"
                        type="button">
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="22" height="10" rx="3" fill="currentColor"/>
                        <rect y="12" width="22" height="10" rx="3" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php if (!empty($arResult['ITEMS'])) { ?>
            <div class="catalog__items">
                <? foreach ($arResult["ITEMS"] as $key => $item) { ?>

                    <div class="card-product">
                        <div class="badges-container">
                            <? foreach ($item['FLAGS_INFO'] as $flag) { ?>
                                <div class="badge <?= $flag['FLAGS_BADGE'] ?>"><?= $flag['FLAG_DATA']['VALUE'] ?></div>
                            <?php } ?>

                        </div>
                        <div class="card-product__inner">
                            <div class="card-product__head" data-card-action-bar>
                                <a class="card-product__action  card-product__action--compare" data-card-action-btn
                                   data-item-id="<?= $item['ID'] ?>">
                                </a>
                                <a class="card-product__action card-product__action--favorite" data-card-action-btn
                                   data-item-id="<?= $item['ID'] ?>">
                                    <svg class="svg ">
                                        <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/svg/sprite.svg#favorite-2"></use>
                                    </svg>
                                </a>
                            </div>
                            <div class="card-product__body">
                                <a href="<?= $item['DETAIL_PAGE_URL'] ?>" class="card-product__img">
                                    <img src="<?= $item['PREVIEW_PICTURE'] ?>" alt=""/>
                                </a>
                                <div class="card-product__info">
                                    <a href="<?= $item['DETAIL_PAGE_URL'] ?>" class="card-product__name"
                                       title="<?= $item['NAME'] ?>"><?= $item['NAME'] ?></a>
                                    <div class="card-product__descr">
                                        <?= $item['PREVIEW_TEXT'] ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-product__footer">
                                <div class="card-product__price-wrapper">
                                    <div class="card-product__warranty">
                                        <svg width="16" height="18" viewBox="0 0 16 18" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path d="M5.3 8.45439L7.2184 10.3728L10.6542 6.93698M7.97712 0.899902L1.25 3.69293V7.08823C1.25 11.4823 3.90889 15.4394 7.97712 17.0999C12.0453 15.4394 14.7042 11.4823 14.7042 7.08823V3.69293L7.97712 0.899902Z"
                                                  stroke="currentColor" stroke-width="1.6" stroke-miterlimit="10"
                                                  stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <?= $item['GARANTIYA'] ?> <?= $item['GARANTIYA'] == 24 || $item['GARANTIYA'] == 3 ? 'месяца' : 'месяцев' ?>
                                    </div>
                                    <div class="card-product__price"><?= $item['FORMATTED_PRICE'] ?></div>
                                    <? if (!empty($item['NDS'])) { ?>
                                        <div class="card-product__tax">в том числе НДС <?= $item['NDS'] ?>%</div>
                                    <? } ?>
                                </div>
                                <div class="card-product__buttons">

                                    <a class="card-product__button card-product__button--plus-decor <?= $item['IN_BASKET'] ? "hidden" : ""?>"
                                       data-option="<?= $item['ID'] ?>" id="basket-button">
                        <span> <svg class="svg cart">
  <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/svg/sprite.svg#cart"></use>
</svg>
 <svg class="svg plus">
  <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/svg/sprite.svg#plus"></use>
</svg>
 </span>
                                    </a>
                                    <div class="product-config-amount <?= $item['IN_BASKET'] ? "" : "hidden"?>" id="panel" data-option="<?= $item['ID'] ?>">
                                        <button class="product-config-amount__button product-config-amount__button_minus"
                                                id="minus"
                                                type="button"
                                                data-option="<?= $item['ID'] ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="2"
                                                 viewBox="0 0 14 2"
                                                 fill="none">
                                                <path d="M12.7926 1H1" stroke="currentColor" stroke-width="1.4"
                                                      stroke-miterlimit="10"
                                                      stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                        <input class="product-config-amount__control" id="amount" type="number" min="1"
                                               max="20"
                                               value="<?= $item['BASKET_COUNT'] ?? 1?>" readonly
                                               data-option="<?= $item['ID'] ?>">
                                        <button class="product-config-amount__button product-config-amount__button_plus"
                                                id="plus"
                                                type="button"
                                                data-option="<?= $item['ID'] ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                 viewBox="0 0 14 14"
                                                 fill="none">
                                                <path d="M12.9996 6.84253H1.20703" stroke="currentColor"
                                                      stroke-width="1.4"
                                                      stroke-miterlimit="10" stroke-linecap="round"
                                                      stroke-linejoin="round"/>
                                                <path d="M7.10156 1V12.687" stroke="currentColor" stroke-width="1.4"
                                                      stroke-miterlimit="10" stroke-linecap="round"
                                                      stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <? } ?>
            </div>
        <? } ?>

        <div class="catalog-pagination" id="pagination-container">
            <a class="catalog-pagination__button prev" id="button-prev">
                <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg"
                     transform="scale(-1, 1)">
                    <path d="M1 10.5L6 6L1 1.5" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </a>
            <a class="catalog-pagination__button next" id="button-next">
                <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 10.5L6 6L1 1.5" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </a>

        </div>
    </div>
<? } ?>

<? unset($arParams['RUNTIME']);
unset($arParams['~RUNTIME']); ?>

<script>

    let paginationQuantity = <?= $arResult['PAGINATION_PAGES_QUANTITY']?>;
    let arParams = '<?= json_encode($arParams)?>';

</script>
