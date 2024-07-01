<?

use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Landing\Binding;
use Bitrix\Main\Composite\StaticArea;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\QueryHelper;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class CatalogListComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{

    use \All4it\IblockHelper;

    /**
     * кешируемые ключи arResult
     * @var array()
     */
    protected array $cacheKeys = [];


    private array $arProperties = [];

    private array $arErrors = [];

    private array $arRequiredFields = [];

    private array $arIblockProps = [];

    private array $arIblockLinks = [];


    /**
     * возвращаемые значения
     * @var mixed
     */
    protected $returned;

    public function configureActions(): array
    {
        return [];
    }


    /**
     * подключает языковые файлы
     */
    public function onIncludeComponentLang(): void
    {
        $this->includeComponentLang(basename(__FILE__));
        Loc::loadMessages(__FILE__);
    }

    /**
     * подготавливает входные параметры
     * @param array $this ->arParams
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        return $arParams;
    }

    /**
     * определяет читать данные из кеша или нет
     * @return bool
     */
    protected function readDataFromCache(): bool
    {
        global $USER;
        if ($this->arParams['CACHE_TYPE'] == 'N') {
            return false;
        }


        if (is_array($this->cacheAddon)) {
            $this->cacheAddon[] = $USER->GetUserGroupArray();
        } else {
            $this->cacheAddon = array($USER->GetUserGroupArray());
        }


        return !($this->startResultCache(false, $this->cacheAddon, md5(serialize($this->arParams))));
    }

    /**
     * кеширует ключи массива arResult
     */
    protected function putDataToCache(): void
    {
        if (is_array($this->cacheKeys) && sizeof($this->cacheKeys) > 0) {
            $this->SetResultCacheKeys($this->cacheKeys);
        }
    }

    /**
     * прерывает кеширование
     */
    protected function abortDataCache(): void
    {
        $this->AbortResultCache();
    }

    /**
     * завершает кеширование
     * @return bool
     */
    protected function endCache(): bool
    {
        if ($this->arParams['CACHE_TYPE'] == 'N') {
            return false;
        }

        $this->endResultCache();
    }

    /**
     * проверяет подключение необходиимых модулей
     * @throws \Bitrix\Main\LoaderException
     */
    protected function checkModules(): void
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Bitrix\Main\LoaderException('Модуль iblock не подключен');
        }
        if (!Loader::includeModule('catalog')) {
            throw new \Bitrix\Main\LoaderException('Модуль catalog не подключен');
        }
        if (!Loader::includeModule('sale')) {
            throw new \Bitrix\Main\LoaderException('Модуль sale не подключен');
        }
    }

    /**
     * выполяет действия перед кешированием
     */
    protected function executeProlog(): void
    {
        $this->initIblockHelper(tableObj: $this->arParams['OBJECT']);
    }

    /**
     * проверяет заполнение обязательных параметров
     * @throws Exception
     */
    protected function checkParams(): void
    {
        if (empty($this->arParams['OBJECT']) || !isset($this->arParams['OBJECT'])) {
            throw new Exception('Объект инфоблока не найден');
        }

        if (!is_object($this->arParams['OBJECT'])) {
            throw new Exception('Параметр инфоблока не является объектом');
        }
    }


    /**
     * выполняет действия после выполения компонента, например установка заголовков из кеша
     */
    protected function executeEpilog(): void
    {
    }

    /**
     * Метод getResult() отвечает за получение результатов.
     * В нем вызываются различные подметоды для получения данных, обработки их и подготовки результата.
     *
     * @throws Exception
     */
    protected function getResult(): void
    {
        $this->fetchEnumProperties();
        $res = $this->fetchDataFromTable(0);
        $this->processFlags($res);
        $this->formatItemsPrice($res);
        $this->prepareResultArray($res);
        $this->calculatePagesQuantity();
        $this->getFilterFlags();
    }


    protected function getFilterFlags(): void
    {
        if (!empty($this->arProperties['FLAGS'])) {
            $flags = [];
            foreach ($this->arProperties['FLAGS'] as $flag) {
                // Проверяем, что у массива $flag есть ключ 'XML_ID'
                if (isset($flag['XML_ID'])) {
                    $flags[$flag['XML_ID']] = ['ID' => $flag['ID'], 'VALUE' => $flag['VALUE']];
                }
            }

            $this->arResult['FLAGS_FILTER']['leasing'] = $flags['leasing'];
            $this->arResult['FLAGS_FILTER']['nds'] = $flags['nds'];
            $this->arResult['FLAGS_FILTER']['sale'] = $flags['sale'];
        }
    }

    /**
     * Метод fetchEnumProperties() получает значения перечисляемых свойств из параметра ENUM_PROPERTIES, если он не пустой, и сохраняет их в $this->arProperties.
     * Для этого метод обращается к объекту $this->iblockHelper, вызывая метод getEnumProperty(), передавая ему коды свойств из $this->arParams['ENUM_PROPERTIES'].
     */
    public function fetchEnumProperties(): void
    {
        if (!empty($this->arParams['ENUM_PROPERTIES']) && is_array($this->arParams['ENUM_PROPERTIES'])) {
            $codes = $this->arParams['ENUM_PROPERTIES'];
            $this->arProperties = $this->getEnumProperty(propCode: $codes);
        }
    }

    public function formatItemsPrice(&$res)
    {
        foreach ($res as $key => $item) {
            $res[$key]['FORMATTED_PRICE'] = \All4it\Helper::formatPrice(($item['PRICE']));
        }
    }

    /**
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws LoaderException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\NotImplementedException
     */
    public function checkInBasket(&$res): void
    {


        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),
            \Bitrix\Main\Context::getCurrent()->getSite());
        foreach ($res as $key => $item) {
            $productId = $item['ID'];

            $existingItem = $basket->getExistsItem('catalog', $productId);
            if ($existingItem) {
                $res[$key]['IN_BASKET'] = true;
                $res[$key]['BASKET_COUNT'] = $existingItem->getQuantity();
            }
        }
    }

    /**
     * Метод fetchDataFromTable() извлекает данные из таблицы базы данных.
     * Затем он использует объект $this->iblockHelper для выполнения запроса к базе данных с помощью метода getDataFormTable(), передавая ему необходимые параметры.
     *
     * @return array
     */
    public function fetchDataFromTable($PAGE): array
    {
        $sortAjax = $this->getSortAjax();

        $flagsFilter = $this->getOptionsFilter();
        $filter = [
            $this->arParams['FILTER'],
        ];

        if (!empty($optionsFilter)) {
            foreach ($optionsFilter as $code => $item) {
                $filter[$code] = $item;
            }
        }


        $sections = $this->getSections();


        if (!empty($sections)) {
            $filter['IBLOCK_SECTION_ID'] = $sections;
        }

        if (!empty($flagsFilter)) {
            $filter['FLAGS.VALUE'] = $flagsFilter;
        }


        $res = $this->getDataFormTable(
            select: $this->arParams['SELECT'],
            filter: $filter,
            order: !empty($sortAjax) ? $sortAjax : $this->arParams['ORDER'] ?? [],
            limit: $this->arParams['LIMIT'] ?? 100,
            offset: $PAGE == 0 ? $this->arParams['OFFSET'] : $this->arParams['LIMIT'] * ($PAGE - 1),
            cache: $this->arParams['CACHE'],
            multiplePropsIdArray: $this->arParams['MULTIPLE_PROPS_ID_ARRAY'] ?? [],
            runtime: $this->arParams['RUNTIME'],
        );

        if ($this->arParams['MULTIPLE_FILTER_PROPERTIES']) {
            $this->refactorDataIfFilteredByMultipleProperty(
                resultArray: $res,
                props: $this->arParams['MULTIPLE_FILTER_PROPERTIES'],
                tableObject: $this->arParams['OBJECT']
            );
        }
        $this->arResult['COUNT'] = $this->countTotal;
        return $res;
    }


    private function buildSectionTree($parentId, &$sectionsHierarchy): array
    {
        $sectionsWithChildren = [];

        if (isset($sectionsHierarchy[$parentId])) {
            foreach ($sectionsHierarchy[$parentId] as $childSection) {
                $childId = $childSection['ID'];
                $childSection['CHILDS'] = $this->buildSectionTree($childId, $sectionsHierarchy);
                $sectionsWithChildren[] = $childSection;
            }
        }

        return $sectionsWithChildren;
    }

    private function findSectionInChildren($sectionCode, $hierarchy)
    {
        foreach ($hierarchy as $section) {
            if ($section['CODE'] == $sectionCode) {
                return $section;
            }
            if (!empty($section['CHILDS'])) {
                $foundInSection = $this->findSectionInChildren($sectionCode, $section['CHILDS']);
                if ($foundInSection !== null) {
                    return $foundInSection;
                }
            }
        }
    }

    private function compileSectionsArrayWithRootFilter(array $desiredSection): array
    {
        $finalSections = [];

        foreach ($desiredSection['CHILDS'] as $level1) {
            if (!empty($level1['UF_NOTSHOW'])) {
                $finalSections[] = $level1['ID'];
            }

            foreach ($level1['CHILDS'] as $level2) {
                if (!empty($level2['UF_NOTSHOW'])) {
                    $finalSections[] = $level2['ID'];
                }

                foreach ($level2['CHILDS'] as $level3) {
                    if (!empty($level3['UF_NOTSHOW'])) {
                        $finalSections[] = $level3['ID'];
                    }

                    foreach ($level3['CHILDS'] as $level4) {
                        if (!empty($level4['UF_NOTSHOW'])) {
                            $finalSections[] = $level4['ID'];
                        }
                    }
                }
            }
        }
        $finalSections[] = $desiredSection['ID'];

        return $finalSections;
    }

    private function compileSectionsArray(array $desiredSection): array
    {
        $finalSections = [];
        foreach ($desiredSection['CHILDS'] as $level1) {
            $level1Copy = $level1;
            unset($level1Copy['CHILDS']);
            $finalSections[] = $level1Copy['ID'];
            foreach ($level1['CHILDS'] as $level2) {
                $level2Copy = $level2;
                unset($level2Copy['CHILDS']);
                $finalSections[] = $level2Copy['ID'];
                foreach ($level2['CHILDS'] as $level3) {
                    $level3Copy = $level3;
                    unset($level3Copy['CHILDS']);
                    $finalSections[] = $level3Copy['ID'];
                    foreach ($level3['CHILDS'] as $level4) {
                        $level4Copy = $level4;
                        unset($level4Copy['CHILDS']);
                        $finalSections[] = $level4Copy['ID'];
                    }
                }
            }
        }
        $finalSections[] = $desiredSection['ID'];
        return $finalSections;
    }

    private function checkOnRootFilter(array $desiredSection): bool
    {
        if (!empty($desiredSection['UF_NOTSHOW'])) {
            return true;
        }

        foreach ($desiredSection['CHILDS'] as $level1) {
            if (!empty($level1['UF_NOTSHOW'])) {
                return true;
            }
            foreach ($level1['CHILDS'] as $level2) {
                if (!empty($level2['UF_NOTSHOW'])) {
                    return true;
                }
                foreach ($level2['CHILDS'] as $level3) {
                    if (!empty($level3['UF_NOTSHOW'])) {
                        return true;
                    }
                    foreach ($level3['CHILDS'] as $level4) {
                        if (!empty($level4['UF_NOTSHOW'])) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    private function getSections(): array
    {
        if (!$this->arParams['SELECTED_CODE']) {
            return [];
        }


        $query = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\Model\Section::compileEntityByIblock(CATALOG_SECTION_IBLOCKID));
        $rsSection = $query
            ->setSelect(['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'UF_NOTSHOW'])
            ->setFilter(['IBLOCK_ID' => CATALOG_SECTION_IBLOCKID])
            ->fetchAll();

        $sectionsHierarchy = [];

        foreach ($rsSection as $item) {
            $parentId = $item['IBLOCK_SECTION_ID'] ?: 0;
            $sectionsHierarchy[$parentId][] = $item;
        }

        $hierarchy = $this->buildSectionTree(0, $sectionsHierarchy);
        $desiredSection = $this->findSectionInChildren($this->arParams['SELECTED_CODE'], $hierarchy);
        $checkOnRootFilter = $this->checkOnRootFilter($desiredSection);

        if ($checkOnRootFilter) {
            return $this->compileSectionsArrayWithRootFilter($desiredSection);
        } else {
            return $this->compileSectionsArray($desiredSection);
        }
    }

    public function newsAjaxPrepareParams($arParams): array
    {
        try {
            \CBitrixComponent::includeComponentClass("all4it:catalog.list");
            $this->initIblockHelper(tableObj: new \Bitrix\Iblock\Elements\ElementCatalog1CTable);
            $arParams['RUNTIME'] = [
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
            ];
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        $arParams['OBJECT'] = new \Bitrix\Iblock\Elements\ElementCatalog1CTable;
        return $this->arParams = $arParams;
    }


    /**
     * Метод getSortAjax() формирует массив сортировки из сессионных данных $_SESSION['CATALOG_SORT'], исключая сортировку по цене.
     * Он возвращает массив, который будет использован при сортировке данных из таблицы.
     *
     * @return array
     */
    protected function getSortAjax(): array
    {
        $sortAjax = [];

        if ($_SESSION['CATALOG_SORT']) {
            foreach ($_SESSION['CATALOG_SORT'] as $code => $item) {
                $sortAjax[$code] = $item;
            }
        }

        return $sortAjax;
    }

    protected function getOptionsFilter(): array
    {
        if ($_SESSION['FLAGS_FILTER']) {
            return $_SESSION['FLAGS_FILTER'];
        } else {
            return [];
        }
    }


    /**
     * Метод processFlags(array &$res) обрабатывает флаги товаров.
     * Он проверяет наличие флагов у каждого товара и добавляет информацию о них в массив результатов.
     *
     * @param array $res
     */
    public function processFlags(array &$res): void
    {
        foreach ($res as $key => $item) {
            $res[$key]['FLAGS_INFO'] = [];
            if (!empty($item['FLAGS_VALUE'])) {
                foreach ($item['FLAGS_VALUE'] as $flagId) {
                    if (isset($this->arProperties['FLAGS'][$flagId])) {
                        $flagValue = $this->arProperties['FLAGS'][$flagId];
                        $badgeClass = $this->getBadgeClass($flagValue['VALUE']);

                        $flagExists = false;
                        foreach ($res[$key]['FLAGS_INFO'] as $info) {
                            if ($info['FLAGS_BADGE'] === $badgeClass) {
                                $flagExists = true;
                                break;
                            }
                        }

                        if (!$flagExists) {
                            $res[$key]['FLAGS_INFO'][] = [
                                'FLAG_DATA' => $flagValue,
                                'FLAGS_BADGE' => $badgeClass
                            ];
                        }
                    }
                }
            }
        }

        foreach ($res as $key => $item) {
            $res[$key]['FLAGS_DETAIL_INFO'] = [];
            if (!empty($item['FLAGS_DETAIL_VALUE'])) {
                foreach ($item['FLAGS_DETAIL_VALUE'] as $flagDetailId) {
                    if (isset($this->arProperties['FLAGS_DETAIL'][$flagDetailId])) {
                        $flagDetailValue = $this->arProperties['FLAGS_DETAIL'][$flagDetailId];
                        $badgeClass = $this->getBadgeClass($flagDetailValue['VALUE']);

                        $flagExists = false;
                        foreach ($res[$key]['FLAGS_DETAIL_INFO'] as $info) {
                            if ($info['FLAGS_BADGE'] === $badgeClass) {
                                $flagExists = true;
                                break;
                            }
                        }

                        if (!$flagExists) {
                            $res[$key]['FLAGS_DETAIL_INFO'][] = [
                                'FLAG_DATA' => $flagDetailValue,
                                'FLAGS_BADGE' => $badgeClass
                            ];
                        }
                    }
                }
            }
        }
    }

    private function getBadgeClass($value)
    {
        switch ($value) {
            case 'В наличии':
                return 'badge_in-stock';
            case 'Скидка':
                return 'badge_discount';
            case 'В пути':
                return 'badge_on-the-way';
            case 'НДС':
                return 'badge_vat';
            case 'Лизинг':
                return 'badge_leasing';
            case 'Гарантия':
                return 'badge_warranty';
            default:
                return '';
        }
    }

    /**
     * Метод prepareResultArray(array $res) подготавливает результирующий массив $this->arResult.
     * Он добавляет информацию об авторизации, полученные товары и перечисляемые свойства.
     *
     * @param array $res
     */
    public function prepareResultArray(array $res): void
    {
        $this->checkInBasket($res);
        $this->arResult['AUTHORIZED'] = \All4it\Helper::isAuthorized();
        $this->arResult['ITEMS'] = $res;
        $this->arResult['ENUM_PROPERTIES'] = $this->arProperties ?? [];

        $this->cacheKeys = ['ITEMS', 'ENUM_PROPERTIES'];
    }

    protected function calculatePagesQuantity(): void
    {
        $this->arResult['PAGINATION_PAGES_QUANTITY'] = floor($this->arResult['COUNT'] / $this->arParams['LIMIT']);

        if ($this->arResult['COUNT'] % $this->arParams['LIMIT'] !== 0) {
            $this->arResult['PAGINATION_PAGES_QUANTITY'] += 1;
        }
    }


    public function executeComponent()
    {
        global $APPLICATION;
        try {
            $this->checkModules();
            $this->checkParams();
            $this->executeProlog();

            if (!$this->readDataFromCache()) {
                $this->getResult();
                $this->putDataToCache();
                $this->includeComponentTemplate();
            }
            $this->executeEpilog();

            return $this->returned;
        } catch (Exception $e) {
            $this->abortDataCache();
            ShowError($e->getMessage());
        }
    }


}