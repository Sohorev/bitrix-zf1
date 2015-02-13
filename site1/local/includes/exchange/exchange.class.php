<?
abstract class exchange {

    const SITE_ID   	= SS_IMPORT_SITE_ID;
    const SITE_ID_STR   = SS_SITE_ID;

    // таблицы импортируемой базы
    const IMPORT_PRODUCTS_TABLE            = 'Tovar001';            // товары
    const IMPORT_COLORS_TABLE              = 'colors';              // справочник цвета
    const IMPORT_MATERIALS_TABLE           = 'materials';           // справочник материалы
    const IMPORT_BRANDS_TABLE              = 'Brends';              // справочник бренды
    const IMPORT_COLLECTIONS_TABLE         = 'collections';         // справочник коллекции
    const IMPORT_COUNTRIES_TABLE           = 'country';             // справочник страны
    const IMPORT_CATEGORIES_TABLE          = 'categories';          // справочник категории
    const IMPORT_PRICE_CATEGORIES_TABLE    = 'PrCategories';        // справочник ценовые категории
    const IMPORT_ASSIGNS_TABLE             = 'assign';              // справочник назначения
    const IMPORT_ASSIGNS_REL_TABLE         = '_assign';             // таблица связи назначения с товаром
    const IMPORT_FORMAT2_TABLE             = 'Format2';             // справочник форматов
    const IMPORT_PICTURES_TABLE            = 'Pictures';            // таблица названий изображений товара
    const IMPORT_ASSOCIATION_TABLE         = '_Association';        // таблица связей товаров (товар <-> расходный материал)
    const IMPORT_ORDERS_TABLE              = 'Orders';              // таблица заказов
    const IMPORT_ORDER_PRODUCTS_TABLE      = 'OrderTovar';          // товары заказа
    const IMPORT_CONTACTORS_TABLE          = 'Contactors';          // таблица дистрибьюторов
    const IMPORT_PERSONS_TABLE             = 'Persons';             // таблица контактных лиц
    const IMPORT_CONTPERSONS_TABLE         = 'ContPersons';         // таблица связи дистрибьюторов с контактными лицами
    const IMPORT_DISCOUNTS_TABLE           = 'Discounts';           // таблица скидок
    const IMPORT_PERSONALIZATION_TABLE     = 'personalization';     // справочник типов персонализаций
    const IMPORT_PERSONALIZATION_REL_TABLE = '_personalization';    // таблица связи типов персонализации с товарами
    const IMPORT_DOCUMENTS_TABLE           = 'documents';
    const IMPORT_DOCUMENTS_REL_TABLE       = '_documents';
    const IMPORT_MANAGERS_TABLE            = 'managers';
    const IMPORT_COMING_TABLE              = 'Coming';              // Таблица приходов товаров
    const IMPORT_MAKE_TO_ORDER_COUNT       = 'NaZakaz';             // Возможное кол-во товара на заказ
    const IMPORT_STATUSES                  = 'Statuses';            // Таблица статусов
    const IMPORT_FILES_TABLE               = 'Files';               // Таблица файлов
	const IMPORT_SHIPMENT_TABLE		       = 'shipments';
	const IMPORT_SHIPMENT_ORDERS_TABLE	   = 'ShipmentsOrder';
	const IMPORT_SHIPMENT_DATES_TABLE	   = 'Shipment_date';
    const IMPORT_FILTERS                   = 'Filters';             // Таблица Спецпредложений
    const IMPORT_FILTERS_REL               = '_Filters';            // Таблица связей Товаров и Спецпредложений
    
    // инфоблоки
    const IB_EXCHANGE_SETTINGS  = IB_EXCHANGE_SETTINGS; // настройки импорта
    const IB_COLORS             = IB_COLORS;            // справочник цвета
    const IB_MATERIALS          = IB_MATERIALS;         // справочник материалов
    const IB_BRANDS             = IB_BRANDS;            // таблица брендов
    const IB_COLLECTIONS        = IB_COLLECTIONS;       // таблица коллекций
    const IB_COUNTRIES          = IB_COUNTRIES;         // справочник стран
    const IB_PRODUCTS           = IB_PRODUCTS;          // таблица товаров
    const IB_PRICE_CATEGORIES   = IB_PRICE_CATEGORIES;  // таблица ценовых категорий
    const IB_ASSIGNS            = IB_ASSIGNS;           // таблица назначений
    const IB_FORMAT2            = IB_FORMAT2;           // таблица форматов
    const IB_ASSOCIATIONS       = IB_ASSOCIATIONS;      // таблица связей товаров
    const IB_DISCOUNT           = IB_DISCOUNT;          // таблица скидок
    const IB_USER_ADDRESS       = IB_USER_ADDRESS;      // таблица пользовательских адресов
    const IB_ORDERS             = IB_ORDERS;            // таблица заказов
    const IB_ORDER_PRODUCTS     = IB_ORDER_PRODUCTS;    // таблица товаров заказа
    const IB_PERSONALIZATIONS   = IB_PERSONALIZATIONS;  // справочник персонализаций
    const IB_DOCUMENTS          = IB_DOCUMENTS;         // справочник документов
    const IB_MANAGERS           = IB_MANAGERS;          // справочник менеджеров
    const IB_COMING             = IB_COMING;            // инфоблок приходов товаров
    const IB_CONTRACTORS        = IB_CONTRACTORS;       // инфоблок юридических лиц
    const IB_COMPANIES          = IB_COMPANIES;         // инфоблок компаний

    // User groups
    const UG_NOT_AUTH           = UG_NOT_AUTH;          // неавторизованный пользователь
    const UG_SITE_USER          = UG_SITE_USER;         // зарегистрированный пользователь
    const UG_COMPANY_MANAGER    = UG_COMPANY_MANAGER;   // менеджер
    const UG_COMPANY_ADMIN      = UG_COMPANY_ADMIN;     // админ
	
	const CONFIRMED_OK_VALUE = CONFIRMED_OK_VALUE;
	const COLLECTED_OK_VALUE = COLLECTED_OK_VALUE;
	const CLOSED_OK_VALUE = CLOSED_OK_VALUE;
	
	const CONTRACTOR_CHANGED = CONTRACTOR_CHANGED;

    const CODE_MAX_LENGTH = 150; 
    
    // Ms SQL resource link 
    protected $rsMsSQL = null;
    
    protected $remoteDbHost     = '';
    protected $remoteDbUser     = '';
    protected $remoteDbPassword = '';
    protected $remoteDbName     = '';

    // ftp server resource link
    protected $rsFTP = null;
    
    protected $ftpHost          = '';
    protected $ftpPort          = '';
    protected $ftpUser          = '';
    protected $ftpPassword      = '';
    protected $ftpPath          = '';
    public static $importPath   = '';
    
    protected $balanceFile      = '';
    
    // email для отправки статитстики
    protected $logEmail   = '';
    // папка для сохранения статистики
    protected $logFolder  = '';
    // дата начала
    protected $date = '';
    // лог-файл
    protected $logFile = '';

    public $lastError = '';
    
    public $error = '';
    
    // лимит на выполнение скрипта сек.
    public $timeLimit = 6000;

    /**
     *
     */
    public function __construct() {
        $this->__getExchangeSettings();
        $this->__connectToRemoteDb();
    }

    /**
     * @return bool
     */
    public function setExecutionTime(){
        // устанавливаем максимальное время выполнения скрипта
        @set_time_limit($this->timeLimit);
        
        return true;
    }

    /**
     * @return bool
     */
    protected function __connectToRemoteDb(){
        if(!$this->rsMsSQL = mssql_connect($this->remoteDbHost, $this->remoteDbUser, $this->remoteDbPassword)){
            die("Не могу подключиться к БД!");
        }

        if(!mssql_select_db($this->remoteDbName, $this->rsMsSQL)){
            die("Не могу выбрать БД!");
        }
        
        mssql_query("SET DATEFORMAT ymd");
        return true;
    }

    /**
     * @return bool
     */
    protected function __getExchangeSettings() {
        setlocale(LC_ALL,       'ru_RU.UTF-8');
        setlocale(LC_NUMERIC,   'en_US.UTF-8');
        setlocale(LC_TIME,      'en_US.UTF-8');
    
        $arFilter = array("IBLOCK_ID" => self::IB_EXCHANGE_SETTINGS);
        $arSelect = array("PROPERTY_DB_HOST", "PROPERTY_DB_USER", "PROPERTY_DB_PASSWORD",
                          "PROPERTY_DB_NAME", "PROPERTY_LOG_EMAIL", "PROPERTY_LOG_FOLDER",
                          "PROPERTY_FTP_SERVER", "PROPERTY_FTP_PORT", "PROPERTY_FTP_USER", "PROPERTY_FTP_PASSWORD",
                          "PROPERTY_FTP_FOLDER", "PROPERTY_STORE_FOLDER", "PROPERTY_BALANCE_FILE");
        $rsSettings = CIBlockElement::GetList(array(),$arFilter,null,null,$arSelect);

        while($row = $rsSettings->Fetch()){
            $arSettings = $row;
        }

        if( ($arSettings['PROPERTY_DB_HOST_VALUE'] == '') ||
            ($arSettings['PROPERTY_DB_NAME_VALUE'] == '') ){
            die('Ошибка чтения параметров подключения к БД!');
        }
        
        $this->remoteDbHost     = $arSettings['PROPERTY_DB_HOST_VALUE'];
        $this->remoteDbUser     = $arSettings['PROPERTY_DB_USER_VALUE'];
        $this->remoteDbPassword = $arSettings['PROPERTY_DB_PASSWORD_VALUE'];
        $this->remoteDbName     = $arSettings['PROPERTY_DB_NAME_VALUE'];
        
        $this->logEmail   = $arSettings['PROPERTY_LOG_EMAIL_VALUE'];
        $this->logFolder  = P_DR . '/' . $arSettings['PROPERTY_LOG_FOLDER_VALUE'];
        
        $this->ftpHost      = $arSettings['PROPERTY_FTP_SERVER_VALUE'];
        $this->ftpPort      = $arSettings['PROPERTY_FTP_PORT_VALUE'] ? $arSettings['PROPERTY_FTP_PORT_VALUE'] : 21;
        $this->ftpUser      = $arSettings['PROPERTY_FTP_USER_VALUE'];
        $this->ftpPassword  = $arSettings['PROPERTY_FTP_PASSWORD_VALUE'];
        $this->ftpPath      = $arSettings['PROPERTY_FTP_FOLDER_VALUE'];
        $this->importPath   = $arSettings['PROPERTY_STORE_FOLDER_VALUE'];
        
        $this->balanceFile  = $arSettings['PROPERTY_BALANCE_FILE_VALUE'];
        
        if(!file_exists($this->logFolder)){
            mkdir($this->logFolder);
        }
        
        $this->date = time();
        
        $this->logFile      = $this->logFolder . '/' . date("dmY-His", $this->date) . '.log';
        return true;
    }

    /**
     * @param $xmlId
     * @return bool
     */
    public function brandExist($xmlId){
        $arFilter = array("IBLOCK_ID" => self::IB_BRANDS, "XML_ID" => $xmlId);
        $count = CIBlockElement::GetList(false, $arFilter, array());
        
        if($count == 1){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool|string
     */
    public function getLogs(){
        return file_get_contents($this->logFile);
    }

    /**
     * @param $subject
     * @return bool
     */
    protected function emailLog($subject){
        $to = $this->logEmail;
        $subject = date("d.m.Y H:i:s", $this->date) . ' ' . $subject;
        $message = $this->getLogs();
    
        $rsSites = CSite::GetByID(SITE_ID);
        $arSite = $rsSites->Fetch();
        $emailFrom = $arSite['EMAIL'];
    
        $headers = "From: {$emailFrom}\r\n";
    
        return bxmail($to, $subject, $message, $headers);
    }

    /**
     * @param $str
     */
    protected function addLog($str){
        if(!file_put_contents($this->logFile, date("d.m.Y-H:i:s") . " - {$str}\n", FILE_APPEND)){
            $this->lastError = "Не могу записать событие '{$str}' в лог файл {$this->logFile}";
        }
    }

    /**
     * @param $str
     * @return string
     */
    public static function toWindows($str){
        return iconv("UTF-8", "WINDOWS-1251", $str);
    }

    /**
     * @param $str
     * @return string
     */
    public static function toUnicode($str){
        return iconv("WINDOWS-1251", "UTF-8", $str);
    }

    /**
     * @param $str
     * @return mixed|string
     */
    public static function translit($str){
        //$str = preg_replace("/\s\(.*\)/u","",$str); // убираем текст из скобочек?
        $str = mb_strtolower($str, "UTF-8");
        $str = str_replace(' ', '_', $str);
        $str = preg_replace("/[^a-zA-ZА-Яа-я0-9\s\_]/u","",$str);
        $str = substr($str, 0, self::CODE_MAX_LENGTH);
 //
        $str = translitIt($str);
        return $str;
    }

    /**
     * Проверка на занятость кода элемента
     * @param $code
     * @param $iBlock
     * @return array|bool|CDBResult|mixed|string
     */
    public function isCodeExist($code, $iBlock) {
        return CIBlockElement::GetList(array(), array('IBLOCK_ID' => $iBlock, 'CODE' => $code), array(), false, array('ID', 'IBLOCK_ID'));
    }

    /**
     * @param $array
     * @return mixed
     */
    public static function clearArrayValues($array){
        foreach($array as $key => $value){
            $array[$key] = self::toUnicode($value);
            $array[$key] = preg_replace("/\s+/u"," ", $array[$key]);
            $array[$key] = preg_replace( "/(^\s+)|(\s+$)/us", "", $array[$key]);  // mb_trim
        }
        
        return $array;
    }
    
    /**
     *  Проверяет существует ли пользователь в БД сайта. Если да, то возвращает имя.
     */
    public function userExists($user_id) {
        $rs = CUser::GetById($user_id);
        $arUser = $rs->Fetch();
        if (!$arUser) return false;
        return true;
    }
    
    /**
     *  Возвращает Имя и Фамилию пользователя по его ID
     */
    public function getUserFullName($user_id) {
        $rsUser = CUser::GetById($user_id);
        $arUser = $rsUser->Fetch();
        if (!$arUser) 
            return false;
        return $arUser["NAME"]." ".$arUser["LAST_NAME"];
    }

    /**
     * @return bool
     */
    protected function ftpConnect()
    {
        if(!$this->rsFTP = ftp_connect($this->ftpHost, $this->ftpPort, $this->timeLimit)){
            $this->lastError = "Невозможно установить соединение с {$this->ftpHost}";
            return false;
        }
        if(!$login = ftp_login($this->rsFTP, $this->ftpUser, $this->ftpPassword)){
            $this->lastError = "Невозможно зайти на {$this->ftpHost}";
            return false;
        }
        if (!ftp_pasv($this->rsFTP, true)) {
            $this->lastError = "Невозможно включить пассивный режим соединения";
            return false;
        }
        if(!ftp_chdir($this->rsFTP, $this->ftpPath)){
            $this->lastError = "Не могу сменить папку на {$this->ftpPath}";
            return false;
        }
        
        return true;
    }

    /**
     * @param $path
     * @param int $filePerm
     * @param int $dirPerm
     * @return bool
     */
    public function recursiveChmod($path, $filePerm=0644, $dirPerm=0755) {
        // Check if the path exists
        if (!file_exists($path)) {
            return(false);
        }
 
        // See whether this is a file
        if (is_file($path)) {
            // Chmod the file with our given filepermissions
            chmod($path, $filePerm);
 
        // If this is a directory...
        } elseif (is_dir($path)) {
            // Then get an array of the contents
            $foldersAndFiles = scandir($path);
 
            // Remove "." and ".." from the list
            $entries = array_slice($foldersAndFiles, 2);
 
            // Parse every result...
            foreach ($entries as $entry) {
                // And call this function again recursively, with the same permissions
                $this->recursiveChmod($path."/".$entry, $filePerm, $dirPerm);
            }
 
            // When we are done with the contents of the directory, we chmod the directory itself
            chmod($path, $dirPerm);
        }
 
        // Everything seemed to work out well, return true
        return(true);
    }

    /**
     * @param $filename
     * @param string $case
     * @return string
     */
    public function getNameAndExtension($filename, $case = "upper") {
        $name       = substr($filename, 0, strrpos($filename, "."));
        $extension  = substr($filename, mb_strlen($name) + 1);
        $extension  = ($case == "upper"
            ? mb_strtoupper($extension, 'UTF-8')
            : mb_strtolower($extension, 'UTF-8')
        );
        return $name . '.' . $extension;
    }

    /* ------------------------------------------------------------------------------------------
        UTILITES
    ------------------------------------------------------------------------------------------ */

    /**
     * existsInDB
     * Проверяет существование хотя бы одной записи, удовлетворяющей заданным условиям
     *
     * @param  string $table      [Название таблица в БД]
     * @param  array  $conditions [Массив вида "название поля" => "значение"]
     * @return bool               [Результат: существует или нет]
     */
    protected function existsInDB($table, $conditions)
    {
        $result = $this->doSelect($table, $conditions);
        return (mssql_num_rows($result) > 0);
    }

    /**
     * doInsert
     * Выполняет запрос на добавление записи в таблицу БД
     *
     * @param  string $table        [Название таблица в БД]
     * @param  array  $values       [Массив вида "название поля" => "значение"]
     * @param  string $errorMessage [Сообщение при ошибке выполнения запроса]
     * @return bool                 [Результат запроса]
     */
    protected function doInsert($table, $values, $errorMessage = "")
    {
        $sql = $this->getSQLForInsert($table, $values);

        if (! $result = mssql_query($sql, $this->rsMsSQL)) {
            $this->lastError  = $errorMessage . ": " . $this->toUnicode(mssql_get_last_message());
            $this->lastError .= "\nЗапрос: " . $sql;
            return false;
        }

        return true;
    }

    /**
     * getSQLForInsert
     * Возвращает текст запроса к БД для добавления записи
     *
     * @param  string $table  [Название таблица в БД]
     * @param  array  $values [Массив вида "название поля" => "значение"]
     * @return string         [Текст запроса]
     */
    protected function getSQLForInsert($table, $values)
    {
        $fields = implode(", ", array_keys($values));
        $values = "'" . implode("', '", $values) . "'";

        // SQL
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
        return $this->toWindows($sql);
    }

    /**
     * doUpdate
     * Выполняет запрос на обновление записи в БД
     *
     * @param  string $table        [Название таблица в БД]
     * @param  array  $values       [Массив вида "название поля" => "значение"]
     * @param  array  $conditions   [Массив вида "название поля" => "значение"]
     * @param  string $errorMessage [Сообщение при ошибке выполнения запроса]
     * @return bool                 [Результат запроса]
     */
    protected function doUpdate($table, $values, $conditions, $errorMessage = "")
    {
        $sql = $this->getSQLForUpdate($table, $values, $conditions);

        if (! $result = mssql_query($sql, $this->rsMsSQL)) {
            $this->lastError  = $errorMessage . ": " . $this->toUnicode(mssql_get_last_message());
            $this->lastError .= "\nЗапрос: " . $sql;
            return false;
        }

        return true;
    }

    /**
     * doUpdate
     * Выполняет запрос на удаление записи в БД
     *
     * @param  string $table         [Название таблица в БД]
     * @param  array $conditions     [Массив вида "название поля" => "значение"]
     * @param  string $errorMessage  [Сообщение при ошибке выполнения запроса]
     * @internal param array $values [Массив вида "название поля" => "значение"]
     * @return bool                  [Результат запроса]
     */
    protected function doDelete($table, $conditions, $errorMessage = "")
    {
        $sql = $this->getSQLForDelete($table, $conditions);

        if (! $result = mssql_query($sql, $this->rsMsSQL)) {
            $this->lastError  = $errorMessage . ": " . $this->toUnicode(mssql_get_last_message());
            $this->lastError .= "\nЗапрос: " . $sql;
            return false;
        }

        return true;
    }

    /**
     * getSQLForUpdate
     * Возвращает текст запроса к БД для обновления записи
     *
     * @param  string $table      [Название таблица в БД]
     * @param  array  $values     [Массив вида "название поля" => "значение"]
     * @param  array  $conditions [Массив вида "название поля" => "значение"]
     * @return string             [Текст запроса]
     */
    protected function getSQLForUpdate($table, $values, $conditions)
    {
        // SET
        $set = "";
        foreach ($values as $field => $val) {
            if ($val) {
                $set .= ", {$field}='{$val}'";
            }
        }
        $set = substr($set, 1);

        // WHERE
        $where = "";
        foreach ($conditions as $field => $val) {
            if ($val) {
                $where .= "AND {$field}='{$val}'";
            }
        }
        $where = substr($where, 3);

        // SQL
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        return $this->toWindows($sql);
    }

    /**
     * doSelect
     * Выполняет запрос на получение данных из БД
     *
     * @param  string $table        [Название таблица в БД]
     * @param  array  $conditions   [Массив вида "название поля" => "значение"]
     * @param  array  $fields       [Массив вида "название поля" => "значение"]
     * @param  string $errorMessage [Сообщение при ошибке выполнения запроса]
     * @return object               [Результат запроса]
     */
    protected function doSelect($table, $conditions, $fields = null, $errorMessage = "")
    {
        $sql = $this->getSQLForSelect($table, $conditions, $fields);

        if (! $result = mssql_query($sql, $this->rsMsSQL)) {
            $this->lastError  = $errorMessage . ": " . $this->toUnicode(mssql_get_last_message());
            $this->lastError .= "\nЗапрос: " . $sql;
            return false;
        }

        return $result;
    }

    /**
     * getSQLForSelect
     * Возвращает текст запроса к БД для выборки данных
     *
     * @param  string $table      [Название таблица в БД]
     * @param  array  $conditions [Массив вида "название поля" => "значение"]
     * @param  array  $fields     [Массив вида "название поля" => "значение"]
     * @return string             [Текст запроса]
     */
    protected function getSQLForSelect($table, $conditions, $fields = null)
    {
        // FIELDS
        $what = "*";

        // WHERE
        if ($conditions && is_array($conditions)) {
            $where = "";
            foreach ($conditions as $field => $val) {
                if ($val) {
                    $where .= "AND {$field}='{$val}'";
                }
            }
            $where = substr($where, 4);
            $where = "WHERE {$where}";
        }

        // SQL
        $sql = "SELECT * FROM {$table} {$where}";
        return $this->toWindows($sql);
    }

    /**
     * getSQLForDelete
     * Удаляет связи пользователя и контрагента
     *
     * @param  string $table      [Название таблица в БД]
     * @param  array  $conditions [Массив вида "название поля" => "значение"]
     * @return string             [Текст запроса]
     */
    protected function getSQLForDelete($table, $conditions)
    {
        // WHERE
        $where = "";
        foreach ($conditions as $field => $val) {
            if ($val) {
                $where .= "AND {$field}='{$val}'";
            }
        }
        $where = substr($where, 3);

        // SQL
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->toWindows($sql);
    }
}