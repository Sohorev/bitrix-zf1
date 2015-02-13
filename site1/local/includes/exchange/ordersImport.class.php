<?

/**
 * Class ordersImport
 */
class ordersImport extends import
{
    /**
     * @param bool $orderId
     * @return bool
     */
    public function importGetRowsCount($orderId = false)
    {
        $sql = 'SELECT count(*) as count FROM ' . self::IMPORT_ORDERS_TABLE . " WHERE C1C='True' AND MainOrder IS NULL AND StId=" . self::SITE_ID;

        if ($orderId) {
            $sql .= " AND NumberSt = '" . $orderId . "'";
        }

        $sql = $this->toWindows($sql);
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения количества импортируемых товаров: ' . mssql_get_last_message();
            $this->addLog($this->lastError);
            die($this->lastError);
        }
        if(!$row = mssql_fetch_assoc($result)){
            return false;
        }

        return $row['count'];
    }

	/**
	 *	Импорт заказов
	 */
    public function import()
    {
        $this->prepareOrders();
    
        $selectFields = 'OrderId, MainOrder, Number, NumberSt, Date, DateCreate, contactor, Person,
                         confirmed, collected, closed, StId, contactorSt, PersonSt,
                         cast(Adres as TEXT) as Adres, Status, Payment';
    
        $sql = 'SELECT ' . $selectFields .  ' FROM ' . self::IMPORT_ORDERS_TABLE . "
		WHERE C1C='True' AND MainOrder IS NULL AND StId='" . self::SITE_ID . "'";
		
        $sql = $this->toWindows($sql);
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения списка заявок: ' . mssql_get_last_message();
            return false;
        }
        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            if($newOrderId = $this->orderImport($row)){
                $this->orderApplicationsImport($newOrderId);
            }
            // отправляем уведомление пользователю
            $this->createEmailEvent($row['Number'], $row['PersonSt']);
        }
        
        // импортируем справочник документов
        $this->documentsImport();
        
        return true;
    }
	
	/**
	 *	Импорт одного заказа
	 */
	public function importOne($orderId_orig)
    {
		$success = false;
        //$this->prepareOrders();

		$selectFields = 'OrderId, MainOrder, Number, NumberSt, Date, DateCreate, contactor, Person,
                         confirmed, collected, closed, StId, contactorSt, PersonSt,
                         cast(Adres as TEXT) as Adres, Status, Payment';
    
        $sql = 'SELECT ' . $selectFields .  ' FROM ' . self::IMPORT_ORDERS_TABLE . "
		WHERE C1C='True' AND MainOrder IS NULL AND StId='" . self::SITE_ID . "' AND NumberSt = '" . $orderId_orig . "'";
		
        $sql = $this->toWindows($sql);
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения списка заявок: ' . mssql_get_last_message();
            return false;
        }

        while ($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            if ($this->orderImport($row)) {
                $success = true;
                $this->orderApplicationsImport($this->toUnicode($row['OrderId']));
            }
            // отправляем уведомление пользователю
            $this->createEmailEvent($row['Number'], $row['PersonSt']);
        }

        // импортируем справочник документов
        $this->documentsImport();
        return $success;
    }

    /**
     * @param $userId
     * @return array|bool
     */
    public function syncOrders($userId) {
		$applicationsArr 	= array();
    	$selectFields = 'OrderId, MainOrder, Number, NumberSt, Date, DateCreate, contactor, Person,
                         confirmed, collected, closed, StId, contactorSt, PersonSt,
                         cast(Adres as TEXT) as Adres, Status, Payment';
    
        $sql = 'SELECT TOP 5 ' . $selectFields .  ' FROM ' . self::IMPORT_ORDERS_TABLE . "
		WHERE C1C='True' AND StId='" . self::SITE_ID . "' AND MainOrder IS NULL AND PersonSt = '" . $userId . "'
		ORDER BY Date DESC";

        $sql = $this->toWindows($sql);
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения списка заявок: ' . mssql_get_last_message();
            return false;
        }
        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            if($newOrderId = $this->orderImport($row)){
                $applicationsArr[$newOrderId] = $this->orderApplicationsImport($this->toUnicode($row['OrderId']));
            }
            // отправляем уведомление пользователю
            $this->createEmailEvent($row['Number'], $row['PersonSt']);
        }
        
        // импортируем справочник документов
        $this->documentsImport();

		return (count($applicationsArr) > 0) ? $applicationsArr : false;
	}

    /**
     *
     */
    protected function documentsImport()
    {
        $sql = "SELECT * FROM " . self::IMPORT_DOCUMENTS_TABLE;
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $str = "Не могу получить типы документов!";
            $this->addLog($str);
        }
        
        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            $docXmlId = self::IMPORT_DOCUMENTS_TABLE . "_" . $row['code'];
            
            if(!$this->documentExist($docXmlId)){
                $arFields = array(
                        'IBLOCK_ID' => self::IB_DOCUMENTS,
                        'XML_ID' => $docXmlId,
                        'NAME' => $row['descr']
                    );
                $ibElement = new CIBlockElement();
                
                if(!$id = $ibElement->Add($arFields)){
                    $str = "Не могу записать документ code=" . $row['descr'];
                    $this->addLog($str);
                }
            }
        }
    }

    /**
     * @param $xmlId
     * @return bool
     */
    protected function documentExist($xmlId)
    {
        $arFilter = array('IBLOCK_ID' => self::IB_DOCUMENTS, 'XML_ID' => $xmlId);
        $dbElement = CIBlockElement::GetList(null, $arFilter);
        if($arElement = $dbElement->GetNext()){
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     */
    protected function createEmailEvent($number, $personSt)
    {
        $dbUser = CUser::GetByID($personSt);
        if(!$arUser = $dbUser->Fetch()){
            return false;
        }
        
        $arEventFields = array(
                "USER_EMAIL" => $arUser['EMAIL'],
                "NUMBER_ST" => $number
        );
        
        if(!CEvent::Send('SALE_ORDER_SPLITTED', 's1', $arEventFields)){
            return false;
        }
        return true;
    }
    
    // расставляем флажки об изменении у родительских заявок
    /**
     * @return bool
     */
    protected function prepareOrders()
    {
        $sql = "select MainOrder from Orders where C1C='True'";
        $sql = $this->toWindows($sql);
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка при выборке заказов!';
            return false;
        }
        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            
            if(!$row['MainOrder']){
                continue;
            }
            $sql = "UPDATE Orders SET C1C='True' WHERE OrderID='{$row['MainOrder']}'";
            $sql = $this->toWindows($sql);
            
            mssql_query($sql, $this->rsMsSQL);
        }
    }

    /**
     * @param $applicationId
     * @param $newAppId
     * @return bool
     */
    public function orderProductsImport($applicationId, $newAppId)
    {
        $sql = "SELECT * FROM " . self::IMPORT_ORDER_PRODUCTS_TABLE . " WHERE OrderID='{$applicationId}'";
        $sql = $this->toWindows($sql);
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения товаров для заказа ID=' . $applicationId . ' ' . mssql_get_last_message();
            return false;
        }
        
        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            $this->orderProductAdd($row, $applicationId, $newAppId);
        }
        return true;
    }

    /**
     * @param $row
     * @param $applicationId
     * @param $newAppId
     * @return bool
     */
    protected function orderProductAdd($row, $applicationId, $newAppId)
    {
        $arProperties = array();
        
        //$arProperties['ORDER_ID']       = self::SITE_ID . $newAppId;
        $arProperties['ORDER_ID']       = $applicationId;
        $arProperties['PRODUCT_XML_ID'] = $row['Tovar'];
        $arProperties['AMOUNT']         = $row['Amount'];
        $arProperties['SUMMA']          = $row['Summa'];
        $arProperties['PRICE']          = $row['Price'];
        
        $arFields['IBLOCK_ID'] = self::IB_ORDER_PRODUCTS;
        $arFields['NAME']      = 'Товар к заказу №' . $newAppId . '. Дата ' . date("d.m.Y");
        $arFields['PROPERTY_VALUES'] = $arProperties;
    
        $ibElement = new CIBlockElement;
        
        if(!$ibElement->Add($arFields)){
            $this->lastError = "Ошибка записи товара OrderId={$row['OrderID']}, Tovar={$row['Tovar']}: " . $ibElement->LAST_ERROR;
            return false;
        }
        return true;
    }
    
    /**
     *	Перед импортом удаляем все заказы у заявки $orderId на сайте
     */
    protected function orderApplicationsDelete($orderId)
    {
        if(!$orderId){
            return false;
        }

        $arFilter = array('IBLOCK_ID' => self::IB_ORDERS, "PROPERTY_MAIN_ORDER_ID" => $orderId);
        $ibElement = new CIBlockElement;
        $dbElement = $ibElement->GetList(array(), $arFilter, null, null, array('ID'));

        while($arElement = $dbElement->Fetch()){
            $ibElement->Delete($arElement['ID']);
        }

        return true;
    }

    /**
     * @param $orderId
     * @return array|bool
     */
    protected function orderApplicationsImport($orderId)
    {
		$applicationsArr = array();
		
		if(!$orderId){
            return false;
        }
		
		// Получаем текущую сумму оплаты для всех заказов заявки
		$arSort = array();
		$arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'PROPERTY_MAIN_ORDER_ID' => $orderId);
		$arSelect = array('ID', 'PROPERTY_ORDER_ID', 'PROPERTY_PAYMENT', 'PROPERTY_DELETED_ORDER',
            'PROPERTY_ALERT_SENDED', 'PROPERTY_DOCS_CHANGED',
            'PROPERTY_SHIPMENT_DATE', 'PROPERTY_PRODUCT_DELETED');

        $arSavedValues = array();
		$rsOrders = CIBlockElement::GetList($arSort, $arFilter, null, null, $arSelect);
		while ($arOrder = $rsOrders->Fetch()) {
			$arPayment[$arOrder['PROPERTY_ORDER_ID_VALUE']] = $arOrder['PROPERTY_PAYMENT_VALUE'];
            $arSavedValues[$arOrder['PROPERTY_ORDER_ID_VALUE']] = array(
                'DELETED_ORDER'   => $arOrder['PROPERTY_DELETED_ORDER_VALUE'],
                'ALERT_SENDED'    => $arOrder['PROPERTY_ALERT_SENDED_VALUE'],
                'DOCS_CHANGED'    => $arOrder['PROPERTY_DOCS_CHANGED_VALUE'],
                'SHIPMENT_DATE'   => $arOrder['PROPERTY_SHIPMENT_DATE_VALUE'],
                'PRODUCT_DELETED' => $arOrder['PROPERTY_PRODUCT_DELETED_VALUE']
            );
		}
		
        $this->orderApplicationsDelete($orderId);
    
        $selectFields = 'OrderId, MainOrder, Number, NumberSt, Date, DateCreate, contactor, Person,
                         confirmed, collected, closed, StId, contactorSt, PersonSt,
                         cast(Adres as TEXT) as Adres, Status, Payment, Prolong, bb';
    
        $sql = 'SELECT ' . $selectFields .  ' FROM ' . self::IMPORT_ORDERS_TABLE . "
		WHERE MainOrder = '" . $orderId . "' AND StId='" . self::SITE_ID . "'";

        $sql = $this->toWindows($sql);
		
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения списка заказов: ' . mssql_get_last_message();
            return false;
        }

        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
			$prolong = ($row['Prolong'] === 'True') ? PROLONGED_ORDER_VALUE : null;
        
			$bb = false;
			if ($row['bb'] == "True" || $row['bb'] == "1" || (intval($row['Payment']) > ($this->getOrderSum($row['OrderId']) / 2))) {
				$bb = NO_DELETE;
			}
			
            $arProperties = array(
                'ORDER_ID'      	=> $row['OrderId'],     // уникальный код заказа
                'MAIN_ORDER_ID' 	=> $row['MainOrder'],   // код родителя
                'NUMBER'        	=> $row['Number'],      // какой-то ключ из 1С
                'USER_ID'       	=> $row['PersonSt'],    // сайт-код пользователя - лучше переделать на CREATED_BY
                'CONFIRMED'     	=> array(array('VALUE' => (int)$row['confirmed'] ? CONFIRMED_OK_VALUE : false)), // статус "подтвержден"
                'COLLECTED'     	=> array(array('VALUE' => (int)$row['collected'] ? COLLECTED_OK_VALUE : false)), // статус "набран"
                'CLOSED'        	=> array(array('VALUE' => (int)$row['closed'] ? CLOSED_OK_VALUE : false)), // статус "закрыт"
                'ADDRESS'       	=> $row['Adres'],       // адрес доставки
                'STATUS'        	=> $row['Status'],       // статус (!?)
                'PAYMENT'       	=> $row['Payment'],     // сумма оплаты
                'PROLONGED_ORDER' 	=> $prolong,      		// заказ продлен
				'CST'				=> 0,
				'NO_DELETE'			=> array(array('VALUE' => $bb)),
				'CONTRACTOR'		=> $row['contactorSt'],
                'DELETED_ORDER'     => $arSavedValues[$row['OrderId']]['DELETED_ORDER'],
                'ALERT_SENDED'      => $arSavedValues[$row['OrderId']]['ALERT_SENDED'],
                'DOCS_CHANGED'      => $arSavedValues[$row['OrderId']]['DOCS_CHANGED'],
                'SHIPMENT_DATE'     => $arSavedValues[$row['OrderId']]['SHIPMENT_DATE'],
                'PRODUCT_DELETED'   => $arSavedValues[$row['OrderId']]['PRODUCT_DELETED']
            );
			
            if (!$this->userExists($row['PersonSt'])) continue;	//Проверка существует ли пользователь в БД сайта
            
            $name = $this->getUserFullName($row['PersonSt']);
            $date = $row['DateCreate'] ? $row['DateCreate'] : $row['Date'];
            $arFields = array(
                'NAME'                  => "Заказ №" . $arProperties['ORDER_ID'] . " от " . $name . '. Дата ' . date('d.m.Y'),
                'IBLOCK_ID'             => self::IB_ORDERS,
                'DATE_ACTIVE_FROM'      => date("d.m.Y H:i:s", strtotime($date)),
                'PROPERTY_VALUES'       => $arProperties
            );
            
            $ibElement = new CIBlockElement;
        
            if(!$newAppId = $ibElement->Add($arFields)){
                $this->lastError = 'Ошибка добавления заказа ID=' . $row['OrderId'] . ' для заявки ID=' . $orderId . ' ' . $ibElement->LAST_ERROR;
            }
			
			$applicationsArr[] = $newAppId;
            
            $this->orderProductsImport($arProperties['ORDER_ID'], $newAppId);
			
			// Менеджер, админ?
			$arGrp = UserHelper::getUserGroup($row['PersonSt']);
			if ($arGrp['ID'] == self::UG_COMPANY_MANAGER || $arGrp['ID'] == self::UG_COMPANY_ADMIN) {
				$emails = array();
				// Включено ли уведомление об оплате?
				$arNotifications = UserHelper::getUserNotifications();
				if ($arPayment[$row['OrderId']] != $row['Payment']) {
					// Если у пользователя включена опция отправки уведомления
					if ($arNotifications['UF_PAYMENT_NOTIFY']) {
						$rsUser = CUser::GetByID($row['PersonSt']);
						$arUser = $rsUser->Fetch();
						$emails[] = $arUser['EMAIL'];
					}
					// Если менеджер, то находим всех админов компании менеджера и отправляем им такое же письмо
					if ($arGrp['ID'] == self::UG_COMPANY_MANAGER) {
						$rsUser = CUser::GetByID($row['PersonSt']);
						$arUser = $rsUser->Fetch();
						$arAdmins = array();
						foreach($arUser['UF_CONTRACTOR'] as $contactor) {
							$filter = Array
							(
								"GROUPS_ID"           	=> array(self::UG_COMPANY_ADMIN),
								"UF_PAYMENT_NOTIFY_M" 	=> 1,
								"UF_CONTRACTOR"			=> array($contactor)
							);
							$rsUsers = CUser::GetList($by="id", $order="asc", $filter); // выбираем пользователей
							while ($arCompanyAdmins = $rsUsers->Fetch()) {
								$emails[] = $arCompanyAdmins['EMAIL'];
							}
						}
					}
					
					// Посылаем уведомление
					if (count($emails) > 0) {
						foreach($emails as $email) {
							$arEventFields = array(
								"USER_EMAIL"        => $email,
								"ORDER_ID"          => $row['OrderId'],
								"PAYMENT_AMOUNT"	=> $row['Payment']
							);
							if(!CEvent::Send('ORDER_PAYMENT_NOTIFICATION', self::SITE_ID_STR, $arEventFields)){
								return false;
							}
						}
					}
				}
			}
            
            $this->uncheckOrder($row['OrderId']);
        }
        return $applicationsArr;
    }
	
	/**
	 *	Возравщает стоимость заказа
	 */
	protected function getOrderSum($orderId)
    {
		$selectFields = 'Summa';
    
        $sql = 'SELECT ' . $selectFields .  ' FROM ' . self::IMPORT_ORDER_PRODUCTS_TABLE . "
		WHERE OrderId='" . $orderId . "' AND StId='" . self::SITE_ID . "'";
		
        $sql = $this->toWindows($sql);
		
		if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения суммы товаров: ' . mssql_get_last_message();
            return false;
        }
		
		$sum = 0;
        while($row = mssql_fetch_assoc($result)){
			$sum += $row['Summa'];
		}
		
		return $sum;
	}

    /**
     * @param $row
     * @return bool|string
     */
    protected function orderImport($row)
    {
        $id = $this->toUnicode($row['NumberSt']);

        // Получаем комментарий к запросу, который оставил при оформлении пользователь
        $arOrigOrder = CIBlockElement::GetList(
            array("id" => "asc"),
            array("IBLOCK_ID" => self::IB_ORDERS, "ACTIVE" => "Y", "PROPERTY_ORDER_ID" => $row["OrderId"]),
            false,
            array("nTopCount" => 1),
            array("ID", "PROPERTY_USER_COMMENT")
        )->Fetch();

        $arProperties = array(
            'ORDER_ID'      => $row['OrderId'], // уникальный код заказа
            'MAIN_ORDER_ID' => null, // код родителя
            'NUMBER'        => $row['Number'], // номер заказа из 1С
            'USER_ID'       => $row['PersonSt'], // ID пользователя, создавшего заказ, на сайте
            'CONFIRMED'     => array(array('VALUE' => (int)$row['confirmed'] ? CONFIRMED_OK_VALUE : false)), // статус "подтвержден"
            'COLLECTED'     => array(array('VALUE' => (int)$row['collected'] ? COLLECTED_OK_VALUE : false)), // статус "собран"
            'CLOSED'        => array(array('VALUE' => (int)$row['closed'] ? CLOSED_OK_VALUE : false)), // статус "закрыт"
            'ADDRESS'       => array("VALUE" => array("TEXT" => $row['Adres'], "TYPE" => "text")), // адрес доставки
            'STATUS'        => $row['Status'], // текстовый статус
            'PAYMENT'       => $row['Payment'], // сумма оплаты
			'CST'			=> 0, // флаг "изменен сайтом"
            'CONTRACTOR'    => $row['contactorSt'], // Id контрагента, от которого создан заказ, на сайте
            'USER_COMMENT'  => array("VALUE" => array("TEXT" => $arOrigOrder['PROPERTY_USER_COMMENT_VALUE']['TEXT'], "TYPE" => "text")) // комментарий пользователя
        );

        $date = $row['DateCreate'] ? $row['DateCreate'] : $row['Date'];
        $arFields = array(
            'DATE_ACTIVE_FROM'      => date("d.m.Y H:i:s", strtotime($date)),
            'PROPERTY_VALUES'       => $arProperties
        );

		if (!$name = $this->userExists($row['PersonSt'])) return false;	// Проверка существует ли пользователь в БД сайта
        $ibElement = new CIBlockElement;
        if($this->orderExist($id) && $id != null) {	//Такая заявка есть в базе => обновляем данные
			// Обновляем инфоблок
            if(!$ibElement->Update($id, $arFields)) {
                $this->lastError = 'Ошибка обновления заявки NumberSt=' . $id . ': ' . $ibElement->LAST_ERROR;
                return false;
            }
			$realOrderId = $row['OrderId'];
        } else {	//То есть заявки нет в базе сайта => добавляем заявку из буферной базы в базу сайта
            $arFields = array(
                'NAME'                  => "Заявка №" . $arProperties['ORDER_ID'] . " от " . $name . '. Дата ' . date('d.m.Y'),
                'IBLOCK_ID'             => self::IB_ORDERS,
                'DATE_ACTIVE_FROM'      => date("d.m.Y H:i:s", strtotime($date)),
                'PROPERTY_VALUES'       => $arProperties
            );
            if(!$newAppId = $ibElement->Add($arFields)){
                $this->lastError = 'Ошибка добавления заявки ID=' . $row['OrderId'] . '. ' . $ibElement->LAST_ERROR;
				return false;
            }
			
			//Обновляем поля OrderId, NumberSt для заявки в буферной базе
			/*$sql = "UPDATE Orders SET OrderId = '" . self::SITE_ID . $newAppId . "', NumberSt='{$newAppId}' WHERE OrderId = '{$row['OrderId']}'";
			$sql = $this->toWindows($sql);
			mssql_query($sql, $this->rsMsSQL);*/
            $sql = "UPDATE Orders SET NumberSt='{$newAppId}' WHERE OrderId = '{$row['OrderId']}'";
            $sql = $this->toWindows($sql);
            mssql_query($sql, $this->rsMsSQL);
			
			//Обновляем поле MainOrder для резервов в буферной базе
			/*$sql = "UPDATE Orders SET MainOrder = '" . self::SITE_ID . $newAppId . "' WHERE MainOrder = '{$row['OrderId']}'";
			$sql = $this->toWindows($sql);
			mssql_query($sql, $this->rsMsSQL);*/
			
			//Импортируем товары заявки
			$this->orderProductsImport($row['OrderId'], $newAppId);
			
			$realOrderId = self::SITE_ID . $newAppId;
        }
    
        $this->uncheckOrder($realOrderId);
    
        return $realOrderId;
    }

    /**
     * @param $stId
     * @return bool
     */
    protected function orderExist($stId)
    {
        $arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'ID' => $stId);
        
        $count = CIBlockElement::GetList(null, $arFilter, array());
        if(!$count){
            return false;
        }
    
        return true;
    }

    /**
     * @return bool
     */
    public function miniOrdersImport()
    {
        $selectFields = 'OrderId,contactorSt';
    
        $sql = 'SELECT ' . $selectFields .  ' FROM ' . self::IMPORT_ORDERS_TABLE . ' WHERE MainOrder IS NOT NULL';

        $sql = $this->toWindows($sql);
		
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения списка заказов: ' . mssql_get_last_message();
            return false;
        }
        $arDBOrders = array();
        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            $arDBOrders[$row['OrderId']] = $row['contactorSt'];
        }
        
        $arSort = array();
        $arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'ORDER_ID'=>false);
        $arSelect = array('ID','PROPERTY_ORDER_ID');
        $rsOrders = CIBlockElement::GetList($arSort, $arFilter, null, null, $arSelect);
        
        while($arOrder = $rsOrders->Fetch()){
            if($arDBOrders[$arOrder['PROPERTY_ORDER_ID_VALUE']]) {
                $iblock_id  = self::IB_ORDERS;
                $code       = "CONTRACTOR";                                                                 // код свойства
                $values     = array('VALUE' => $arDBOrders[$arOrder['PROPERTY_ORDER_ID_VALUE']]);           // значение свойства
                CIBlockElement::SetPropertyValuesEx($arOrder['ID'], $iblock_id, array($code => $values));
            }
        }
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function uncheckOrder($orderId)
    {
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
}
?>