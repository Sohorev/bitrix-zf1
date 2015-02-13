<?php
class moveProducts extends ordersExport {
    public function __construct(){
        parent::__construct();
    }
	/**
	 *	Method deletes old products from orders
	 */
	public function deleteOldProducts($orders) {
		// Deleting order products
		foreach($orders as $order) {
			$sql = "DELETE FROM OrderTovar WHERE OrderID = '" . $order . "'";
			$sql = $this->toWindows($sql);
			if(!$result = mssql_query($sql, $this->rsMsSQL)){
				return false;
			}
		}
		return true;
	}
	/**
	 *	Method checks if these orders belongs to current user or if user is company admin
	 */
	public function checkPermissions($orders) {
		global $USER;
		$userId = $USER->GetID();
		
		// Check if both orders belongs to current user
		$arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'PROPERTY_ORDER_ID' => $orders, 'PROPERTY_USER_ID' => $userId);
		$arSelect = array('ID');
		
		$dbElements = CIBlockElement::GetList(false, $arFilter, false, false, $arSelect);
		
		$i = 0;
		while($row = $dbElements->GetNext()) {
			$i++;
		}
		
		if ($i == 2) {
			return true;
		} else {
			// Check if current user is company admin
			if (UserHelper::getUserGroup() != self::UG_COMPANY_ADMIN)
				return false;
				
			// Get orders users
			$arFilter = array('IBLOCK_ID' => self::IB_ORDERS, 'PROPERTY_ORDER_ID' => $orders);
			$arSelect = array('ID', 'PROPERTY_USER_ID');
			$dbElements = CIBlockElement::GetList(false, $arFilter, false, false, $arSelect);
			while($row = $dbElements->GetNext()) {
				$orderUsers[] = $row['PROPERTY_USER_ID_VALUE'];
			}
			// Get admin companies
			$filter = array(
				"ID" => $userId
			);
			$rsUsers = $USER->GetList(($by="id"), ($order="asc"), $filter, array("SELECT" => array("UF_CONTRACTOR")));
			$curUser = $rsUsers->GetNext();
			// Get all users which belong to admin contactors
			$filter = array(
				"UF_CONTRACTOR" => $curUser['UF_CONTRACTOR']
			);
			$rsUsers = $USER->GetList(($by="id"), ($order="asc"), $filter);
			while ($companyUsers = $rsUsers->GetNext()) {
				$companyUsersIds[] = $companyUsers['ID'];
			}
			// Check if orders users are in array of admin companies users
			foreach($orderUsers as $userId) {
				if (!in_array($userId, $companyUsersIds))
					return false;
			}
		}
		return true;
	}
	/**
	 *	Method inserts new products to orders
	 */
	public function insertNewProducts($arProducts) {
		// Insert new products
		foreach($arProducts as $order => $products) {
			$insertedProducts = array();
			foreach($products as $product) {
				$arFilter = array('IBLOCK_ID' => self::IB_ORDER_PRODUCTS, 'ID' => $product['id']);
				$arSelect = array('IBLOCK_ID', 'ID', 'PROPERTY_ORDER_ID', 'PROPERTY_PRODUCT_XML_ID',
                          'PROPERTY_AMOUNT', 'PROPERTY_SUMMA', 'PROPERTY_PRICE');
				
				$dbElements = CIBlockElement::GetList(false, $arFilter, false, false, $arSelect);
				
				$row = $dbElements->GetNext();
				
				$arFields = array();
				$arFields['OrderId'] = "'" . $order . "'";
				$arFields['Tovar']   = "'" . $row['PROPERTY_PRODUCT_XML_ID_VALUE'] . "'";
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

				if (!$insertedProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]) {
					$sql = "INSERT INTO " . self::IMPORT_ORDER_PRODUCTS_TABLE . " (" . $setFields . ") VALUES (" . $setValues . ")";
					$sql = $this->toWindows($sql);
				} else {
					$sql = "UPDATE " . self::IMPORT_ORDER_PRODUCTS_TABLE . " SET Amount = '" . ($insertedProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]['AMOUNT'] + $row['PROPERTY_AMOUNT_VALUE']) . "', 
														Summa = '" . ($insertedProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]['SUMMA'] + $row['PROPERTY_SUMMA_VALUE']) . "' 
														WHERE OrderId = '" . $order . "' AND 
															Tovar = '" . $row['PROPERTY_PRODUCT_XML_ID_VALUE'] . "'";
					$sql = $this->toWindows($sql);
				}
				
				$insertedProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']] = array(
					"AMOUNT" => $insertedProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]['AMOUNT'] + $row['PROPERTY_AMOUNT_VALUE'],
					"SUMMA"  => $insertedProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]['SUMMA'] + $row['PROPERTY_SUMMA_VALUE']
				);
				
				if(!$result = mssql_query($sql, $this->rsMsSQL)){
					$this->lastError = "Ошибка перемещения товара Tovar=" . $arFields['Tovar'] . " из заказа OrderId=" . $arFields['OrderId'] . " в заказ " . $order['id'] . " в 1С: " . $this->toUnicode(mssql_get_last_message());
					$this->addLog($this->lastError);
					continue;
				}
			}
			$this->updateCSt($order, true);
		}
		return true;
	}
	/**
	 *	Method checks 1C answer
	 */
	public function checkMoveAnswer($orders) {
		$i = 0;
		
		$sql = "SELECT OrderID FROM Orders WHERE 
			(OrderID = '" . $orders[0] . "' OR
			OrderID = '" . $orders[1] . "') AND
			C1C = 'True' AND
			(CSt = 'False' OR CSt IS NULL)";
		$sql = $this->toWindows($sql);
		if(!$result = mssql_query($sql, $this->rsMsSQL)){
			echo $this->lastError = "Ошибка при проверке ответа от 1С: " . $this->toUnicode(mssql_get_last_message());
			$this->addLog($this->lastError);
			return false;
		}
		
		while($row = mssql_fetch_assoc($result)) {
			$i++;
		}
		return $i;
	}
	/**
	 *	Methods revert moving products
	 */
	public function revertChanges($changedOrders) {
		if ($this->deleteOldProducts($changedOrders)) {
			foreach($changedOrders as $order) {
				// Get original order products to revert changes
				$arFilter = array('IBLOCK_ID' => self::IB_ORDER_PRODUCTS, 'PROPERTY_ORDER_ID' => $order);
				$arSelect = array('IBLOCK_ID', 'ID', 'PROPERTY_ORDER_ID', 'PROPERTY_PRODUCT_XML_ID',
						  'PROPERTY_AMOUNT', 'PROPERTY_SUMMA', 'PROPERTY_PRICE');
				
				$dbElements = CIBlockElement::GetList(false, $arFilter, false, false, $arSelect);
				
				while($row = $dbElements->GetNext()) {
					$arFields = array();
					$arFields['OrderId'] = "'" . $order . "'";
					$arFields['Tovar']   = "'" . $row['PROPERTY_PRODUCT_XML_ID_VALUE'] . "'";
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
						$this->lastError = "Ошибка перемещения товара Tovar=" . $arFields['Tovar'] . " из заказа OrderId=" . $arFields['OrderId'] . " в заказ " . $order['id'] . " в 1С: " . $this->toUnicode(mssql_get_last_message());
						$this->addLog($this->lastError);
						continue;
					}
				}
			}
		}
		return true;
	}
	/**
	 *	Method changes site order products
	 */
	public function changeSiteProducts($changedProducts) {
		foreach($changedProducts as $order=>$products) {
			$orderProducts = array();
			foreach($products as $product) {
				CIBlockElement::SetPropertyValuesEx(
					$product,
					self::IB_ORDER_PRODUCTS,
					array(
						'ORDER_ID' => $order
					)
				);
			}
		}
		
		foreach($changedProducts as $order=>$products) {
			$arFilter = array('IBLOCK_ID' => self::IB_ORDER_PRODUCTS, 'PROPERTY_ORDER_ID' => $order);
			$arSelect = array('IBLOCK_ID', 'ID', 'PROPERTY_ORDER_ID', 'PROPERTY_PRODUCT_XML_ID',
					  'PROPERTY_AMOUNT', 'PROPERTY_SUMMA');
			
			$dbElements = CIBlockElement::GetList(false, $arFilter, false, false, $arSelect);
			
			while($row = $dbElements->GetNext()) {
				if ($orderProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]) {
					CIBlockElement::SetPropertyValuesEx(
						$row['ID'],
						self::IB_ORDER_PRODUCTS,
						array(
							'AMOUNT' => ($orderProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]['AMOUNT'] + $row['PROPERTY_AMOUNT_VALUE']),
							'SUMMA' => ($orderProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]['SUMMA'] + $row['PROPERTY_SUMMA_VALUE'])							
						)
					);
					CIBlockElement::Delete($orderProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']]['ID']);
				}
				$orderProducts[$row['PROPERTY_PRODUCT_XML_ID_VALUE']] = array(
					"ID"		=> $row['ID'],
					"AMOUNT"	=> $row['PROPERTY_AMOUNT_VALUE'],
					"SUMMA"		=> $row['PROPERTY_SUMMA_VALUE']
				);
			}
			$this->uncheckOrder($order);
		}
		
		return true;
	}
}
?>