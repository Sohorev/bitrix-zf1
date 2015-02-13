<?
class ordersExport extends export {

    public function __construct(){
        parent::__construct();
    }

    /**
     *  Экспорт всех заявок
     */
    public function export() {
        $arFilter = array('IBLOCK_ID' => IB_ORDERS, 'PROPERTY_CST_VALUE' => 1, 'PROPERTY_MAIN_ORDER_ID' => false);
        $arSelect = array('IBLOCK_ID', 'ID', 'TIMESTAMP_X_UNIX', 'CREATED_BY', 'PROPERTY_ORDER_ID',
                          'PROPERTY_MAIN_ORDER_ID', 'PROPERTY_NUMBER', 'PROPERTY_USER_ID',
                          'PROPERTY_ADDRESS', 'PROPERTY_CONFIRMED', 'PROPERTY_COLLECTED',
                          'PROPERTY_CLOSED', 'PROPERTY_CST', 'PROPERTY_CONTRACTOR');
        $dbElements = CIBlockElement::GetList(null, $arFilter, null, null, $arSelect);

        $i = 0;
        while($row = $dbElements->GetNext()){
            $orderId = $row['PROPERTY_ORDER_ID_VALUE'];

            if(!$this->orderHasApps($orderId)){
                $this->deleteOrderProducts($orderId);
                if(!$this->exportOrderProducts($orderId)){
                    $this->addLog($this->lastError);
                }
            } else {
                // экспортируем заявки заказа
                $this->exportOrderApplication($orderId);
            }

            if(!$this->exportOrder($row)){
                $this->addLog($this->lastError);
                continue;
            }

            $i++;
        }
    }
    
    /**
     *  Экспорт одной заявки (по ее ID (без 001 в начале))
     */
    public function exportOne($orderId_orig){
        $arFilter = array('IBLOCK_ID' => IB_ORDERS, 'ID' => $orderId_orig, 'PROPERTY_CST_VALUE' => 1, 'PROPERTY_MAIN_ORDER_ID' => false);
        $arSelect = array('IBLOCK_ID', 'ID', 'TIMESTAMP_X_UNIX', 'CREATED_BY', 'PROPERTY_ORDER_ID',
                          'PROPERTY_MAIN_ORDER_ID', 'PROPERTY_NUMBER', 'PROPERTY_USER_ID',
                          'PROPERTY_ADDRESS', 'PROPERTY_CONFIRMED', 'PROPERTY_COLLECTED',
                          'PROPERTY_CLOSED', 'PROPERTY_CST', 'PROPERTY_CONTRACTOR');
        $dbElements = CIBlockElement::GetList(null, $arFilter, null, array("nTopCount" => 1), $arSelect);

        $i = 0;
        while($row = $dbElements->GetNext()){
            $orderId = $row['PROPERTY_ORDER_ID_VALUE'];

            if(!$this->orderHasApps($orderId)){
                $this->deleteOrderProducts($orderId);
                if(!$this->exportOrderProducts($orderId)){
                    $this->addLog($this->lastError);
                }
            } else {
                // экспортируем заказы заявки
                $this->exportOrderApplication($orderId);
            }

			if(!$this->exportOrder($row)){
                $this->addLog($this->lastError);
                continue;
            }
			
            $i++;
        }
    }
    
	public function changeOrderProducts($orderId, $products) {
		if(!$orderId){
            $this->lastError = "Ошибка экспорта товаров, не передан обязательный параметр OrderId!";
            return false;
        }
        
        foreach($products as $product){
            $arFields = array();
            
            $arFields['OrderId'] = "'" . $product['ORDER_ID'] . "'";
            $arFields['Tovar']   = "'" . $product['PRODUCT_XML_ID'] . "'";
            $arFields['Amount']  = $product['AMOUNT'];
            $arFields['Summa']   = $product['SUMMA'];
            $arFields['Price']   = $product['PRICE'];
            $arFields['StId']    = "'" . self::SITE_ID . "'";

            $setFields = '';
            $setValues = '';
            foreach($arFields as $key => $value){
                if($value){
                    $setFields .= ", {$key}";
                    $setValues .= ", {$value}";
                }
            }
            $setFields = substr($setFields, 1);
            $setValues = substr($setValues, 1);

            $sql = "INSERT INTO " . self::IMPORT_ORDER_PRODUCTS_TABLE . " (" . $setFields . ") VALUES (" . $setValues . ")";
			
            $sql = $this->toWindows($sql);
            if(!$result = mssql_query($sql, $this->rsMsSQL)){
                $this->lastError = "Ошибка экспорта товара Tovar={$arFields['Tovar']} из заказа OrderId={$arFields['OrderId']} в 1С: " . $this->toUnicode(mssql_get_last_message());
                $this->addLog($this->lastError);
                continue;
            }
            
            if(!mssql_rows_affected($this->rsMsSQL)){
                $this->lastError = "Ошибка экспорта товара Tovar={$arFields['Tovar']} из заказа OrderId={$arFields['OrderId']} в 1С: " . $this->toUnicode(mssql_get_last_message());
                $this->addLog($this->lastError);
                continue;
            }
            
        }
        
        return true;
	}
	
    protected function orderHasApps($orderId){
        $arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'PROPERTY_MAIN_ORDER_ID' => $orderId);
        $count = CIBlockElement::GetList(null, $arFilter, array());
        
        return $count;
    }
    
    /**
     *   Всегда перезаписываем заказы внутри заявки
     */
    protected function exportOrderApplication($orderId)
    {
        global $USER;
        if (! is_object($USER)) {
            $USER = new CUser;
        }

        $arFilter = array('IBLOCK_ID' => IB_ORDERS, 'PROPERTY_CST_VALUE' => 1, 'PROPERTY_MAIN_ORDER_ID' => $orderId);

        $arSelect = array('IBLOCK_ID','ID','TIMESTAMP_X_UNIX', 'CREATED_BY', 'PROPERTY_ORDER_ID',
                          'PROPERTY_MAIN_ORDER_ID', 'PROPERTY_NUMBER', 'PROPERTY_USER_ID',
                          'PROPERTY_ADDRESS', 'PROPERTY_CONFIRMED', 'PROPERTY_COLLECTED',
                          'PROPERTY_CLOSED', 'PROPERTY_CST', 'PROPERTY_DOCUMENTS');
        
        $dbElements = CIBlockElement::GetList(null, $arFilter, null, null, $arSelect);
        
        $this->deleteOrderApplications($orderId); // удаляем все заказы с MainOrder = $orderId
        
        while($row = $dbElements->Fetch()){
            $arUser = $USER->GetByID($row["PROPERTY_USER_ID_VALUE"]);
            $applicationId = $row['PROPERTY_ORDER_ID_VALUE'];
            
            $this->deleteOrderProducts($applicationId); // очищаем товары заказа
            $this->exportOrder($row); // записываем заказ
            $this->exportOrderProducts($applicationId); // записываем товары заказа
        
            $this->exportDocuments($row['ID'], $applicationId, $row['PROPERTY_DOCUMENTS_VALUE'], $arUser["EMAIL"]);
        
            $this->orderUncheck($row['ID']);
        }
    }
    
    /**
     *  Снимаем галочку "Отправить в 1С"
     */
    protected function orderUncheck($orderStId){
        CIBlockElement::SetPropertyValuesEx($orderStId, self::IB_ORDERS, array('CST' => array('VALUE' => 0)));
        return true;
    }
    
    /**
     *  Проверяем изменялся ли заказ из 1С (флаг C1C) по OrderID
     */
    protected function orderIsOutdated($orderId){
        $sql = "SELECT OrderId, C1C FROM " . self::IMPORT_ORDERS_TABLE . " WHERE OrderId='{$orderId}'";
        $sql = $this->toWindows($sql);
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            return false;
        }
        // булевый тип в базе хранится как строка ('True','False'), которая не всегда бывает заполнена (NULL)
        $row = mssql_fetch_assoc($result);
        if( $row['C1C'] && (trim($row['C1C']) ) == 'True'){
            return true;
        }
        return false;
    }
    
    
    /**
     *  Проверяем изменялся ли заказ из 1С (флаг C1C) по Number
     */
    public function checkStatusOrderEdit($orderId){
        $sql = "SELECT OrderID, CSt, C1C FROM " . self::IMPORT_ORDERS_TABLE . " WHERE OrderID='{$orderId}'";
        $sql = $this->toWindows($sql);
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            return false;
        }
        // булевый тип в базе хранится как строка ('True','False'), которая не всегда бывает заполнена (NULL)
        $row = $this->clearArrayValues(mssql_fetch_assoc($result));
        return $row;
    }
    
    /**
        Получаем количество записей в инфоблоке товара для всех заказов
     */
    protected function getOrderProductsCount($orderId){
        $arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'PROPERTY_MAIN_ORDER_ID' => $orderId);
        $arSelect = array('PROPERTY_ORDER_ID');
        $dbApplications = CIBlockElement::GetList(null, $arFilter, null, null, $arSelect);
        
        $count = 0;
        while($arApplication = $dbApplications->Fetch()){
    
            $arFilter = array('IBLOCK_ID' => self::IB_ORDER_PRODUCTS, 'PROPERTY_ORDER_ID' => $arApplication['PROPERTY_ORDER_ID_VALUE']);
            $count += CIBlockElement::GetList(null, $arFilter, array());
    
        }
        return $count;
    }
    
    
    /**
        Экспортируем заказ
     */
    public function exportOrder($row){
        $orderId = $row['PROPERTY_ORDER_ID_VALUE']; //С 001
    
        if($this->orderIsOutdated($orderId)){
            $this->lastError = "Заказ {$orderId} не может быть экспортирован, так как был изменён в 1С.";
            $this->addLog($this->lastError);
            return false;
        }

        $arFilter = array('ID' => $row['PROPERTY_USER_ID_VALUE']);
        $arParams = array('SELECT' => array('UF_CONTACTOR_ID', 'UF_USER_ID', 'UF_DISTRIBUTOR_ID', 'UF_LAST_CONTRACTOR'), 'FIELDS' => array('ID'));
        $rsUser = CUser::GetList($by="timestamp_x", $order="desc", $arFilter, $arParams);
        $arUser = $rsUser->Fetch();

        $contractorId   = $row["PROPERTY_CONTRACTOR_VALUE"];
        $contractorCode = UserHelper::getContractorCode1C($contractorId);

        $arFields = array();

        $arFields['OrderId']    = "'{$orderId}'";
        $arFields['MainOrder']  = "NULL";
        $arFields['Number']     = $row['PROPERTY_NUMBER_VALUE'] ? "'{$row['PROPERTY_NUMBER_VALUE']}'" : "NULL";
        $arFields['NumberSt']   = "'{$row['ID']}'";
        $arFields['Date']       = "'" . date("Y-m-d H:i:s", $row['TIMESTAMP_X_UNIX']) . "'";
        $arFields['contactor']  = "'{$contractorCode}'";
        $arFields['Person']     = "'{$arUser['UF_USER_ID']}'"; 
        $arFields['confirmed']  = $row['PROPERTY_CONFIRMED_VALUE'] ? "'True'" : "NULL";
        $arFields['collected']  = $row['PROPERTY_COLLECTED_VALUE'] ? "'True'" : "NULL";
        $arFields['closed']     = $row['PROPERTY_CLOSED_VALUE'] ? "'True'" : "NULL";
        $arFields['CSt']        = 1;
        $arFields['contactorSt']= $contractorId;
        $arFields['PersonSt']   = "'{$row['PROPERTY_USER_ID_VALUE']}'";
        $arFields['Adres']      = "'" . $row['PROPERTY_ADDRESS_VALUE']['TEXT'] . "'";
        $arFields['StId']       = "'" . self::SITE_ID . "'";
        
        if($this->orderExist($orderId)){
            if(!$this->updateOrder($orderId, $arFields)){
                $this->addLog($this->lastError);
            }
        } else {
            if(!$this->addOrder($arFields)){
                $this->addLog($this->lastError);
            }
        }
        
        if(!$this->orderUncheck($row['ID'])){
            $this->addLog($this->lastError);
        }
        
        return true;
    }
    
    /**
     * Экспорт документов
     */
    public function docsExport()
    {
        $arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'PROPERTY_DOCS_CHANGED' => DOCUMENTS_CHANGED);
        $arSelect = array("ID", "PROPERTY_ORDER_ID", "PROPERTY_DOCUMENTS", "PROPERTY_USER_ID");
        $orders = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);

        /* Удаление документов */
        while($row = $orders->Fetch()){
            $arUser = CUser::GetByID($row["PROPERTY_USER_ID_VALUE"])->Fetch();
            $sql = "DELETE FROM " . self::IMPORT_DOCUMENTS_REL_TABLE . " WHERE orderId = '" . $row['PROPERTY_ORDER_ID_VALUE'] . "' AND mail = '" . $arUser["EMAIL"] . "'";
            $sql = $this->toWindows($sql);
            mssql_query($sql, $this->rsMsSQL);

            $this->exportDocuments($row['ID'], $row['PROPERTY_ORDER_ID_VALUE'], $row['PROPERTY_DOCUMENTS_VALUE'], $arUser["EMAIL"]);
        }

        return true;
    }

    /**
     * Экспорт документов конкретного заказа на указанный email.
     * Если email не указан, берется email пользователя, создавшего заказ.
     */
    public function orderDocsExport($orderID, $email = null)
    {
        $arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'ID' => $orderID);
        $arSelect = array("ID", "PROPERTY_ORDER_ID", "PROPERTY_DOCUMENTS", "PROPERTY_USER_ID");

        $orders = CIBlockElement::GetList(
            array(),
            $arFilter,
            false,
            array("nTopCount" => 1),
            $arSelect
        );

        /* Удаление документов */
        if ($row = $orders->Fetch()) {
            if (! $email) {
                $arUser = CUser::GetByID($row["PROPERTY_USER_ID_VALUE"])->Fetch();
                $email = $arUser["EMAIL"];
            }

            $this->exportDocuments($row['ID'], $row['PROPERTY_ORDER_ID_VALUE'], $row['PROPERTY_DOCUMENTS_VALUE'], $email);
        }

        return true;
    }
	
	/**
	 *	Изменяет юр. лицо для заказа
	 */
	public function updateContractor($orderId, $contractorCode, $contractorId) {
		$sql = "UPDATE " . self::IMPORT_ORDERS_TABLE . " 
			SET contactor = '" . $contractorCode . "', contactorSt = '" . $contractorId . "'
			WHERE OrderId = '" . $orderId . "'";

		$sql = $this->toWindows($sql);
		mssql_query($sql, $this->rsMsSQL);
		
		if(!mssql_rows_affected($this->rsMsSQL)) {
			$str = "Ошибка изменения юр. лица для заказа № {$orderId}";
			$this->lastError = $str;
			$this->addLog($str);
			return false;
		}
		return true;
	}
    
    /**
     *  Экспорт одного документа
     */
    public function exportDocument($orderId, $doc, $email) {
        // если есть запрашиваемые документы
        $doc = str_replace(self::IMPORT_DOCUMENTS_TABLE . "_", "", $doc);
        $sql = "INSERT INTO " . self::IMPORT_DOCUMENTS_REL_TABLE . " VALUES({$doc}, '{$orderId}', 1, '{$email}')";
        $sql = $this->toWindows($sql);
        mssql_query($sql, $this->rsMsSQL);

        if(!mssql_rows_affected($this->rsMsSQL)){
            $str = "Ошибка записи документа code={$doc} для заказа {$orderId}. " . $this->toUnicode(mssql_get_last_message());
            $this->lastError = $str;
            return false;
        }

        return true;
    }

    /**
     * Экспорт массива документов
     * @param $id
     * @param $orderId
     * @param $arDocs
     * @param $email
     *
     * @return bool
     */
    protected function exportDocuments($id, $orderId, $arDocs, $email)
    {
        // если есть запрашиваемые документы
        if (count($arDocs)) {
            $sql = "DELETE FROM " . self::IMPORT_DOCUMENTS_REL_TABLE . " WHERE orderId = '{$orderId}' AND mail = '{$email}";
            $sql = $this->toWindows($sql);
            mssql_query($sql, $this->rsMsSQL);

            foreach($arDocs as $doc) {
                $doc = str_replace(self::IMPORT_DOCUMENTS_TABLE . "_", "", $doc);
                $sql = "INSERT INTO " . self::IMPORT_DOCUMENTS_REL_TABLE . " VALUES({$doc}, '{$orderId}', 1, '{$email}')";
                $sql = $this->toWindows($sql);
                mssql_query($sql, $this->rsMsSQL);

                if(!mssql_rows_affected($this->rsMsSQL)) {
                    $str = "Ошибка записи документа code={$doc} для заказа {$orderId}. " . $this->toUnicode(mssql_get_last_message());
                    $this->addLog($str);
                    $this->lastError = $str;
                }
            }
            
            CIBlockElement::SetPropertyValuesEx(
                $id,
                self::IB_ORDERS,
                array(
                    'DOCUMENTS' => false,
                    'DOCS_CHANGED' => null
                )
            );
        } // if count

        return true;
    }
    
    protected function addOrder($arFields){
        $setFields = '';
        $setValues = '';
        foreach($arFields as $key => $value){
            if($value){
                $setFields .= ", {$key}";
                $setValues .= ", {$value}";
            }
        }
        $setFields = substr($setFields, 1);
        $setValues = substr($setValues, 1);

        $sql = "INSERT INTO " . self::IMPORT_ORDERS_TABLE . " (" . $setFields . ") VALUES (" . $setValues . ")";

        $sql = $this->toWindows($sql);
        mssql_query($sql, $this->rsMsSQL);
        
        if(!mssql_rows_affected($this->rsMsSQL)){
            $this->lastError = "Ошибка экспорта заказа OrderId={$arFields['OrderId']} в 1С: " . $this->toUnicode(mssql_get_last_message());
            return false;
        }
            
        return true;
    
    }
    
    protected function updateOrder($orderId, $arFields){
        $setFields = '';
        foreach($arFields as $key => $value){
            if($value){
                $setFields .= ", {$key}={$value}";
            }
        }
        $setFields = substr($setFields, 1);
        $sql = "UPDATE " . self::IMPORT_ORDERS_TABLE . " SET " . $setFields . " WHERE OrderID='{$orderId}'";

        $sql = $this->toWindows($sql);
        mssql_query($sql, $this->rsMsSQL);
        
        if(!mssql_rows_affected($this->rsMsSQL)){
            $this->lastError = "Ошибка обновления заказа OrderId={$arFields['OrderId']} в 1С: " . $this->toUnicode(mssql_get_last_message());
            return false;
        }
            
        return true;
    
    }
    
    public function updateCSt($orderId, $value){
        $sql = "UPDATE " . self::IMPORT_ORDERS_TABLE . " SET CSt = " . $value . " WHERE OrderID='{$orderId}'";
        $sql = $this->toWindows($sql);
		
        mssql_query($sql, $this->rsMsSQL);
        
        if(!mssql_rows_affected($this->rsMsSQL)){
            $this->lastError = "Ошибка обновления заказа OrderId={$orderId} в 1С: " . $this->toUnicode(mssql_get_last_message());
            return false;
        }
        return true;
    }
	
	public function uncheckOrder($orderId){
        if(!$orderId){
            return false;
        }
    
        $sql = "UPDATE Orders SET C1C=NULL WHERE OrderId = '{$orderId}'";
        $sql = $this->toWindows($sql);
        if (mssql_query($sql, $this->rsMsSQL)) {
			return true;
		}
		$this->lastError = 'Ошибка снятия отметки C1C у ORDER ID=' . $orderId;
        return false;
    }
    
    protected function deleteOrderApplications($orderId){
        $sql = "DELETE FROM " . self::IMPORT_ORDERS_TABLE . " WHERE MainOrder='{$orderId}'";
        $sql = $this->toWindows($sql);
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            return false;
        }
        
        return true;
    }
    
    public function deleteOrderProducts($applicationId){
        $sql = "DELETE FROM " . self::IMPORT_ORDER_PRODUCTS_TABLE . " WHERE OrderId='{$applicationId}'";
        $sql = $this->toWindows($sql);
        
        mssql_query($sql, $this->rsMsSQL);
        
        return true;
    }
    
    /**
        Экспортируем товары заказа
     */
    protected function exportOrderProducts($orderId){
        
        if(!$orderId){
            $this->lastError = "Ошибка экспорта товаров, не передан обязательный параметр OrderId!";
            return false;
        }
        
        $arFilter = array('IBLOCK_ID' => self::IB_ORDER_PRODUCTS, 'PROPERTY_ORDER_ID' => $orderId);

        $arSelect = array('IBLOCK_ID', 'ID', 'PROPERTY_ORDER_ID', 'PROPERTY_PRODUCT_XML_ID',
                          'PROPERTY_AMOUNT', 'PROPERTY_SUMMA', 'PROPERTY_PRICE');

        $dbElements = CIBlockElement::GetList(null, $arFilter, null, null, $arSelect);
        
        while($row = $dbElements->GetNext()){
            
            $arFields = array();
            
            $arFields['OrderId'] = "'{$row['PROPERTY_ORDER_ID_VALUE']}'";
            $arFields['Tovar']   = "'{$row['PROPERTY_PRODUCT_XML_ID_VALUE']}'";
            $arFields['Amount']  = $row['PROPERTY_AMOUNT_VALUE'];
            $arFields['Summa']   = $row['PROPERTY_SUMMA_VALUE'];
            $arFields['Price']   = $row['PROPERTY_PRICE_VALUE'];
            $arFields['StId']    = "'" . self::SITE_ID . "'";

            $setFields = '';
            $setValues = '';
            foreach($arFields as $key => $value){
                if($value){
                    $setFields .= ", {$key}";
                    $setValues .= ", {$value}";
                }
            }
            $setFields = substr($setFields, 1);
            $setValues = substr($setValues, 1);

            $sql = "INSERT INTO " . self::IMPORT_ORDER_PRODUCTS_TABLE . " (" . $setFields . ") VALUES (" . $setValues . ")";

            $sql = $this->toWindows($sql);
            if(!$result = mssql_query($sql, $this->rsMsSQL)){
                $this->lastError = "Ошибка экспорта товара Tovar={$arFields['Tovar']} из заказа OrderId={$arFields['OrderId']} в 1С: " . $this->toUnicode(mssql_get_last_message());
                $this->addLog($this->lastError);
                continue;
            }
            
            if(!mssql_rows_affected($this->rsMsSQL)){
                $this->lastError = "Ошибка экспорта товара Tovar={$arFields['Tovar']} из заказа OrderId={$arFields['OrderId']} в 1С: " . $this->toUnicode(mssql_get_last_message());
                $this->addLog($this->lastError);
                continue;
            }
            
        }
        
        return true;
        
    }
    
    public function orderExist($orderId){
        $sql = "SELECT OrderId FROM " . self::IMPORT_ORDERS_TABLE . " where OrderId='" . $orderId . "'";
        $sql = $this->toWindows($sql);
        $result = mssql_query($sql, $this->rsMsSQL);
        
        if(mssql_num_rows($result) > 0){
            return true;
        }
        
        return false;
    }

    	
	public function getOrderIdByNumber($number) {
		$sql = "SELECT OrderId FROM " . self::IMPORT_ORDERS_TABLE . " where Number='" . $number . "'";
        $sql = $this->toWindows($sql);
        $result = mssql_query($sql, $this->rsMsSQL);
        
		$orderId = false;
        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
			$orderId = $row['OrderId'];
        }
        
        return $orderId;
	}

    /**
     * @param $sql
     * @return bool
     */
    public function insert($sql) {
        $sql = $this->toWindows($sql);
        mssql_query($sql, $this->rsMsSQL);

        if(!mssql_rows_affected($this->rsMsSQL)){
            $this->lastError = $this->toUnicode(mssql_get_last_message());
            $this->addLog($this->lastError);
            return false;
        }

        return true;
    }
}

?>