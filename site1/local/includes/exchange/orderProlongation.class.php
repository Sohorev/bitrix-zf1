<?php

/**
 * Class orderProlongation
 */
class orderProlongation extends ordersImport {

    /**
     * Продляем заказ
     * @param $orderId
     * @param $number
     * @return bool
     */
    public function orderProlong($orderId, $number){
		$where  = $orderId ? "OrderId = '" . $orderId . "'" : "Number = '" . $number . "'";
	    $query  = "UPDATE " . self::IMPORT_ORDERS_TABLE . " SET CSt='True', Prolong='True'";
            $query .= " WHERE " . $where . " AND (Prolong = 'False' OR Prolong IS NULL)";
		$query = $this->toWindows($query);    
		$res = mssql_query($query, $this->rsMsSQL);
		if (!$res) return false;
		return true;
    }

    /**
     * Проверяем продлен ли заказ
     * @param $orderId
     * @param $number
     * @return bool
     */
    public function orderProlongCheck($orderId, $number) {
        $where  = $orderId ? "OrderId = '" . $orderId . "'" : "Number = '" . $number . "'";
		$query  = "SELECT OrderId, Number, Date, DateCreate, Prolong FROM " . self::IMPORT_ORDERS_TABLE;
            $query .= " WHERE (CSt = 'False' OR CSt IS NULL)";
            $query .= " AND C1C = 'True'";
            $query .= " AND Prolong = 'True'";
            $query .= " AND " . $where;
		$query  = $this->toWindows($query);
		$res = mssql_query($query, $this->rsMsSQL);
		
		if (!mssql_num_rows($res)) {
            return false;
        }
		
		$tmpArr = mssql_fetch_assoc($res);
		$this->setProlongation($tmpArr);

		return true;
	}

    /**
     * @param $params
     * @return bool
     */
    protected function setProlongation($params) {
		global $USER;
        $params     = $this->clearArrayValues($params);
        $arElement  = CIBlockElement::GetList(
            array(),
            array('IBLOCK_ID' => self::IB_ORDERS, "PROPERTY_ORDER_ID" => $params['OrderId']),
            false,
            false,
            array("IBLOCK_ID", "ID", "DATE_ACTIVE_FROM")
        )->Fetch();

        require_once (P_LIBRARY . 'workCalendar/workCalendar.class.php');
        $date           = $arElement["DATE_ACTIVE_FROM"];
        $daysToDelete   = intval(getSettingText('DELETE_ORDER_TIME'));
        $daysToProlong  = intval(getSettingText('UPDATE_ORDER_TIME'));
        $plusTime       = $daysToDelete + $daysToProlong;
        $calendar       = new WorkCalendar($date);
        $orderDeleteDate = $calendar->addDaysToDate($plusTime)->getDate();

        CIBlockElement::SetPropertyValues($arElement['ID'], self::IB_ORDERS, PROLONGED_ORDER_VALUE, 'PROLONGED_ORDER');
		
		$arEventFields = array(
            "ORDER_ID"	    => $params['Number'],
			"NEW_DATE"	    => $orderDeleteDate,
			"USER_EMAIL"    => $USER->GetEmail()
        );

        if(!CEvent::Send('PROLONG_ORDER', SS_SITE_ID, $arEventFields)){
            return false;
        }
		
		$this->uncheckOrder($params['OrderId']);
        return true;
	}
}