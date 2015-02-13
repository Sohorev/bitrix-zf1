<?
class usersImport extends import {

	/**
	 *	Убиваем дубли контрагентов
	 */
    protected function cleanContactors()
    {
        $sql = "SELECT Code, CodeSt FROM " . self::IMPORT_CONTACTORS_TABLE . " WHERE StId='" . self::SITE_ID . "'";
        $sql = $this->toWindows($sql);
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            echo "Не могу выбрать контрагентов для удаления дублей: " . $this->toUnicode(mssql_get_last_message());
            return false;
        }
        while($row = mssql_fetch_assoc($result)) {
            $row = $this->clearArrayValues($row);

			if($row['Code'] && $row['Code'] != 'NULL') {
                $arContractors[$row['Code']][] = $row['CodeSt'];
            }
        }
        foreach($arContractors as $code=>$arContactor){
            if (count($arContactor)>1) {
                sort($arContactor);
                unset($arContactor[0]);
                $sql = "DELETE FROM ".self::IMPORT_CONTACTORS_TABLE." WHERE Code='".$code."' AND CodeSt IN ('".implode("', '", $arContactor)."')";
                $sql = $this->toWindows($sql);
                if(!$result = mssql_query($sql, $this->rsMsSQL)){
                    echo "Не могу удалить контрагента с кодом=".$code.": " . $this->toUnicode(mssql_get_last_message());
                    continue;
                }
            }
        }

        return;
    }

    /**
	 *	Импортируем контрагентов, пользователей, компании, скидки
	 */
    public function import()
    {
        // Убиваем дубли контрагентов
        $this->cleanContactors();
        
        // выбираем контрагентов, помеченных 1С как изменённых и имеющих 1С-код
        $sql = "SELECT Code, CodeSt, Name, INN, City, Manager, LimitReserv FROM " . self::IMPORT_CONTACTORS_TABLE
             . " WHERE C1C='True' AND StId='" . self::SITE_ID . "'"; // AND Code IS NOT NULL
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            echo "Не могу выбрать контрагентов для импорта: " . $this->toUnicode(mssql_get_last_message());
            return false;
        }

        while($row = mssql_fetch_assoc($result)) {
            $row = $this->clearArrayValues($row);
            if(!$this->importContractors($row)){
                echo "Ошибка при импорте контрагента CodeSt=" . $row['CodeSt'] . ": " . $this->lastError;
                continue;
            }
        }
        
        // выбираем пользователей
        $sql = "SELECT Code, CodeSt, Name1, Name2, Phone, Mail FROM " . self::IMPORT_PERSONS_TABLE
            . " WHERE C1C='True' AND Code IS NOT NULL AND StId='" . self::SITE_ID . "'";
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            echo "Не могу выбрать пользователей для импорта: " . mssql_get_last_message();
        }
        
        while($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            if(!$this->importPerson($row)){
                echo "Ошибка при импорте пользователя CodeSt=" . $row['CodeSt'] . ": " . $this->lastError;
                continue;
            }
        }
		
        // загружаем скидки
        //$this->importDiscounts();
    }
	
	/**
	 *	Импортирование только лимита резервирования в момент заказа
	 */
	public function importReserveLimit($userId)
    {
        // Получаем последнего выбранного контрагента пользователя
        $contractorId = UserHelper::getUserContractor($userId);
			
        // Находим лимит резервирования контрагента
        $sql = "SELECT LimitReserv FROM " . self::IMPORT_CONTACTORS_TABLE
             . " WHERE StId='" . self::SITE_ID . "' AND CodeSt='" . $contractorId . "'";

        $sql = $this->toWindows($sql);

        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            echo "Не могу выбрать дистрибьюторов для импорта: " . $this->toUnicode(mssql_get_last_message());
            return false;
        }

        $rowCont = mssql_fetch_assoc($result);
        $rowCont = $this->clearArrayValues($rowCont);

        if (!$rowCont['LimitReserv']) {
            $rowCont['LimitReserv'] = 0;
        }

        echo $rowCont['LimitReserv'] . "<br>";

        $this->importContractorReserveLimit($contractorId, $rowCont['LimitReserv']);
		
		return true;
	}
	
	protected function importContractorReserveLimit($contractorId, $reserveLimit)
    {
		$el = new CIBlockElement;
        $el->SetPropertyValuesEx(
            $contractorId,
            self::IB_CONTRACTORS,
            array("RESERV_LIMIT" => $reserveLimit)
        );
		
		return true;
	}
	
    protected function importContractors($row)
    {
        $id = $row['CodeSt'];
		
        if($row['Manager']){
            $this->importManager($row['Manager']);
        }
        
		$contractor = new CIBlockElement;

        if (! $row['Code']) {
            $this->deleteContractor($row['CodeSt']);
            return true;
        }
		
		$contractorData = $this->checkContractorExists($row['Code'], $row['CodeSt']);
		
		$arProperties = array(
			'CST'			=> 0,
			'INN'			=> $row['INN'],
			'CITY'			=> $row['City'],
			'MANAGER'		=> $row['Manager'],
			'RESERV_LIMIT'	=> $row['LimitReserv'],
			'COMPANY'		=> $contractorData['PROPERTY_COMPANY_VALUE']
        );
        
        $arFields = array(
            'ACTIVE'            => 'Y',
			'IBLOCK_ID'			=> self::IB_CONTRACTORS,
            'NAME'     			=> $row['Name'],
			'XML_ID'			=> $row['Code'],
            'PROPERTY_VALUES' 	=> $arProperties
        );

		if ($contractorData) {
			$contractor->Update($contractorData['ID'], $arFields);	// Обновляем данные, если юр.лицо уже есть на сайте
		} else {
			$newContractorId = $contractor->Add($arFields);	// Добавляем новое юр. лицо
			$this->updateContractorCodeSt($row['Code'], $newContractorId);
			$id = $newContractorId;
		}

        $this->uncheckContractor($id);
    
        return true;
    }

    protected function deleteContractor($id)
    {
        // Delete from DB
        $sql = "DELETE FROM Contactors WHERE CodeSt = '{$id}'";
        $sql = $this->toWindows($sql);

        if(!$result = mssql_query($sql, $this->rsMsSQL)) {
            return false;
        }

        // Delete from site
        CIBlockElement::Delete($id);

        return true;
    }
	
	protected function updateContractorCodeSt($code, $newContractorId)
    {
		$sql = "UPDATE " . self::IMPORT_CONTACTORS_TABLE . " SET CodeSt = '{$newContractorId}' WHERE Code = '{$code}'";
		$sql = $this->toWindows($sql);
		if(!$result = mssql_query($sql, $this->rsMsSQL)) {
			return false;
		}
		$sql = "UPDATE " . self::IMPORT_CONTPERSONS_TABLE . " SET ContactorSt = '{$newContractorId}' WHERE Contactor = '{$code}'";
		$sql = $this->toWindows($sql);
		if(!$result = mssql_query($sql, $this->rsMsSQL)) {
			return false;
		}
		return true;
	}
	
	protected function checkContractorExists($xml_id, $id = null)
    {
		$arSort = array();

        if ($id) {
            $arFilter = array(
                "IBLOCK_ID"	=>self::IB_CONTRACTORS,
                array(
                    "LOGIC" => "OR",
                    "XML_ID" => $xml_id,
                    "ID" => $id
                )
            );
        } else {
            $arFilter = array(
                "IBLOCK_ID"	=>self::IB_CONTRACTORS,
                "XML_ID" => $xml_id
            );
        }

		$arSelect = array("ID", "PROPERTY_COMPANY");
		$res = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
		while($arTmp = $res->GetNext()) {
			return $arTmp;
		}
		return false;
	}

    protected function managerExist($name)
    {
        $arFilter = array('IBLOCK_ID' => self::IB_MANAGERS, 'NAME' => $name);
        $dbElement = CIBlockElement::GetList(null, $arFilter);
        $arElement = $dbElement->Fetch();
        
        if($arElement['ID']){
            return $arElement['ID'];
        } else {
            return false;
        }
    }
    
    protected function importManager($name)
    {
        $sql = "SELECT * FROM " . self::IMPORT_MANAGERS_TABLE . " WHERE name = '" . $this->toWindows($name) . "'";

        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            echo "Не могу найти менеджера " . $this->toUnicode($name) . " для импорта: " . $this->toUnicode(mssql_get_last_message());
        }
        
        if($row = mssql_fetch_assoc($result)){
            $row = $this->clearArrayValues($row);
            $arFields = array('IBLOCK_ID' => self::IB_MANAGERS, 'NAME' => $name, 'PROPERTY_VALUES' => array('EMAIL' => $row['email'], 'PHONE' => $row['telephon']));
            $ibElement = new CIBlockElement();
            
            if($id = $this->managerExist($name)){
                if(!$ibElement->Update($id, $arFields)){
                    $this->lastError = $ibElement->LAST_ERROR;
                    return false;
                }
            } else {
                if(!$ibElement->Add($arFields)){
                    $this->lastError = $ibElement->LAST_ERROR;
                    return false;
                }
            }
        }
        return true;
    }
    
    protected function importPerson($row)
    {
        $id = $row['CodeSt'];
        
		$contractors = $this->getUserContractors($row['Code']);
		
        $arFields = array(
            'NAME'       	 => $row['Name2'],
            'LAST_NAME'  	 => $row['Name1'],
            'LOGIN'      	 => $row['Mail'],
            'EMAIL'      	 => $row['Mail'],
            'WORK_PHONE' 	 => $row['Phone'],
            'UF_USER_ID' 	 => $row['Code'],
            'UF_ACCEPTED' 	 => true,
			'UF_CONTRACTOR'  => $contractors,
            "UF_SENDED_FILE" => false
        ); 

        $user = new CUser;

		if ($this->userExists($id)) {	//Update user
			if(!$user->Update($id, $arFields)) {
				$this->lastError = $user->LAST_ERROR;
				$this->addLog($this->lastError);
				return false;
			}
		} else {	//Add user (не добавляется, потому что нет пароля для пользователя в буферной БД. Ведутся логи.)
			if(!$newUserId = $user->Add($arFields)) {
				$this->lastError = $user->LAST_ERROR;
				$this->addLog($this->lastError);
				return false;
			}
		}
        
		// Если у пользователя есть реальные юр.лица, к которым он прикреплен, и есть Code 1С, то переводим его в Менеджеры
		$arGroups = $user->GetUserGroup($id);
		if (count($contractors) > 0) {
			if (!in_array(self::UG_COMPANY_MANAGER, $arGroups)) {
				$arGroups[] = self::UG_COMPANY_MANAGER;
			}
			$user->SetUserGroup($id, $arGroups);
		} else {	// Если у пользователя не нашлось прикрепленных юр.лиц, но он Менеджер, то убираем его из этой группы
			if (in_array(self::UG_COMPANY_MANAGER, $arGroups)) {
				$key = array_search(self::UG_COMPANY_MANAGER, $arGroups);
				if ($key !== false) {
					unset($arGroups[$key]);
				}
				$user->SetUserGroup($id, $arGroups);
			}
		}
		
        $this->uncheckPerson($id);
    
        return true;
    }
	/**
	 *	Возвращает кол-во юридических лиц, к которым прикреплен пользователь
	 */
	protected function getUserContractorsCount($userId)
    {
		$sql = "SELECT DISTINCT C.Code FROM " . self::IMPORT_CONTACTORS_TABLE . " C 
		LEFT JOIN " . self::IMPORT_CONTPERSONS_TABLE . " CP ON CP.Contactor = C.Code
		WHERE CP.PersonSt = '" . $userId . "'";
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            echo "Не могу найти юридических лиц для пользователя " . $userId;
        }
        
		$count = 0;
        while($row = mssql_fetch_assoc($result)){
			$count++;
		}
		
		return $count;
	}
	
	/**
	 *	Возвращает массив юридических лиц, к которым прикреплен пользователь
	 */
	protected function getUserContractors($userId)
    {
	    // Получаем названия юр.лиц из буферной БД
		// Выбираются только те юр.лица, которые реально существую в Contactors
		$sql = "SELECT DISTINCT C.Code FROM " . self::IMPORT_CONTACTORS_TABLE . " C 
			LEFT JOIN " . self::IMPORT_CONTPERSONS_TABLE . " CP ON CP.Contactor = C.Code
			WHERE CP.Person = '" . $userId . "'";
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            echo "Не могу найти юридических лиц для пользователя " . $userId;
        }
        
		$contractors1C = array();
        while($row = mssql_fetch_assoc($result)){
			$row = $this->clearArrayValues($row);
			$contractors1C[] = $this->toUnicode($row['Code']);
		}

		// Получаем ID юр.лиц с сайта по полученным ранее названиям юр.лиц
		if (count($contractors1C) > 0) {
			$contractors 	= array();
			$arSort 		= array();
			$arFilter 		= array(
			   "IBLOCK_ID"	=>self::IB_CONTRACTORS,
			   "XML_ID"		=> $contractors1C
			);
			$arSelect = array("ID");
			$res = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
			while($arTmp = $res->GetNext()) {
				$contractors[] = $arTmp['ID'];
			}
		}
		return $contractors;
	}
    
	/**
	 *	Ставит новый ID для пользователя, полученный сайтом
	 */
	private function setNewUserId($newId, $oldId)
    {
		$sql = "UPDATE " . self::IMPORT_PERSONS_TABLE . " SET CodeSt='{$newId}' WHERE CodeSt='{$oldId}'";
        mssql_query($sql, $this->rsMsSQL);
        if(mssql_rows_affected($this->rsMsSQL) < 1) return false;
	}
	
    // снимаем флаг об изменении в 1С в удалённой базе
    protected function uncheckContractor($codeSt)
    {
        $sql = "UPDATE " . self::IMPORT_CONTACTORS_TABLE . " SET C1C=NULL WHERE CodeSt='{$codeSt}'";
        
        mssql_query($sql, $this->rsMsSQL);
        
        if(mssql_rows_affected($this->rsMsSQL) < 1){
            return false;
        }
        
        return true;
    }
    
    // снимаем флаг с об изменении в 1С в удалённой базе
    protected function uncheckPerson($codeSt)
    {
        $sql = "UPDATE " . self::IMPORT_PERSONS_TABLE . " SET C1C=NULL WHERE CodeSt='{$codeSt}'";
        
        mssql_query($sql, $this->rsMsSQL);
        
        if(mssql_rows_affected($this->rsMsSQL) < 1){
            return false;
        }
        
        return true;
    }
    
    /**
     * Импорт скидок в систему.
     */
    public function importDiscounts()
    {
        // В режиме отладки на экран будет выводиться информация, но реальные запросы, изменяющие данные, выполняться не будут.
        $debugMode = false;
        $arDiscountsId = array();

        if (! $debugMode) {
            //$this->clearAllDiscounts();
        }

        // Выбираем измененные скидки из буферной базы данных
        $sql = "SELECT * FROM " . self::IMPORT_DISCOUNTS_TABLE . " WHERE C1C=1";
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Не могу получить список скидок: ' . mssql_get_last_message() . '<br>';
        }
        
        // Выполняем импорт каждой отдельной скидки
        while ($row = mssql_fetch_assoc($result)) {
            // Очищаем все значения полученного массива (удаляем пробелы и т.п.)
            $arFields = $this->clearArrayValues($row);
            
            // Проверяем, что контрагент существует в системе
            if (! $contractorId = $arFields['ContactorSt']) {
                $this->lastError = "Не могу сохранить скидку. Не найден контрагент {$arFields['Contactor']}<br>";
                echo $this->lastError;
                continue;
            }
            
            // Проверяем, существует ли текущий бренд в системе
            $arFields['Brend'] = self::IMPORT_BRANDS_TABLE . '_' .$arFields['Brend'];
            if (! $this->brandExist($arFields['Brend'])) {
                $this->lastError = "Не могу сохранить скидку. Бренд {$arFields['Brend']} не найден в системе<br>";
                echo $this->lastError;
                continue;
            } 

            // Для каждого найденного пользователя регистрируем на него скидку
            if (! $debugMode) {
                if ($arD = $this->discountExist($contractorId, $arFields['Brend'])) {
                    if (! $this->discountUpdate($arD["ID"], $arFields['discount'])) {
                        echo $this->lastError;
                        continue;
                    }
                // Иначе добавляем новую
                } else {
                    if(!$id = $this->discountAdd($contractorId, $arFields['Brend'], $arFields['discount'])){
                        echo $this->lastError;
                        continue;
                    }
                }
            } else {
                echo "Контрагент: {$contractorId}; Бренд: {$arFields['Brend']}; Скидка: {$arFields['discount']}%<br>";
            }

            // Снимаем пометку об изменении скидки 1С
            if (! $debugMode) {
                $this->uncheckDiscount($row);
            }

            // Сохраняем ID скидки в массив
            //$arDiscountsId[$id] = 1;
        }

        if (! $debugMode) {
            //$this->clearOldDiscounts($arDiscountsId);
        }
    
        return true;
    }
    
    /**
     * Снимает флаг об изменении скидки
     */
    protected function uncheckDiscount($row)
    {  
        $sql = "UPDATE Discounts SET C1C=NULL WHERE Contactor='{$row['Contactor']}' AND Brend={$row['Brend']} AND discount={$row['discount']}";
        mssql_query($sql, $this->rsMsSQL);
            
        if (mssql_rows_affected($this->rsMsSQL) < 1) {
            return false;
        }

        return true;
    }

    /**
     * Добавляет новую скидку в систему.
     * Если добавление прошло успешно, возвращает ее ID.
     */
    protected function discountAdd($contractor, $brend, $discount)
    {
        $ibElement = new CIBlockElement();
        
        $arFields = array(
            'IBLOCK_ID' => self::IB_DISCOUNT,
            'NAME'      => $contractor . '_' . $brend,
            'ACTIVE'    => 'Y',
            'PROPERTY_VALUES' => array(
                'CONTRACTOR_ID' => $contractor,
                'BRAND_ID'      => $brend,
                'DISCOUNT'      => $discount
            )
        );
        
        if (! $discountId = $ibElement->Add($arFields)) {
            $this->lastError = 'Не могу сохранить скидку для контрагента '
                               . $contractor . ': ' . $ibElement->LAST_ERROR . '<br>';
            return false;
        }

        return $discountId;
    }
    
    /**
     * Обновляет параметры существующей скидки
     */
    protected function discountUpdate($id, $discount)
    {
        $arFields = array(
            'DISCOUNT' => $discount
        );
    
        $ibElement = new CIBlockElement();
        $ibElement->SetPropertyValuesEx($id, self::IB_DISCOUNT, $arFields);
        
        return true;
    }
    
    /**
     * Проверяет, существует ли скидка, привязанная к текущим контргенту и бренду.
     * Если существует, возвращает ее ID и значение скидки.
     */
    protected function discountExist($contractorId, $brand)
    {
        $arFilter = array(
            'IBLOCK_ID'              => self::IB_DISCOUNT,
            'PROPERTY_CONTRACTOR_ID' => $contractorId,
            'PROPERTY_BRAND_ID'      => $brand
        );
        
        $rsDiscount = CIBlockElement::GetList(
            array(), 
            $arFilter,
            false,
            false,
            array("ID", "PROPERTY_DISCOUNT")
        );
        
        if (! $arDiscount = $rsDiscount->Fetch()) {
            return false;
        }
        
        return array(
            "ID" => $arDiscount['ID'],
            "DISCOUNT" => $arDiscount['PROPERTY_DISCOUNT_VALUE']
        );
    }

    /**
     * Удаляет устаревшие скидки.
     */
    public function clearOldDiscounts()
    {
        $arCurrentDiscounts = array();

        $rsIblockDiscounts = CIBlockElement::GetList(
            array("id" => "asc"),
            array("IBLOCK_ID" => self::IB_DISCOUNT, "ACTIVE" => "Y"),
            false,
            false,
            array("ID", "PROPERTY_CONTRACTOR_ID", "PROPERTY_BRAND_ID")
        );

        $result = $this->doSelect("Discounts", array());
        while ($row = mssql_fetch_assoc($result)) {
            $row = $this->clearArrayValues($row);
            $arCurrentDiscounts[$row["ContactorSt"]][] = "Brends_" . $row["Brend"];
        }

        while ($arDiscount = $rsIblockDiscounts->GetNext()) {
            if (! in_array($arDiscount["PROPERTY_BRAND_ID_VALUE"], $arCurrentDiscounts[$arDiscount["PROPERTY_CONTRACTOR_ID_VALUE"]])) {
                CIBlockElement::Delete($arDiscount["ID"]);
            }
        }

        return $this;
    }

    /**
     * Удаляет все скидки
     */
    public function clearAllDiscounts()
    {
        $rsDiscounts = CIBlockElement::GetList(
            array("id" => "asc"),
            array("IBLOCK_ID" => self::IB_DISCOUNT),
            false,
            false,
            array("ID")
        );

        while ($arDiscount = $rsDiscounts->GetNext()) {
            CIBlockElement::Delete($arDiscount["ID"]);
        }

        return $this;
    }
    
    /**
     * Update 2013.06.03
     * Теперь эта функция возвращает массив ID пользователей, привязанных к контрагенту
     */
    protected function getUsersId($contractor)
    {
        // Получаем ID контрагента по его XML_ID
        $arContractor = CIBlockElement::GetList(
            array("id" => "asc"),
            array("IBLOCK_ID" => self::IB_CONTRACTORS, "XML_ID" => $contractor),
            false,
            array("nTopCount" => 1),
            array("ID")
        )->GetNext();

        // Получаем список пользователей, связанных с этим контрагентом
        $rsUsers = CUser::GetList(
            $by="id",
            $order="asc",
            array("ACTIVE" => "Y", "UF_CONTRACTOR" => $arContractor["ID"])
        );

        // Собираем массив ID этих пользователей и возвращаем его
        $arUsers = array();
        while ($arUser = $rsUsers->GetNext()) {
            $arUsers[] = $arUser["ID"];
        }

        return $arUsers;

        // Старый код
        /*$arFilter = array("UF_CONTACTOR_ID" => $xmlId);
        $rsUser = CUser::GetList(($by="timestamp_x"), ($order="desc"), $arFilter);
        if(!$arUser = $rsUser->Fetch()){        
            return false;
        }
        
        return $arUser['ID'];*/
    }

}

?>
