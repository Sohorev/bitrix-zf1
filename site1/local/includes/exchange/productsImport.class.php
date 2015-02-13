<?

/**
 * Class productsImport
 */
class productsImport extends import {

    /**
     * максимальное количество возможных цветов, все, что сверх должно игнорироваться с генерацией ошибки
     */
    const MAX_COLORS = 16;

    /**
     *
     */
    const DEFAULT_FETCH_LIMIT = 10000;

    /**
     * значение поля из связи с таблицей "назначения"
     * @var string
     */
    protected $assign = '';

    /**
     * лимит на количество выбираемых элементов по-умолчанию
     * @var int
     */
    public $countLimit = self::DEFAULT_FETCH_LIMIT;
    /**
     * сколько всего элементов необходимо импортировать
     * @var int
     */
    public $countImport = 0;

    /**
     * @var int
     */
    public $importType = 0; // 0 - automatic, 1 - manual

    /**
     * массив коллекций встречающихся в процессе импорта
     * @var array
     */
    protected $arCollections = array();

    /**
     * массив идентификаторов товара встречающихся в запросе
     * @var array
     */
    protected $arProductXmlId = array();

    /**
     * @var array
     */
    protected $arValues = array('code' => array('code' => 'XML_ID', 'value' => ''), 'Name' => array('code' => 'NAME', 'value' => ''), 'Article' => array('code' => 'ARTICLE', 'value' => ''), 'Brand' => array('code' => 'BRAND_ID', 'value' => ''), 'collection' => array('code' => 'COLLECTION_ID', 'value' => ''), 'number_packaged' => array('code' => 'NUMBER_PACKAGED', 'value' => ''), 'length' => array('code' => 'LENGTH', 'value' => ''), 'width' => array('code' => 'WIDTH', 'value' => ''), 'height' => array('code' => 'HEIGHT', 'value' => ''), 'weight' => array('code' => 'WEIGHT', 'value' => ''), 'volume' => array('code' => 'VOLUME', 'value' => ''), 'country' => array('code' => 'COUNTRY_ID', 'value' => ''), 'Color1' => array('code' => 'COLOR1_ID', 'value' => ''), 'Color2' => array('code' => 'COLOR2_ID', 'value' => ''), 'Color3' => array('code' => 'COLOR3_ID', 'value' => ''), 'Color4' => array('code' => 'COLOR4_ID', 'value' => ''), 'barcode' => array('code' => 'BARCODE', 'value' => ''), 'category' => array('code' => 'CATEGORY_ID', 'value' => ''), 'pricecategory' => array('code' => 'PRICE_CATEGORY_ID', 'value' => ''), 'material1' => array('code' => 'MATERIAL1_ID', 'value' => ''), 'material2' => array('code' => 'MATERIAL2_ID', 'value' => ''), 'material3' => array('code' => 'MATERIAL3_ID', 'value' => ''), 'material4' => array('code' => 'MATERIAL4_ID', 'value' => ''), 'rubber' => array('code' => 'RUBBER', 'value' => ''), 'stitching' => array('code' => 'STITCHING', 'value' => ''), 'packaging' => array('code' => 'PACKAGING', 'value' => ''), 'format2' => array('code' => 'FORMAT2_ID', 'value' => ''), 'stamping' => array('code' => 'STAMPING', 'value' => ''), 'price' => array('code' => 'PRICE', 'value' => ''), 'priceue' => array('code' => 'PRICEUE', 'value' => ''), 'balance' => array('code' => 'BALANCE', 'value' => ''), 'reserv' => array('code' => 'RESERV', 'value' => ''), 'ComingReserv' => array('code' => 'COMING_RESERVE', 'value' => ''));

    /**
     * @param bool $importId
     */
    public function __construct($importId = false) {
        parent::__getExchangeSettings();
        $this->__connectToRemoteDb();
        $this->__setImportSettings($importId);
        $this->logFolder .= '/productsImport';
        if (!file_exists($this->logFolder)) {
            mkdir($this->logFolder);
        }
        $this->logFile = $this->logFolder . '/' . date("Ymd-His", $this->date) . '.log';
    }


    /**
     * @param $importId
     */
    protected function __setImportSettings($importId) {

        if ($importId) {
            $this->importId = $importId;
        } else {
            $this->importId = time();
        }

        $this->countImport = $this->importGetRowsCount();
    }

    /**
     * @return int
     */
    protected function importGetRowsCount() {
        $query = 'SELECT count(*) as count FROM ' . self::IMPORT_PRODUCTS_TABLE . " where mark=0x01";
        $query = $this->toWindows($query);
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Ошибка получения количества импортируемых товаров!');
        }
        if ($row = mssql_fetch_assoc($result)) {
            return $row['count'];
        }

        return 0;
    }

    /**
     * @param $colorXmlId
     * @return bool
     */
    public function colorExist($colorXmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_COLORS, "XML_ID" => self::IMPORT_COLORS_TABLE . '_' . $colorXmlId);
        $arSelect = array("XML_ID", "NAME");
        $rsColor = CIBlockElement::GetList(array(), $arFilter, null, null, $arSelect);

        if ($arColor = $rsColor->Fetch()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $colorXmlId
     * @return bool
     */
    protected function importColor($colorXmlId) {
        // вытаскиваем параметры цвета из исходной базы
        $query = 'SELECT * FROM ' . self::IMPORT_COLORS_TABLE . ' WHERE code=' . $colorXmlId;
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            // если заданный в товаре цвет не хранится в справочнике цветов
            $this->lastError = "Невозможно сопоставить цвет с кодом {$colorXmlId}!";
            return false;
        }
        $arImportColor = mssql_fetch_assoc($result);
        $arImportColor['descr'] = $this->toUnicode($arImportColor['descr']);

        // запоминаем цвет в инфоблоке
        $el = new CIBlockElement;
        $arFields = array('NAME' => $arImportColor['descr'], 'XML_ID' => self::IMPORT_COLORS_TABLE . '_' . $arImportColor['code'], 'IBLOCK_ID' => self::IB_COLORS);
        if (!$el->Add($arFields, false, false, false)) {
            // если не смогли сохранить цвет в инфоблоке
            $this->lastError = "Ошибка сохранения цвета {$arImportColor['code']} => {$arImportColor['descr']} " . strip_tags($el->LAST_ERROR);
            return false;
        }
        return true;
    }

    /**
     * @return array|bool|CDBResult|mixed|string
     */
    protected function colorsCount() {
        $arFilter = array('IBLOCK_ID' => self::IB_COLORS);
        $count = CIBlockElement::GetList(false, $arFilter, array());
        return $count;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function materialExist($xmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_MATERIALS, "XML_ID" => self::IMPORT_MATERIALS_TABLE . '_' . $xmlId);
        $count = CIBlockElement::GetList(false, $arFilter, array());

        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $xmlId
     * @return bool
     */
    protected function importMaterial($xmlId) {
        $query = 'SELECT * FROM ' . self::IMPORT_MATERIALS_TABLE . ' WHERE code=' . $xmlId;
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $this->lastError = "Невозможно сопоставить материал с кодом {$xmlId}!";
            return false;
        }
        $arImport = mssql_fetch_assoc($result);
        $arImport['descr'] = $this->toUnicode($arImport['descr']);

        $el = new CIBlockElement;
        $arFields = array('NAME' => $arImport['descr'], 'XML_ID' => self::IMPORT_MATERIALS_TABLE . '_' . $arImport['code'], 'IBLOCK_ID' => self::IB_MATERIALS);
        if (!$el->Add($arFields, false, false, false)) {
            $this->lastError = "Ошибка сохранения материала {$arImport['code']} => {$arImport['descr']} " . strip_tags($el->LAST_ERROR);
            return false;
        }

        return true;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function brandExist($xmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_BRANDS, "XML_ID" => self::IMPORT_BRANDS_TABLE . '_' . $xmlId);
        $count = CIBlockElement::GetList(false, $arFilter, array());

        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $xmlId
     * @return bool
     */
    protected function importBrand($xmlId) {
        $query = 'SELECT * FROM ' . self::IMPORT_BRANDS_TABLE . ' WHERE code=' . $xmlId;
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $this->lastError = "Невозможно сопоставить бренд с кодом {$xmlId}!";
            return false;
        }

        $arImport = mssql_fetch_assoc($result);
        $arImport['descr'] = $this->toUnicode($arImport['descr']);
        $this->translit($arImport['descr']) . "<br/>";

        $el = new CIBlockElement;
        $arFields = array('NAME' => $this->parseBrandName($arImport['descr']), 'XML_ID' => self::IMPORT_BRANDS_TABLE . '_' . $arImport['code'], 'IBLOCK_ID' => self::IB_BRANDS, 'CODE' => $this->translit($arImport['descr']));
        if (!$el->Add($arFields, false, false, false)) {
            $this->lastError = "Ошибка сохранения бренда {$arImport['code']} => {$arImport['descr']} " . strip_tags($el->LAST_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Убираем примечания из названия бренда вида BrandName (BrandNameRU), Country
     * @param $str
     * @return mixed
     */
    public static function parseBrandName($str) {
        $str = preg_replace("/\s\(.*\)/u", "", $str);
        $str = preg_replace("/,\s.*/u", "", $str);

        return $str;
    }

    /**
     * @param $brandXmlId
     * @return bool
     */
    public function collectionSectionExist($brandXmlId) {
        $arFilter = array('IBLOCK_ID' => self::IB_BRANDS, 'XML_ID' => self::IMPORT_BRANDS_TABLE . '_' . $brandXmlId);
        $dbBrand = CIBlockElement::GetList(false, $arFilter, false, false, array('CODE'));

        if (!$arBrand = $dbBrand->Fetch()) {
            $this->lastError = "Бренд XML_ID => {$brandXmlId} не найден!";
            return false;
        }

        $brandCode = $arBrand['CODE'];

        $arFilter = array('CODE' => $brandCode, 'IBLOCK_ID' => self::IB_COLLECTIONS);
        $dbSection = CIBlockSection::GetList(array(), $arFilter);

        if (!$row = $dbSection->Fetch()) {
            return false;
        }

        return true;
    }

    /**
     * @param $brandXmlId
     * @return bool
     */
    protected function collectionSectionGetId($brandXmlId) {
        $arFilter = array('IBLOCK_ID' => self::IB_BRANDS, 'XML_ID' => self::IMPORT_BRANDS_TABLE . '_' . $brandXmlId);
        $dbBrand = CIBlockElement::GetList(false, $arFilter, false, false, array('CODE'));

        if (!$arBrand = $dbBrand->Fetch()) {
            $this->lastError = "Бренд XML_ID => {$brandXmlId} не найден!";
            return false;
        }

        $brandCode = $arBrand['CODE'];

        $arFilter = array('CODE' => $brandCode, 'IBLOCK_ID' => self::IB_COLLECTIONS);
        $dbSection = CIBlockSection::GetList(array(), $arFilter);

        if (!$row = $dbSection->Fetch()) {
            $this->lastError = 'Раздел коллекции для бренда XML_ID => ' . $brandXmlId . 'не найден!';
            return false;
        }

        return $row['ID'];
    }

    /**
     * @param $brandXmlId
     * @return bool|int
     */
    protected function collectionSectionAdd($brandXmlId) {
        $arFilter = array('IBLOCK_ID' => self::IB_BRANDS, 'XML_ID' => self::IMPORT_BRANDS_TABLE . '_' . $brandXmlId);
        $dbBrand = CIBlockElement::GetList(false, $arFilter, false, false, array('NAME', 'CODE'));

        if (!$arBrand = $dbBrand->Fetch()) {
            $this->lastError = "Бренд XML_ID => {$brandXmlId} не найден!";
            return false;
        }

        $brandName = $arBrand['NAME'];
        $brandCode = $arBrand['CODE'];

        $arFields = array('CODE' => $brandCode, // мнемонический идентификатор раздела колекций совпадает с кодом бренда
            'NAME' => $brandName, // название раздела коллекций совпадает с названием бренда
            'IBLOCK_ID' => IB_COLLECTIONS,);
        $section = new CIBlockSection;
        if (!$id = $section->Add($arFields)) {
            $this->lastError = 'Не удалось добавить раздел коллекций: ' . strip_tags($section->LAST_ERROR);
        }

        return $id;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function collectionGetId($xmlId) {
        $arFilter = array('IBLOCK_ID' => self::IB_COLLECTIONS, 'XML_ID' => $xmlId);
        $arSelect = array('ID');

        $dbElement = CIBlockElement::GetList(null, $arFilter, null, null, $arSelect);

        if (!$arElement = $dbElement->Fetch()) {
            return false;
        }

        return $arElement['ID'];
    }

    /**
     * @param $xmlId
     * @param $brandXmlId
     * @return bool
     */
    public function collectionExist($xmlId, $brandXmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_COLLECTIONS, "XML_ID" => self::IMPORT_COLLECTIONS_TABLE . '_' . $brandXmlId . $xmlId, "PROPERTY_BRAND_ID" => self::IMPORT_BRANDS_TABLE . '_' . $brandXmlId);
        $count = CIBlockElement::GetList(false, $arFilter, array());

        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $xmlId
     * @param $brandXmlId
     * @return bool
     */
    protected function importCollection($xmlId, $brandXmlId) {
        $query = 'SELECT * FROM ' . self::IMPORT_COLLECTIONS_TABLE . ' WHERE code=' . $xmlId;
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $this->lastError = "Невозможно сопоставить коллекцию с кодом {$xmlId}!";
            return false;
        }

        $arImport = mssql_fetch_assoc($result);
        $arImport['descr'] = $this->toUnicode($arImport['descr']);

        if (!$sectionId = $this->collectionSectionGetId($brandXmlId)) {
            //TODO: ERROR
            echo $this->lastError;
        }

        $el = new CIBlockElement;
        $arFields = array('NAME' => $arImport['descr'], 'XML_ID' => self::IMPORT_COLLECTIONS_TABLE . '_' . $brandXmlId . $arImport['code'], 'IBLOCK_ID' => self::IB_COLLECTIONS, 'IBLOCK_SECTION_ID' => $sectionId, 'CODE' => $this->translit($arImport['descr']), 'PROPERTY_VALUES' => array('BRAND_ID' => self::IMPORT_BRANDS_TABLE . '_' . $brandXmlId));

        if (!$el->Add($arFields, false, false, false)) {
            $this->lastError = "Ошибка сохранения коллекции {$arImport['code']} => {$arImport['descr']} " . strip_tags($el->LAST_ERROR);
            return false;
        }

        return true;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function countryExist($xmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_COUNTRIES, "XML_ID" => self::IMPORT_COUNTRIES_TABLE . '_' . $xmlId,);
        $count = CIBlockElement::GetList(false, $arFilter, array());

        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $xmlId
     * @return bool
     */
    protected function countryImport($xmlId) {
        $query = 'SELECT * FROM ' . self::IMPORT_COUNTRIES_TABLE . ' WHERE code=' . $xmlId;
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $this->lastError = "Невозможно сопоставить страну с кодом {$xmlId}!";
            return false;
        }
        $arImport = mssql_fetch_assoc($result);
        $arImport['descr'] = $this->toUnicode($arImport['descr']);

        $el = new CIBlockElement;
        $arFields = array('NAME' => $arImport['descr'], 'XML_ID' => self::IMPORT_COUNTRIES_TABLE . '_' . $arImport['code'], 'IBLOCK_ID' => self::IB_COUNTRIES);
        if (!$el->Add($arFields, false, false, false)) {
            $this->lastError = "Ошибка сохранения страны {$arImport['code']} => {$arImport['descr']} " . strip_tags($el->LAST_ERROR);
            return false;
        }

        return true;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function categoryExist($xmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_PRODUCTS, "XML_ID" => self::IMPORT_CATEGORIES_TABLE . '_' . $xmlId,);
        $dbSection = CIBlockSection::GetList(null, $arFilter);

        if (!$row = $dbSection->Fetch()) {
            return false;
        }

        return true;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    protected function categoryImport($xmlId) {
        $query = 'SELECT * FROM ' . self::IMPORT_CATEGORIES_TABLE . ' WHERE code=' . $xmlId;
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $this->lastError = "Невозможно сопоставить категорию с кодом {$xmlId}!";
            return false;
        }
        $arImport = mssql_fetch_assoc($result);
        $arImport['descr'] = $this->toUnicode($arImport['descr']);

        $arFields = array('IBLOCK_ID' => self::IB_PRODUCTS, 'XML_ID' => self::IMPORT_CATEGORIES_TABLE . '_' . $xmlId, 'NAME' => $arImport['descr'], 'CODE' => $this->translit($arImport['descr']));

        $section = new CIBlockSection;
        if (!$sectionId = $section->Add($arFields)) {
            $this->lastError = "Невозможно создать категорию XML_ID => {$xmlId}: " . strip_tags($section->LAST_ERROR);
            return false;
        }

        return true;
    }

    /**
     * @param $xmlId
     * @return mixed
     */
    public function getCategoryId($xmlId) {
        $arFilter = array('IBLOCK_ID' => self::IB_PRODUCTS, 'XML_ID' => self::IMPORT_CATEGORIES_TABLE . '_' . $xmlId);
        $arSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'IBLOCK_ID'))->Fetch();
        return $arSection['ID'];
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function priceCategoryExist($xmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_PRICE_CATEGORIES, "XML_ID" => self::IMPORT_PRICE_CATEGORIES_TABLE . '_' . $xmlId,);
        $count = CIBlockElement::GetList(false, $arFilter, array());

        if ($count == 1) {
            return true;
        }

        return false;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    protected function priceCategoryImport($xmlId) {
        $query = 'SELECT * FROM ' . self::IMPORT_PRICE_CATEGORIES_TABLE . ' WHERE code=' . $xmlId;
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $this->lastError = "Невозможно сопоставить ценовую категорию с кодом {$xmlId}!";
            return false;
        }
        $arImport = mssql_fetch_assoc($result);
        $arImport['descr'] = $this->toUnicode($arImport['descr']);

        $el = new CIBlockElement;
        $arFields = array('NAME' => $arImport['descr'], 'XML_ID' => self::IMPORT_PRICE_CATEGORIES_TABLE . '_' . $arImport['code'], 'IBLOCK_ID' => self::IB_PRICE_CATEGORIES);
        if (!$el->Add($arFields, false, false, false)) {
            $this->lastError = "Ошибка сохранения ценовой категории {$arImport['code']} => {$arImport['descr']} " . strip_tags($el->LAST_ERROR);
            return false;
        }

        return true;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function assignExist($xmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_ASSIGNS, "XML_ID" => self::IMPORT_ASSIGNS_TABLE . '_' . $xmlId,);
        $dbResult = CIBlockElement::GetList(false, $arFilter, false, false, array('XML_ID'));
        $arResult = $dbResult->Fetch();

        if ($arResult['XML_ID']) {
            return $arResult['XML_ID'];
        } else {
            return false;
        }
    }

    /**
     * @param $xmlId
     * @return bool
     */
    protected function assignsImport($xmlId) {
        $query = "SELECT * FROM " . self::IMPORT_ASSIGNS_REL_TABLE . " WHERE code='" . $xmlId . "' AND descr <> ''";
        $result = mssql_query($query, $this->rsMsSQL);
        if (!$arImport = mssql_fetch_assoc($result)) {
            return false;
        }

        $assignXmlId = $arImport['descr'];

        if (!$xmlId = $this->assignExist($assignXmlId)) {
            $query = "SELECT * FROM " . self::IMPORT_ASSIGNS_TABLE . " WHERE code='" . $assignXmlId . "'";
            $result = mssql_query($query, $this->rsMsSQL);
            if (!$arImport = mssql_fetch_assoc($result)) {
                $this->lastError = "Не удалось найти соответствие в справочнике назначений assign.code={$assignXmlId}";
            }

            $arImport['descr'] = $this->toUnicode($arImport['descr']);

            $el = new CIBlockElement;
            $arFields = array('NAME' => $arImport['descr'], 'XML_ID' => self::IMPORT_ASSIGNS_TABLE . '_' . $arImport['code'], 'IBLOCK_ID' => self::IB_ASSIGNS);
            if (!$id = $el->Add($arFields, false, false, false)) {
                $this->lastError = "Ошибка сохранения ценовой категории {$arImport['code']} => {$arImport['descr']} " . strip_tags($el->LAST_ERROR);
                return false;
            }
        }

        $xmlId = $this->assignExist($assignXmlId);

        return $xmlId;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function format2Exist($xmlId) {
        $arFilter = array("IBLOCK_ID" => self::IB_FORMAT2, "XML_ID" => self::IMPORT_FORMAT2_TABLE . '_' . $xmlId,);
        $count = CIBlockElement::GetList(false, $arFilter, array());

        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $xmlId
     * @return bool
     */
    protected function format2Import($xmlId) {
        $query = 'SELECT * FROM ' . self::IMPORT_FORMAT2_TABLE . ' WHERE code=' . $xmlId;
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $this->lastError = "Невозможно сопоставить формат 2 с кодом {$xmlId}!";
            return false;
        }
        $arImport = mssql_fetch_assoc($result);
        $arImport['descr'] = $this->toUnicode($arImport['descr']);

        $el = new CIBlockElement;
        $arFields = array('NAME' => $arImport['descr'], 'XML_ID' => self::IMPORT_FORMAT2_TABLE . '_' . $arImport['code'], 'IBLOCK_ID' => self::IB_FORMAT2);
        if (!$el->Add($arFields, false, false, false)) {
            $this->lastError = "Ошибка сохранения формата 2 {$arImport['code']} => {$arImport['descr']} " . strip_tags($el->LAST_ERROR);
            return false;
        }

        return true;
    }

    /**
     * @param $remoteFileName
     * @param $localFileName
     * @param $mdTime
     * @param $productXmlId
     * @return bool
     */
    protected function ftpGetImage($remoteFileName, $localFileName, $mdTime, $productXmlId) {
        $localFileName = $this->toUnicode(P_DR . '/' . $this->importPath . $localFileName);
        $remoteFileNameLower = $this->getNameAndExtension($remoteFileName, "lower");
        $remoteFileNameUpper = $this->getNameAndExtension($remoteFileName, "upper");

        if (file_exists($localFileName) && CPicture::exists($productXmlId)) {
            // если дата модификации файла на ftp-сервере младше или равна
            // дате модификации локального файла, то пропускаем
            if (strtotime($mdTime) <= filemtime($localFileName)) {
                return true;
            }
        }

        if (!ftp_get($this->rsFTP, $localFileName, $remoteFileNameLower, FTP_BINARY)) {
            if (!ftp_get($this->rsFTP, $localFileName, $remoteFileNameUpper, FTP_BINARY)) {
                $this->lastError = "Невозможно скачать файл {$this->ftpPath}{$this->toUnicode($remoteFileName)} и сохранить под именем {$localFileName}\n";
                return false;
            }
        }

        if (!$pictureId = CPicture::save($localFileName)) {
            $this->lastError = "Возникла ошибка при сохранении изображения {$this->toUnicode($localFileName)}";
            return false;
        }
        /*CFile::ResizeImageGet($pictureId, array("width" => 138, "height" => 138), BX_RESIZE_IMAGE_PROPORTIONAL);
        CFile::ResizeImageGet($pictureId, array("width" => 258, "height" => 258), BX_RESIZE_IMAGE_PROPORTIONAL);
        CFile::ResizeImageGet($pictureId, array("width" => 210, "height" => 210), BX_RESIZE_IMAGE_PROPORTIONAL);*/

        return true;
    }

    /**
     * @param $productXmlId
     * @return bool
     */
    protected function picturesImport($productXmlId) {
        $query = "SELECT descr, Date FROM " . self::IMPORT_PICTURES_TABLE . " WHERE code='" . $productXmlId . "' AND Date IS NOT NULL ORDER BY descr ASC";

        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $this->lastError = "Невозможно сопоставить картинку с кодом {$productXmlId}!";
            return false;
        }
        $arPics = array();
        while ($row = mssql_fetch_assoc($result)) {
            $matches = array();
            preg_match('/^(.{0,8})(.+)\.(.+)/', $row['descr'], $matches);

            if ($matches[2] >= 1) {
                if (!$this->ftpGetImage($row['descr'], $row['descr'], $row['Date'], $productXmlId)) {
                    return false;
                }
                $arPics[] = $this->toUnicode($row['descr']);
            }
        }
        if (!count($arPics)) {
            return false;
        }
        CPicture::__delete($productXmlId, $arPics);
        return true;
    }

    /**
     *
     */
    public function import() {

        $this->addLog("Импорт товаров начался");
        // устанавливаем максимальное время выполнения скрипта
        @set_time_limit($this->timeLimit);

        $selectFields = 'code, Article, Name, Brand, collection, number_packaged,
                         length, width, height,
                         weight, volume, country, Color1, Color2, Color3, Color4,
                         barcode, category, pricecategory, material1, material2, material3,
                         material4, rubber, stitching, packaging, format2, stamping,
                         price, kurs, priceue, balance, reserv, ComingReserv';

        $query = 'SELECT ' . $selectFields . ' FROM ' . self::IMPORT_PRODUCTS_TABLE . " where mark=0x01"; //" where code='00036753'";
        $query = $this->toWindows($query);
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT!');
        }

        if (!$this->ftpConnect()) {
            die('Ошибка при подключении к FTP: ' . $this->lastError);
        }

        // фетчим по одному
        $count = 0; // счетчик добавленных/обновленных товаров
        $i = 0; // общий счетчик

        // Ставим права на все файлы папки импорта (чтобы можно было перезаписать файл)
        if (!$this->recursiveChmod(P_DR . '/' . $this->importPath, 0666, 0777)) {
            $str = "Ошибка при попытке изменить права на директорию " . P_DR . '/' . $this->importPath . " и ее файлы.";
            $this->addLog($str);
        }
        while (($row = mssql_fetch_assoc($result)) && ($i < $this->countLimit)) {
            $productStr = "code='{$this->toUnicode($row['code'])}', Article='{$this->toUnicode($row['Article'])}', Name='{$this->toUnicode($row['Name'])}'";
            if (!ftp_pwd($this->rsFTP)) {
                if (!$this->ftpConnect()) {
                    die('не могу подключиться к FTP');
                }
            }

            // вычищаем поля от лишних пробелов
            $row = $this->clearArrayValues($row);

            $i++;
            $this->error = 0;

            if (!$this->productUncheck($row['code'])) {
                $str = "Предупреждение! Товар {$productStr}. Ошибка при снятии пометки об изменени в 1С. " . $this->lastError;
                $this->addLog($str);
            }

            // все ли поля заполнены ?
            if ($row['Article'] == '' || $row['Name'] == '' || $row['Brand'] == '' || $row['collection'] == '' || $row['category'] == '' || $row['price'] == '') {
                $str = "Не могу записать товар {$productStr} - не все необходимые поля заполнены. Пропуск.";
                $this->addLog($str);
                continue;
            }

            // ------------------------ colors -----------------------------------------------
            $arItemColors = array($row['Color1'], $row['Color2'], $row['Color3'], $row['Color4']);
            if (!($row['Color1'] > 0)) {
                $str = "Товар {$productStr}. Не указан основной цвет Color1. Пропуск.";
                $this->addLog($str);
                continue;
            }
            foreach ($arItemColors as $colorXmlId) {
                if ($colorXmlId > 0) {
                    if (!$this->colorExist($colorXmlId)) { // в нашей базе нет этого цвета
                        if ($this->colorsCount() < self::MAX_COLORS) {
                            if (!$this->importColor($colorXmlId)) {
                                $str = "Товар {$productStr}. Не могу сохранить цвет {$colorXmlId}: {$this->lastError}. Пропуск.";
                                $this->addLog($str);
                                $this->error = 1;
                                break;
                            }
                        } else {
                            $str = "Товар {$productStr}. Цвет {$colorXmlId} не может быть импортирован. Лимит на цвета " . self::MAX_COLORS . " Пропуск.";
                            $this->addLog($str);
                            $this->error = 1;
                            break;
                        }
                    }
                }
            }
            if ($this->error) continue;
            // ------------------------ materials --------------------------------------------
            $arItemMaterials = array($row['material1'], $row['material2'], $row['material3'], $row['material4']);
            foreach ($arItemMaterials as $materialXmlId) {
                if ($materialXmlId > 0) {
                    if (!$this->materialExist($materialXmlId)) {
                        if (!$this->importMaterial($materialXmlId)) {
                            $str = "Товар {$productStr}. Ошибка при импорте материала {$materialXmlId}. " . $this->lastError . " Пропуск";
                            $this->addLog($str);
                            $this->error = 1;
                            break;
                        }
                    }
                }
            }
            if ($this->error) continue;
            // ------------------------ collections & brands ---------------------------------
            $brandXmlId = $row['Brand'];
            if (!$this->brandExist($brandXmlId)) {
                if (!$this->importBrand($brandXmlId)) {
                    $str = "Товар {$productStr}. Ошибка при импорте бренда {$brandXmlId}. " . $this->lastError . " Пропуск";
                    $this->addLog($str);
                    continue;
                }
            }
            $collectionXmlId = $row['collection'];
            if ($collectionXmlId > 0) {
                if (!$this->collectionSectionExist($brandXmlId)) { // внешний код коллекции совпадает с внешним кодом бренда
                    if (!$sectionId = $this->collectionSectionAdd($brandXmlId)) {
                        $str = "Товар {$productStr}. Ошибка при создании раздела коллекций для бренда {$brandXmlId}. " . $this->lastError . " Пропуск";
                        $this->addLog($str);
                        continue;
                    }
                }

                if (!$this->collectionExist($collectionXmlId, $brandXmlId)) {
                    if (!$this->importCollection($collectionXmlId, $brandXmlId)) {
                        $str = "Товар {$productStr}. Ошибка при импорте коллекции {$collectionXmlId}. " . $this->lastError . " Пропуск";
                        $this->addLog($str);
                        continue;
                    }
                }
            }
            // ------------------------ countries --------------------------------------------
            $countryXmlId = $row['country'];
            if ($countryXmlId > 0) {
                if (!$this->countryExist($countryXmlId)) {
                    if (!$this->countryImport($countryXmlId)) {
                        $str = "Товар {$productStr}. Ошибка при импорте страны {$countryXmlId}. " . $this->lastError . " Пропуск";
                        $this->addLog($str);
                        continue;
                    }
                }
            }
            // ------------------------ category ---------------------------------------------
            $categoryXmlId = $row['category'];
            if ($categoryXmlId > 0) {
                if (!$this->categoryExist($categoryXmlId)) {
                    if (!$this->categoryImport($categoryXmlId)) {
                        $str = "Товар {$productStr}. Ошибка при импорте категории {$categoryXmlId}. " . $this->lastError . " Пропуск";
                        $this->addLog($str);
                        continue;
                    }
                }
            }
            // ------------------------ price catagory ---------------------------------------
            $priceCategoryXmlId = $row['pricecategory'];
            if ($priceCategoryXmlId > 0) {
                if (!$this->priceCategoryExist($priceCategoryXmlId)) {
                    if (!$this->priceCategoryImport($priceCategoryXmlId)) {
                        $str = "Товар {$productStr}. Ошибка при импорте ценовой категории {$priceCategoryXmlId}. " . $this->lastError . " Пропуск";
                        $this->addLog($str);
                        continue;
                    }
                }
            }
            // ------------------------ assigns ----------------------------------------------
            $productXmlId = $row['code'];
            $this->assign = $this->assignsImport($productXmlId); // импортируем "назначения"
            // ------------------------ format 2 ---------------------------------------------
            $format2XmlId = $row['format2'];
            if ($format2XmlId > 0) {
                if (!$this->format2Exist($format2XmlId)) {
                    if (!$this->format2Import($format2XmlId)) {
                        $str = "Товар {$productStr}. Ошибка при импорте \"формат 2\" {$format2XmlId}. " . $this->lastError . " Пропуск";
                        $this->addLog($str);
                    }
                }
            }
            // ------------------------ pictures ---------------------------------------------
            if (!$this->picturesImport($productXmlId)) {
                $str = "Товар {$productStr}. Ошибка при импорте изображений. " . $this->lastError . ". Пропуск";
                $this->addLog($str);

                /* Снова помечаем товар как измененный, чтобы при повторном импорте он снова был обработан */
                if (!$this->productCheck($productXmlId)) {
                    $str = "Предупреждение! Товар {$productStr}. Ошибка при установки пометки об изменени в 1С. " . $this->lastError;
                    $this->addLog($str);
                }
                continue;
            }
            // ------------------------ products ---------------------------------------------
            if (!$productId = $this->productImport($productXmlId, $row)) {
                $str = "Товар {$productStr} - ошибка при добавлении/обновлении. " . $this->lastError . ". Пропуск";
                $this->addLog($str);
                continue;
            } else {
                $str = "Успешный импорт товара {$productStr}!";
                $this->addLog($str);
            }
            // ------------------------ personalization --------------------------------------
            $this->personalizationImport($productXmlId);

            // формируем массив брендов с коллекциями
            if (!in_array($collectionXmlId, $this->arCollections[$brandXmlId])) {
                $this->arCollections[$brandXmlId][] = $collectionXmlId;
            }
            // формируем массив идентификаторов
            $this->arProductXmlId[] = $productXmlId;

            $count++;
        } // while fetch_assoc

        // добавляем материалы к коллекциям
        $this->collectionsReset($this->arCollections);

        // делаем неактивными пустые разделы каталога
        $this->resetSections();
        $this->resetCollections();
        $this->resetBrands();

        // импортируем ассоциации товаров
        $this->associationsImport();
        $this->addLog("Импорт товаров закончился. Всего: " . $this->countImport . "; Обработано: " . $i . "; Импортировано: " . $count);

        // если существует лог файл
        if (file_exists($this->logFile)) {
            // отправляем письмо админу
            if (!$this->emailLog('Ошибки при импорте товаров из 1С')) {
                $str = date("d.m.Y-H:i:s") . " ошибка отправки статистики на " . $this->logEmail;
                $this->addLog($str);
            }
        }

    } // import

    /**
     * Импортируем персонализации
     * @param $productXmlId
     * @return bool
     */
    protected function personalizationImport($productXmlId) {
        if (!$productId = $this->productExist($this->toUnicode($productXmlId))) {
            return false;
        }
        $sql = 'SELECT * FROM ' . self::IMPORT_PERSONALIZATION_REL_TABLE . " WHERE code='{$productXmlId}'";
        if (!$result = mssql_query($sql, $this->rsMsSQL)) {
            $str = date("d.m.Y-H:i:s") . " не могу выбрать персонализации для товара XML_ID=" . $this->toUnicode($productXmlId) . ". " . $this->toUnicode(mssql_get_last_message());
            $this->addLog($str);
        }

        $arProp = array();
        while ($row = mssql_fetch_assoc($result)) {
            $row = $this->clearArrayValues($row);
            $persXmlId = self::IMPORT_PERSONALIZATION_TABLE . '_' . $this->toUnicode($row['descr']);

            if (!$this->personalizationExist($persXmlId)) {
                $sql = 'SELECT * FROM ' . self::IMPORT_PERSONALIZATION_TABLE . " WHERE code='" . $row['descr'] . "'";
                if (!$dbPers = mssql_query($sql, $this->rsMsSQL)) {
                    $str = date("d.m.Y-H:i:s") . " не могу найти персонализацию code=" . $this->toUnicode($row['descr']) . " для товара code=" . $this->toUnicode($productXmlId) . ". " . $this->toUnicode(mssql_get_last_message());
                    $this->addLog($str);
                    continue;
                }
                if (!$arPers = mssql_fetch_assoc($dbPers)) {
                    continue;
                }

                $arPers = $this->clearArrayValues($arPers);
                $persName = $this->toUnicode($arPers['descr']);

                if ($this->personalizationAdd($persXmlId, $persName)) {
                    $str = date("d.m.Y-H:i:s") . " не могу сохранить персонализацию code={$persXmlId}" . " для товара code=" . $this->toUnicode($productXmlId) . ". " . $this->lastError;
                }
            }
            $arProp[] = $persXmlId;

        } // while
        if (count($arProp)) {
            CIBlockElement::SetPropertyValuesEx($productId, self::IB_PRODUCTS, array('PERSONALIZATION' => $arProp));
        } else {
            CIBlockElement::SetPropertyValuesEx($productId, self::IB_PRODUCTS, array('PERSONALIZATION' => false));
        }

        return true;
    }

    /**
     * @param $persXmlId
     * @param $persName
     * @return bool
     */
    protected function personalizationAdd($persXmlId, $persName) {
        $ibElement = new CIBlockElement();
        $arFields = array('IBLOCK_ID' => self::IB_PERSONALIZATIONS, 'XML_ID' => $persXmlId, 'NAME' => $persName);
        if (!$ibElement->Add($arFields)) {
            $this->lastError = $ibElement->LAST_ERROR;
            return false;
        }
        return true;
    }

    /**
     * @param $persXmlId
     * @return bool
     */
    protected function personalizationExist($persXmlId) {
        $arFilter = array('IBLOCK_ID' => self::IB_PERSONALIZATIONS, 'XML_ID' => $persXmlId);
        $dbElement = CIBlockElement::GetList(null, $arFilter);

        if (!$arElement = $dbElement->GetNext()) {
            return false;
        }

        return true;
    }

    /**
     * Прячем (ACTIVE) или показываем разделы инфоблока в зависимости от наличия в них вложенных элементов
     */
    protected function resetSections() {
        $ibSection = new CIBlockSection();
        $arSectionFilter = array('IBLOCK_ID' => self::IB_PRODUCTS);
        $rsSection = $ibSection->GetList(null, $arSectionFilter, true);

        while ($row = $rsSection->Fetch()) {
            if ($row['ELEMENT_CNT'] == 0) {
                $ibSection->Update($row['ID'], array('ACTIVE' => 'N'));
            } elseif ($row['ACTIVE'] != 'Y') {
                $ibSection->Update($row['ID'], array('ACTIVE' => 'Y'));
            }
        }
    }

    /**
     * Прячем (ACTIVE) или показываем коллекции в зависимости от существования соответствующих товаров на сайте
     */
    protected function resetCollections() {
        $ibElement = new CIBlockelement();

        $arFilter = array('IBLOCK_ID' => self::IB_COLLECTIONS);
        $arSelect = array('ID', 'IBLOCK_ID', 'XML_ID', 'NAME');

        $rsCollections = CIBlockElement::GetList(null, $arFilter, null, null, $arSelect);

        while ($row = $rsCollections->GetNext()) {
            $arFilter = array('IBLOCK_ID' => self::IB_PRODUCTS, 'PROPERTY_COLLECTION_ID' => $row['XML_ID']);
            $count = CIBlockElement::GetList(null, $arFilter, array());
            if ($count == 0) {
                $ibElement->Update($row['ID'], array('ACTIVE' => 'N'));
            } else {
                $ibElement->Update($row['ID'], array('ACTIVE' => 'Y'));
            }
        }
    }

    /**
     * Прячем (ACTIVE) или показываем бренды в зависимости от существования соответствующих товаров на сайте
     */
    protected function resetBrands() {
        $ibElement = new CIBlockelement();

        $arFilter = array('IBLOCK_ID' => self::IB_BRANDS);
        $arSelect = array('ID', 'IBLOCK_ID', 'XML_ID', 'NAME');

        $rsBrands = CIBlockElement::GetList(null, $arFilter, null, null, $arSelect);

        while ($row = $rsBrands->GetNext()) {
            $arFilter = array('IBLOCK_ID' => self::IB_PRODUCTS, 'PROPERTY_BRAND_ID' => $row['XML_ID']);
            $count = CIBlockElement::GetList(null, $arFilter, array());
            if ($count == 0) {
                $ibElement->Update($row['ID'], array('ACTIVE' => 'N'));
            } else {
                $ibElement->Update($row['ID'], array('ACTIVE' => 'Y'));
            }
        }
    }

    /**
     * @param $product1
     * @param $product2
     * @return bool
     */
    protected function associationExist($product1, $product2) {
        $arFilter = array('IBLOCK_ID' => self::IB_ASSOCIATIONS, array('LOGIC' => 'AND', 'PROPERTY_PRODUCT1_ID' => $product1, 'PROPERTY_PRODUCT2_ID' => $product2));
        $dbElement = CIBlockElement::GetList(null, $arFilter);
        $arElement = $dbElement->Fetch();

        if ($arElement['ID']) {
            return $arElement['ID'];
        }
        return false;
    }

    /**
     * Импортируем связи для существующих на сайте товаров
     * @return bool
     */
    protected function associationsImport() {
        $query = 'SELECT * FROM ' . self::IMPORT_ASSOCIATION_TABLE . '
        WHERE 
        EXISTS(SELECT * FROM ' . self::IMPORT_PRODUCTS_TABLE . ' WHERE ' . self::IMPORT_PRODUCTS_TABLE . '.code = ' . self::IMPORT_ASSOCIATION_TABLE . '.code) 
        OR 
        EXISTS(SELECT * FROM ' . self::IMPORT_PRODUCTS_TABLE . ' WHERE ' . self::IMPORT_PRODUCTS_TABLE . '.code = ' . self::IMPORT_ASSOCIATION_TABLE . '.descr)
        ';
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            $str = "Ошибка получения связных товаров";
            $this->addLog($str);
            return false;
        }

        $this->clearAssociations();

        while ($row = mssql_fetch_assoc($result)) {
            $association = new CIBlockElement;

            $product1 = $this->toUnicode($row['code']);
            $product2 = $this->toUnicode($row['descr']);

            /*if($this->associationExist($product1,$product2)){
                continue;
            }*/
            if (!$this->productExist($product2)) {
                $str = "Ошибка записи ассоциации для товара code={$product1}. Связываемый товар code={$product2} не найден на сайте!";
                $this->addLog($str);
                continue;
            }

            $arFields = array('IBLOCK_ID' => self::IB_ASSOCIATIONS, 'NAME' => $product1 . '_' . $product2, 'PROPERTY_VALUES' => array('PRODUCT1_ID' => $product1, 'PRODUCT2_ID' => $product2));

            if (!$association->add($arFields)) {
                $str = "Ошибка записи ассоциации товаров {$product1} => {$product2}. " . $association->LAST_ERROR;
                $this->addLog($str);
                continue;
            }
        }
        return true;
    }

    /**
     * @return bool
     * Очищает ИБ ассоциаций
     */
    protected function clearAssociations() {
        $res = CIBlockElement::GetList(array(), array('IBLOCK_ID' => self::IB_ASSOCIATIONS), false, false, array('ID'));

        while ($tmp = $res->Fetch()) {
            if (!CIBlockElement::Delete($tmp['ID'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Перезаписываем материалы коллекции, предполагалось, что материалов коллекции может быть несколько
     * @param $arBrandsCollections
     */
    protected function collectionsReset($arBrandsCollections) {
        foreach ($arBrandsCollections as $brandXmlId => $arCollections) {
            foreach ($arCollections as $collectionXmlId) {
                $this->collectionMaterialsReset($brandXmlId, $collectionXmlId);
            }
        }
    }

    /**
     * Пишем материалы коллекции
     * @param $brandXmlId
     * @param $collectionXmlId
     */
    protected function collectionMaterialsReset($brandXmlId, $collectionXmlId) {
        // вытаскиваем материалы товаров, принадлежащие этой коллекции
        $arFilter = array('IBLOCK_ID' => self::IB_PRODUCTS, 'PROPERTY_BRAND_ID' => $this->brandXmlId($brandXmlId), 'PROPERTY_COLLECTION_ID' => $this->collectionXmlId($brandXmlId, $collectionXmlId));
        // (!) группируем товары по коллекции и по материалам
        $arGroup = array('PROPERTY_COLLECTION_ID', 'PROPERTY_MATERIAL1_ID');
        $dbElements = CIBlockElement::GetList(null, $arFilter, $arGroup);

        $arElements = array();
        while ($row = $dbElements->Fetch()) {

            if (strlen($row['PROPERTY_MATERIAL1_ID_VALUE']) > 0) {
                $arElements[] = $row;
                $collectionId = $this->getCollectionId($row['PROPERTY_COLLECTION_ID_VALUE']);

                $arFields = array('MATERIAL_ID' => $row['PROPERTY_MATERIAL1_ID_VALUE']);

                $el = new CIBlockElement;
                $el->SetPropertyValuesEx($collectionId, self::IB_COLLECTIONS, $arFields);
            }
        }
    }

    /**
     * Получаем код коллекции по внешнему коду
     * @param $xmlId
     * @return bool
     */
    protected function getCollectionId($xmlId) {
        $arFilter = array('IBLOCK_ID' => self::IB_COLLECTIONS, 'XML_ID' => $xmlId);

        $arSelect = array('ID');
        $dbElement = CIBlockElement::GetList(null, $arFilter, null, null, $arSelect);

        if (!$arElement = $dbElement->Fetch()) {
            return false;
        }

        return $arElement['ID'];
    }

    /**
     * Снимаем пометку товара об изменении из 1С
     * @param $xmlId
     * @return bool
     */
    protected function productUncheck($xmlId) {
        $query = "UPDATE " . self::IMPORT_PRODUCTS_TABLE . " SET mark=0x00 WHERE code='{$xmlId}'";

        mssql_query($query, $this->rsMsSQL);
        $rows = mssql_rows_affected($this->rsMsSQL);

        if ($rows != 1) {
            $this->lastError = mssql_get_last_message();
            return false;
        }

        return true;
    }

    /**
     * Ставит пометку товара об изменении из 1С
     * @param $xmlId
     * @return bool
     */
    protected function productCheck($xmlId) {
        $query = "UPDATE " . self::IMPORT_PRODUCTS_TABLE . " SET mark=0x01 WHERE code='{$xmlId}'";

        mssql_query($query, $this->rsMsSQL);
        $rows = mssql_rows_affected($this->rsMsSQL);

        if ($rows != 1) {
            $this->lastError = mssql_get_last_message();
            return false;
        }

        return true;
    }

    /**
     * Существует ли товар с внешним кодом $xmlId
     * @param $xmlId
     * @param bool $returnArray
     * @return bool
     */
    public function productExist($xmlId, $returnArray = false) {
        $arFilter = array("IBLOCK_ID" => self::IB_PRODUCTS, "XML_ID" => $xmlId);
        $arResult = CIBlockElement::GetList(array(), $arFilter, false, false, array('IBLOCK_ID', 'ID', 'CODE'))->Fetch();

        if (!$arResult['ID']) {
            return false;
        }

        return $returnArray ? $arResult : $arResult['ID'];
    }

    /**
     * Формируем строку тегов по свойствам товара
     * @param $arProps
     * @return string
     */
    protected function productGetTags($arProps) {
        $tags = '';
        $tags .= $this->getBrandName($arProps['BRAND_ID']) . ',';
        $tags .= $this->getCollectionName($arProps['COLLECTION_ID']) . ',';
        $tags .= $this->getColorName($arProps['COLOR1_ID']);

        return $tags;
    }

    /**
     * Получаем название бренда по внешнему коду
     * @param $xmlId
     * @return mixed
     */
    public static function getBrandName($xmlId) {
        $arFilter = array('IBLOCK' => self::IB_BRANDS, 'XML_ID' => $xmlId);
        $ibElement = new CIBlockElement();
        $dbElement = $ibElement->GetList(null, $arFilter, null, null, array('NAME'));
        $arElement = $dbElement->Fetch();

        return $arElement['NAME'];
    }

    /**
     * Получаем название коллекции по внешнему коду
     * @param $xmlId
     * @return mixed
     */
    public static function getCollectionName($xmlId) {
        $arFilter = array('IBLOCK' => self::IB_COLLECTIONS, 'XML_ID' => $xmlId);
        $ibElement = new CIBlockElement();
        $dbElement = $ibElement->GetList(null, $arFilter, null, null, array('NAME'));
        $arElement = $dbElement->Fetch();

        return $arElement['NAME'];
    }

    /**
     * Получаем название цвета по его внешнему коду
     * @param $xmlId
     * @return mixed
     */
    public static function getColorName($xmlId) {
        $arFilter = array('IBLOCK' => self::IB_COLORS, 'XML_ID' => $xmlId);
        $ibElement = new CIBlockElement();
        $dbElement = $ibElement->GetList(null, $arFilter, null, null, array('NAME'));
        $arElement = $dbElement->Fetch();

        return $arElement['NAME'];
    }

    /**
     * @param $xmlId
     */
    public static function getCategoryName($xmlId) {

    }

    /**
     * @param $xmlId
     * @param $arProduct
     * @return bool|int
     */
    protected function productImport($xmlId, $arProduct) {
        foreach ($arProduct as $field => $value) {
            $this->arValues[$field]['value'] = $this->toUnicode($value);
            $this->arValues[$field]['value'] = preg_replace("/\s+/u", " ", $this->arValues[$field]['value']);
            $this->arValues[$field]['value'] = preg_replace("/(^\s+)|(\s+$)/us", "", $this->arValues[$field]['value']);
        }

        $this->arValues['collection']['value'] = self::IMPORT_COLLECTIONS_TABLE . '_' . $this->arValues['Brand']['value'] . $this->arValues['collection']['value'];
        $this->arValues['Brand']['value'] = self::IMPORT_BRANDS_TABLE . '_' . $this->arValues['Brand']['value'];
        if ($this->arValues['country']['value']) {
            $this->arValues['country']['value'] = self::IMPORT_COUNTRIES_TABLE . '_' . $this->arValues['country']['value'];
        }
        if ($this->arValues['Color1']['value'] > 0) {
            $this->arValues['Color1']['value'] = $this->colorXmlId($this->arValues['Color1']['value']);
        }
        if ($this->arValues['Color2']['value'] > 0) {
            $this->arValues['Color2']['value'] = $this->colorXmlId($this->arValues['Color2']['value']);
        }
        if ($this->arValues['Color3']['value'] > 0) {
            $this->arValues['Color3']['value'] = $this->colorXmlId($this->arValues['Color3']['value']);
        }
        if ($this->arValues['Color4']['value'] > 0) {
            $this->arValues['Color4']['value'] = $this->colorXmlId($this->arValues['Color4']['value']);
        }
        $this->arValues['category']['value'] = self::IMPORT_CATEGORIES_TABLE . '_' . $this->arValues['category']['value'];
        if ($this->arValues['pricecategory']['value']) {
            $this->arValues['pricecategory']['value'] = self::IMPORT_PRICE_CATEGORIES_TABLE . '_' . $this->arValues['pricecategory']['value'];
        }
        if ($this->arValues['material1']['value'] > 0) {
            $this->arValues['material1']['value'] = $this->materialXmlId($this->arValues['material1']['value']);
        }
        if ($this->arValues['material2']['value'] > 0) {
            $this->arValues['material2']['value'] = $this->materialXmlId($this->arValues['material2']['value']);
        }
        if ($this->arValues['material3']['value'] > 0) {
            $this->arValues['material3']['value'] = $this->materialXmlId($this->arValues['material3']['value']);
        }
        if ($this->arValues['material4']['value'] > 0) {
            $this->arValues['material4']['value'] = $this->materialXmlId($this->arValues['material4']['value']);
        }
        if ($this->arValues['format2']['value']) {
            $this->arValues['format2']['value'] = self::IMPORT_FORMAT2_TABLE . '_' . $this->arValues['format2']['value'];
        }

        $arFields = array();
        foreach ($this->arValues as $valueItem) {
            if (!$valueItem['code']) {
                continue;
            }
            $arFields[$valueItem['code']] = $valueItem['value'];
        }

        $code = $this->translit($arFields['NAME']);
        $arFields['IBLOCK_ID']          = self::IB_PRODUCTS;
        $arFields['TAGS']               = $this->productGetTags($arFields['PROPERTY_VALUES']);
        $arFields['IBLOCK_SECTION_ID']  = $this->getCategoryId($arProduct['category']);
        $arFields['PROPERTY_VALUES']    = $arFields;

        $arFields['PROPERTY_VALUES']['ASSIGN'] = $this->assign;
        $arFields['PROPERTY_VALUES']['CATEGORY_SORT'] = $this->collectionGetId($this->collectionXmlId($arProduct['Brand'], $arProduct['collection']));

        $product = new CIBlockElement;

        if ($curProduct = $this->productExist($arFields['XML_ID'], true)) {
            $id = $curProduct['ID'];
            $product->SetPropertyValuesEx($id, self::IB_PRODUCTS, $arFields['PROPERTY_VALUES']);

            if ($curProduct['CODE'] !== $code) {
                while ($this->isCodeExist($code, self::IB_PRODUCTS)) {
                    $code .= '_';
                    if ($curProduct['CODE'] === $code) {
                        break;
                    }
                }
            }

            $arUpdate = array(
                'NAME'              => $arFields['NAME'],
                'CODE'              => $code,
                'IBLOCK_SECTION_ID' => $arFields['IBLOCK_SECTION_ID'],
                'TAGS'              => $arFields['TAGS']
            );
            if (!$product->Update($id, $arUpdate)) {
                $this->lastError = "Ошибка обновления товара code={$arFields['XML_ID']} " . strip_tags($product->LAST_ERROR);
                return false;
            }
        } else {
            while ($this->isCodeExist($code, self::IB_PRODUCTS)) {
                $code .= '_';
            }
            $arFields['CODE'] = $code;
            if (!$id = $product->Add($arFields)) {
                $this->lastError = "Ошибка добавления товара code={$arFields['XML_ID']} " . strip_tags($product->LAST_ERROR);
                return false;
            }
        }

        $this->updateProps($arProduct);
        return $id;
    }

    /**
     * Устанавливаем цены и количество в свойства товара
     * @param $row
     * @return bool
     */
    protected function updateProps($row) {
        /* Проверка на всякий случай, чтобы цены не обнулялись */
        $kurs = isset($row['kurs']) ? floatval($row['kurs']) : 1;

        $productCode = $this->toUnicode($row['code']);
        $productBalance = $row['balance'];
        $productReserv = $row['reserv'];
        $price = floatval($row['price'])*$kurs;
        $priceue = $row['priceue'];
        $comingReserve = $row['ComingReserv'];

        // если товар существует в базе
        if ($productId = $this->productExist($productCode)) {
            // пишем свойства элемента инфоблока
            CIBlockElement::SetPropertyValues($productId, self::IB_PRODUCTS, $price, 'PRICE');
            CIBlockElement::SetPropertyValues($productId, self::IB_PRODUCTS, $priceue, 'PRICEUE');
            CIBlockElement::SetPropertyValues($productId, self::IB_PRODUCTS, $productBalance, 'BALANCE');
            CIBlockElement::SetPropertyValues($productId, self::IB_PRODUCTS, $productReserv, 'RESERV');
            CIBlockElement::SetPropertyValues($productId, self::IB_PRODUCTS, $comingReserve, 'COMING_RESERVE');

            // пишем свойства торгового каталога для элемента инфоблока
            $this->productSetQuantity($productId, $productBalance, $productReserv);
            $this->productSetPrice($productId, $price);
        }

        return true;
    }

    /**
     * Дублируем количество товара в свойства каталога
     * @param $productId
     * @param $quantity
     * @param $productReserv
     * @return bool
     */
    public static function productSetQuantity($productId, $quantity, $productReserv) {
        $arFields = array('ID' => $productId, 'QUANTITY' => $quantity, 'RESERV' => $productReserv);

        if (!CCatalogProduct::Add($arFields)) {
            return false;
        }

        return true;
    }

    /**
     * Дублируем цену товара в свойства каталога
     * @param $productId
     * @param $price
     * @return bool
     */
    public static function productSetPrice($productId, $price) {
        if (!CPrice::DeleteByProduct($productId)) {
            return false;
        }

        $arFields = array('PRODUCT_ID' => $productId, 'CATALOG_GROUP_ID' => 1, // TODO: define const?
            'PRICE' => $price, 'CURRENCY' => 'RUB');

        if (!CPrice::Add($arFields)) {
            return false;
        }

        return true;
    }

    /**
     * Делаем ключи "уникальными"
     * @param $code
     * @return string
     */
    public static function brandXmlId($code) {
        return self::toUnicode(self::IMPORT_BRANDS_TABLE . '_' . $code);
    }

    /**
     * @param $brandCode
     * @param $collectionCode
     * @return string
     */
    public static function collectionXmlId($brandCode, $collectionCode) {
        return self::toUnicode(self::IMPORT_COLLECTIONS_TABLE . '_' . $brandCode . $collectionCode);
    }

    /**
     * @param $code
     * @return string
     */
    public static function materialXmlId($code) {
        return self::toUnicode(self::IMPORT_MATERIALS_TABLE . '_' . $code);
    }

    /**
     * @param $code
     * @return string
     */
    public static function colorXmlId($code) {
        return self::IMPORT_COLORS_TABLE . '_' . $code;
    }

    /**
     * @param $code
     * @return mixed
     */
    public static function productXmlId($code) {
        //return self::IMPORT_PRODUCTS_TABLE . '_' . $code;
        return $code;
    }

    /**
     * убираем лишние пробелы, в возвращаемом MsSQL массиве
     * @param $array
     * @return mixed
     */
    public static function clearArrayValues($array) {
        foreach ($array as $key => $value) {
            // обрабатываем данные
            //$array[$key] = self::toUnicode($value);
            $array[$key] = preg_replace("/\s+/", " ", $array[$key]);
            $array[$key] = preg_replace("/(^\s+)|(\s+$)/s", "", $array[$key]); // mb_trim
        }
        return $array;
    }

    /**
     * @return bool|string
     */
    public function getLogs() {
        return file_get_contents($this->logFile);
    }

    /**
     * @param $subject
     * @return bool
     */
    protected function emailLog($subject) {
        $to = $this->logEmail;
        $subject = date("d.m.Y H:i:s", $this->date) . ' ' . $subject;
        $message = $this->getLogs();

        $rsSites = CSite::GetByID(SITE_ID);
        $arSite = $rsSites->Fetch();
        $emailFrom = $arSite['EMAIL'];

        $headers = "From: {$emailFrom}\r\n";

        return bxmail($to, $subject, $message, $headers);
    }
}