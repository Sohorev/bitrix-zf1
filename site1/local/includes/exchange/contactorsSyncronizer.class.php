<?

/* ------------------------------------------------------------------------------------------
    ContractorsSyncronizer
    Класс, синхронизирующий ID контрагентов во всех таблицах буферной базы с сайтом.
------------------------------------------------------------------------------------------ */

/**
 * Class ContractorsSyncronizer
 */
class ContractorsSyncronizer extends export
{

/* ------------------------------------------------------------------------------------------
    Константы, переменные класса
------------------------------------------------------------------------------------------ */    

    /**
     * Массив контрагентов, обладающих неактуальными ID сайта
     */
    private $_needUpdate = array();

    /**
     * Ассоциации контрагентов в 1С и на сайте.
     * Массив вида "CODE элемента в 1С" => "ID элемента на сайте".
     */
    private $_assContractors = array();

    /**
     * Количество контрагентов на сайте
     */
    private $_contractorsCount = 0;

    /**
     * Таблицы, данные в которых нужно обновить
     */
    private $_updateTables = array(
        "Contractors" => true,
        "ContPersons" => true,
        "Orders"      => true,
        "Discounts"   => true,
        "Shipments"   => true
    );

    /**
     * Переменная, в которую помещаются все логи.
     */
    private $_dispLog = "";

/* ------------------------------------------------------------------------------------------
    Геттеры, сеттеры, логгеры, хелперы
------------------------------------------------------------------------------------------ */    
    
    /**
     * Геттер массива ассоциаций контрагентов.
     */
    public function getAssociations()
    {
        return $this->_assContractors;
    }

    /**
     * Геттер количества контрагентов на сайте.
     */
    public function getCount()
    {
        return $this->_contractorsCount;
    }

    /**
     * Логгер.
     * Записывает строку с последующим переносом строки.
     */
    private function logStr($str)
    {
        $this->_dispLog .= "{$str}<br>";
        return $this;
    }

    /**
     * Выводит лог на экран.
     */
    private function displayLog()
    {
        echo $this->_dispLog;
        return $this;
    }

    /**
     * Выполняет запрос.
     * Если запрос не выполнен, логирует его для последующего анализа.
     */
    private function doQuery($query)
    {
        $msquery = $this->toWindows($query);

        if (! $result = mssql_query($msquery, $this->rsMsSQL)) {
            $this->logStr("&nbsp;&nbsp;Ошибка. Запрос: {$query}");
        }
    }

/* ------------------------------------------------------------------------------------------
    Алгоритм синхронизации данных
------------------------------------------------------------------------------------------ */    

    /**
     * Старт алгоритма.
     */
    public function export()
    {
        $this->findOutdatedContractors()->initAssociations()->updateTables()->displayLog();
    }

    /**
     * @return $this
     */
    private function findOutdatedContractors()
    {
        $table   = self::IMPORT_CONTACTORS_TABLE;

        $query   = "SELECT Code FROM {$table} WHERE (Code IS NOT Null) AND (LEN(CodeSt) < 6)";
        $msquery = $this->toWindows($query);
        $result  = mssql_query($msquery, $this->rsMsSQL);

        while ($row = mssql_fetch_assoc($result)) {
            $this->_needUpdate[] = trim($this->toUnicode($row["Code"]));
        }
        
        return $this;
    }

    /**
     * Формирует массив вида "CODE элемента в 1С" => "ID элемента на сайте".
     * Записывает полученный массив в переменную $this->_assContractors.
     */
    private function initAssociations()
    {
        $rsContractors = CIBlockElement::GetList(
            array("id" => "asc"),
            array("IBLOCK_ID" => self::IB_CONTRACTORS, "ACTIVE" => "Y", "XML_ID" => $this->_needUpdate),
            false,
            false,
            array("ID", "XML_ID")
        );

        while ($arContractor = $rsContractors->GetNext()) {
            $this->_assContractors[$arContractor["XML_ID"]] = $arContractor["ID"];
            $this->_contractorsCount++;
        }

        return $this;
    }

    /**
     * Обновление таблиц.
     * Последовательно запускает обновление каждой требуемой таблицы.
     * Логируется время запуска и окончания работы скрипта.
     * По ходу выполнения логируются параметры запросов и сам запрос в случае ошибки.
     */
    private function updateTables()
    {
        $this->logStr("-------------------------------------------------------------------");
        $this->logStr("&nbsp;&nbsp;Начинаем обновлять данные " . date("Y.m.d H:i:s"));
        $this->logStr("-------------------------------------------------------------------<br>");

        foreach ($this->_assContractors as $code => $id) {
            $this->logStr("Обновляем контрагента. Код 1С: {$code}, код на сайте: {$id}.");

            $this->updateContractors($code, $id)
                ->updateContPersons($code, $id)
                ->updateOrders($code, $id)
                ->updateDiscounts($code, $id)
                ->updateShipments($code, $id);

            $this->logStr("");
        }

        $this->logStr("-------------------------------------------------------------------");
        $this->logStr("&nbsp;&nbsp;Обновление данных закончено " . date("Y.m.d H:i:s"));
        $this->logStr("-------------------------------------------------------------------<br>");

        return $this;
    }

/* ------------------------------------------------------------------------------------------
    Методы обновления конкретных таблиц
------------------------------------------------------------------------------------------ */    
    
    /**
     * Обновление данных таблицы Contactors.
     * В этой таблице хранится сущность "контрагенты".
     */
    private function updateContractors($code, $id)
    {
        if (! $this->_updateTables["Contractors"] || ! $code || ! $id) {
            return $this;
        }

        $table = self::IMPORT_CONTACTORS_TABLE;
        $query = "UPDATE {$table} SET CodeSt = '{$id}' WHERE Code = '{$code}'";
        $this->doQuery($query);
        
        return $this;
    }

    /**
     * Обновление данных таблицы ContPersons.
     * В этой таблице хранятся привязки пользователей к контрагентам.
     */
    private function updateContPersons($code, $id)
    {
        if (! $this->_updateTables["ContPersons"] || ! $code || ! $id) {
            return $this;
        }

        $table = self::IMPORT_CONTPERSONS_TABLE;
        $query = "UPDATE {$table} SET ContactorSt = '{$id}' WHERE Contactor = '{$code}'";
        $this->doQuery($query);

        return $this;
    }

    /**
     * Обновление данных таблицы Orders.
     * В этой таблице хранятся заказы. У каждого заказа должен быть контрагент.
     */
    private function updateOrders($code, $id)
    {
        if (! $this->_updateTables["Orders"] || ! $code || ! $id) {
            return $this;
        }

        $table = self::IMPORT_ORDERS_TABLE;
        $query = "UPDATE {$table} SET contactorSt = '{$id}' WHERE contactor = '{$code}'";
        $this->doQuery($query);

        return $this;
    }

    /**
     * Обновление данных таблицы Discounts.
     * В этой таблице хранятся скидки контрагентов.
     */
    private function updateDiscounts($code, $id)
    {
        if (! $this->_updateTables["Discounts"] || ! $code || ! $id) {
            return $this;
        }

        $table = self::IMPORT_DISCOUNTS_TABLE;
        $query = "UPDATE {$table} SET ContactorSt = '{$id}' WHERE Contactor = '{$code}'";
        $this->doQuery($query);

        return $this;
    }

    /**
     * Обновление данных таблицы Shipments.
     * В этой таблице хранятся данные по отгрузкам заказов.
     */
    private function updateShipments($code, $id)
    {
        if (! $this->_updateTables["Shipments"] || ! $code || ! $id) {
            return $this;
        }

        $table = self::IMPORT_SHIPMENT_TABLE;
        $query = "UPDATE {$table} SET ContactorSt = '{$id}' WHERE Contactor = '{$code}'";
        $this->doQuery($query);

        return $this;
    }
}