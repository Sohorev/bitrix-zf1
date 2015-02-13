<?php

class shipDates extends ordersExport
{
    public function __construct()
    {
        parent::__construct();
    }

	/**
	 *	Method writes orders to buffer db to receive shipment dates
	 */
	public function makeRequest($orders)
    {
		global $USER;
		$docId = $this->genDocId();
		// Write to 'shipments'
		$sql = "INSERT INTO " . self::IMPORT_SHIPMENT_TABLE . " VALUES (
									'" . $docId . "',
									GETDATE(),
									null,
									null,
									null,
									'False',
									'True',
									null,
									null,
									null,
									'" . $USER->GetID() . "'
								)";

        // !!!!!!!!!!!!! Раскомментировать, когда появится поле StId в БД !!!!!!!!!!!!!!!!!
/*        $sql = "INSERT INTO " . self::IMPORT_SHIPMENT_TABLE . " VALUES (
									'" . $docId . "',
									GETDATE(),
									null,
									null,
									null,
									'False',
									'True',
									null,
									null,
									null,
									'" . $USER->GetID() . "',
									'" . self::SITE_ID . "'
								)";*/
		$sql = $this->toWindows($sql);
		if(!$result = mssql_query($sql, $this->rsMsSQL)){
			$this->lastError = "Ошибка записи в таблицу shipments: " . $this->toUnicode(mssql_get_last_message());
			$this->addLog($this->lastError);
			return false;
		}
	
		// Write orders to 'ShipmentsOrder'
		foreach ($orders as $order) {
			$sql = "INSERT INTO " . self::IMPORT_SHIPMENT_ORDERS_TABLE . " VALUES (
							'" . $docId . "',
							'" . $order . "'
						)";
			$sql = $this->toWindows($sql);
			if(!$result = mssql_query($sql, $this->rsMsSQL)){
				$this->lastError = "Ошибка записи в таблицу ShipmentsOrder: " . $this->toUnicode(mssql_get_last_message());
				$this->addLog($this->lastError);
				return false;
			}
		}
		return $docId;
	}

	/**
	 *	Method generates doc_id string
	 */
	protected function genDocId()
    {
		global $USER;
		$userId = $USER->GetID();
		return substr(md5(time()), 0, 13);
	}

	/**
	 *	Method checks if 1C generated shipment dates
	 */
	public function checkDates($docId)
    {
		$sql = "SELECT C1C, CSt FROM " . self::IMPORT_SHIPMENT_TABLE . " WHERE DocID = '" . $docId . "'";
		$sql = $this->toWindows($sql);
		if(!$result = mssql_query($sql, $this->rsMsSQL)){
			$this->lastError = "Ошибка получения данных из таблицы shipments: " . $this->toUnicode(mssql_get_last_message());
			$this->addLog($this->lastError);
			return false;
		}
		$row = mssql_fetch_assoc($result);

		// Check if 1C approved request
		if ($row['C1C'] == 1 && $row['CSt'] == 0) {
			// Get dates
			$sql = "SELECT TOP 7 Date FROM " . self::IMPORT_SHIPMENT_DATES_TABLE . " WHERE DocID = '" . $docId . "'";
			$sql = $this->toWindows($sql);
			if(!$result = mssql_query($sql, $this->rsMsSQL)){
				$this->lastError = "Ошибка получения данных из таблицы Shipment_date: " . $this->toUnicode(mssql_get_last_message());
				$this->addLog($this->lastError);
				return false;
			}

			while ($row = mssql_fetch_assoc($result)) {
				$shipmentDates[] = date('d.m.Y', strtotime($row['Date']));
			}
			
			// Set C1C = false
			$sql = "UPDATE " . self::IMPORT_SHIPMENT_TABLE . " SET C1C = 'False' WHERE DocID = '" . $docId . "'";
			$sql = $this->toWindows($sql);
			$result = mssql_query($sql);
		} else {
			return false;
		}

		return $shipmentDates;
	}

	/**
	 *	Method sets shipment date
	 */
	public function setShipmentDate($docId, $shipmentDate)
    {
		$sql = "UPDATE " . self::IMPORT_SHIPMENT_TABLE . " 
			SET 
				SDate = CAST('" . date('Ymd', strtotime($shipmentDate)) . "' AS DATETIME),
				CSt = 'True'
			WHERE DocID = '" . $docId . "'";
		$sql = $this->toWindows($sql);

		if(!$result = mssql_query($sql, $this->rsMsSQL)){
			$this->lastError = "Ошибка установки даты отгрузки заказа: " . $this->toUnicode(mssql_get_last_message());
			$this->addLog($this->lastError);
			return false;
		}

		$sql = "SELECT OrderID FROM " . self::IMPORT_SHIPMENT_ORDERS_TABLE . " WHERE DocID = '" . $docId . "'";
		$sql = $this->toWindows($sql);
		$result = mssql_query($sql, $this->rsMsSQL);

		while ($row = mssql_fetch_assoc($result)) {
			$row = $this->clearArrayValues($row);
			$arFilter = array("IBLOCK_ID" => self::IB_ORDERS, 'PROPERTY_ORDER_ID' => $row['OrderID']);
			$arSelect = array("ID", "PROPERTY_MAIN_ORDER_ID");
			$rsSettings = CIBlockElement::GetList(array(),$arFilter,null,null,$arSelect);

			while ($res = $rsSettings->Fetch()) {
				CIBlockElement::SetPropertyValuesEx(
                    $res['ID'], self::IB_ORDERS, array("CONFIRMED" => self::CONFIRMED_OK_VALUE)
                );
				CIBlockElement::SetPropertyValuesEx(
                    $res['ID'], self::IB_ORDERS, array("SHIPMENT_DATE" => $shipmentDate)
                );

                $arApplication = CIBlockElement::GetList(
                    array("id" => "asc"),
                    array("IBLOCK_ID" => self::IB_ORDERS, "PROPERTY_ORDER_ID" => $res["PROPERTY_MAIN_ORDER_ID_VALUE"]),
                    false,
                    array("nTopCount" => 1),
                    array("ID")
                )->Fetch();

                // Оригинальному запросу приписываем статус ожидания подтверждения отгрузки.
                CIBlockElement::SetPropertyValuesEx(
                    $arApplication['ID'], self::IB_ORDERS, array("STATUS" => "Ожидает подтверждения отгрузки")
                );
			}
		}

		return true;
	}
}