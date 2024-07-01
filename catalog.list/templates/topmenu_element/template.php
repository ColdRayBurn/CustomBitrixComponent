<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
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
<?php if (!empty($arResult['ITEMS'])) {
    $item = $arResult['ITEMS'][0];
?>
<div class="catalog-popup__best-seller">
            <div class="card-product">
                <div class="card-product__inner">
                    <div class="card-product__head" data-card-action-bar>
<!--                        <a class="card-product__action" data-card-action-btn>-->
<!--                            <svg class="svg">-->
<!--                                <use xlink:href="--><?php //= SITE_TEMPLATE_PATH ?><!--/svg/sprite.svg#graph"></use>-->
<!--                            </svg>-->
<!--                        </a>-->
                        <a class="card-product__action card-product__action--favorite" data-card-action-btn data-item-id="<?= $item['ID'] ?>">
                            <svg class="svg ">
                                <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/svg/sprite.svg#favorite-2"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="card-product__body">
                        <div class="card-product__img">
                            <img src="<?=$item['PREVIEW_PICTURE']?>" alt=""/>
                        </div>
                        <div class="card-product__info">
                            <a href="<?=$item['DETAIL_PAGE_URL']?>" class="card-product__name" title="HPE Synergy D3940 12Gb"><?=$item['NAME']?></a>
                            <div class="card-product__descr">
                                <?=$item['PREVIEW_TEXT']?>
</div>
                        </div>
                    </div>
                    <div class="card-product__footer">
                        <div class="card-product__price"><?= $item['FORMATTED_PRICE']?></div>
                        <div class="card-product__buttons">
                            <a class="card-product__button card-product__button--plus-decor" data-option="<?= $item['ID'] ?>">
                      <span> <svg class="svg cart">
      <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/svg/sprite.svg#cart"></use>
    </svg>
     <svg class="svg plus">
      <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/svg/sprite.svg#plus"></use>
    </svg>
     </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?}?>