<?php
class deletedOrdersImport extends import
{
    public function import()
    {
		$arOrders       = $this->getOrders();
		$arSiteOrders   = $this->getSiteOrders();

        foreach($arSiteOrders as $arOrder) {
            if(!in_array($arOrder['ORDER_ID'], $arOrders)) {
                $this->setDeletedOrder($arOrder['ID']);
            }
        }
    }
    
    protected function getOrders()
    {
        $rows         = array();
        $selectFields = 'Number';
        $sql          = 'SELECT ' . $selectFields .  ' FROM ' . self::IMPORT_ORDERS_TABLE . " WHERE (MainOrder IS NOT NULL) AND ((Payment IS NULL) OR (Payment = 0)) AND (StId='" . self::SITE_ID . "') AND (DATEDIFF(month, Date, GETDATE()) <= 1 ) AND bb != 'True'";
        $sql          = $this->toWindows($sql);
        
        if(!$result = mssql_query($sql, $this->rsMsSQL)){
            $this->lastError = 'Ошибка получения списка заказов: ' . mssql_get_last_message();
            return false;
        }
        
        while($row = mssql_fetch_assoc($result)){
            $row    = $this->clearArrayValues($row);
            $rows[] = $row['Number'];
        }
        return $rows;
    }
    
    protected function getSiteOrders()
    {
        $rows       = array();
        $arSelect   = Array(
            "ID", 
            "NAME", 
            "DATE_CREATE", 
            "DATE_ACTIVE_FROM", 
            "PROPERTY_NUMBER", 
            "PROPERTY_MAIN_ORDER_ID", 
            "PROPERTY_USER_ID", 
            "PROPERTY_PROLONGED_ORDER",
            "PROPERTY_ALERT_SENDED"
        );
        $arFilter   = Array(
            "IBLOCK_ID"                 => self::IB_ORDERS, 
            "ACTIVE"                    => "Y", 
            "!PROPERTY_MAIN_ORDER_ID"   => false, 
            "!PROPERTY_DELETED_ORDER"   => DELETED_ORDER_VALUE, 
            "PROPERTY_PAYMENT"          => false,
			"!PROPERTY_NO_DELETE"		=> NO_DELETE,
            "PROPERTY_CONFIRMED"        => false,
            "PROPERTY_COLLECTED"        => false,
            "PROPERTY_CLOSED"           => false,
            array(
                "LOGIC"     => "OR",
                array(
                    "!DATE_ACTIVE_FROM"  => false, 
                    "<=DATE_ACTIVE_FROM" => date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("SHORT")), strtotime("now")),
                    ">=DATE_ACTIVE_FROM" => date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("SHORT")), strtotime("-1 month"))
                ),
                array(
                    "DATE_ACTIVE_FROM"   => false, 
                    "<=DATE_CREATE" => date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("SHORT")), strtotime("now")),
                    ">=DATE_CREATE" => date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("SHORT")), strtotime("-1 month"))
                )
            )
        );
        $res        = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        
        while($row = $res->Fetch()) {
            $rows[] = array(
                'ID'                => $row['ID'], 
                'NAME'              => $row['NAME'],
                'ORDER_ID'          => $row['PROPERTY_NUMBER_VALUE'], 
                'DATE_ACTIVE_FROM'  => $row['DATE_ACTIVE_FROM'], 
                'DATE_CREATE'       => $row['DATE_CREATE'],
                'USER_ID'           => $row['PROPERTY_USER_ID_VALUE'],
                'PROLONGED_ORDER'   => $row['PROPERTY_PROLONGED_ORDER_VALUE'],
                'ALERT_SENDED'      => $row['PROPERTY_ALERT_SENDED_VALUE']
            );
        }

        return $rows;
    }
    
    protected function setDeletedOrder($id)
    {
        $iblock_id  = self::IB_ORDERS;
        $code       = "DELETED_ORDER";                              // код свойства
        $values     = array('VALUE' => DELETED_ORDER_VALUE);        // значение свойства
        
        CIBlockElement::SetPropertyValuesEx($id, $iblock_id, array($code => $values));
    }
    
    public function checkTimeToDeleteOrder()
    {
        require_once (P_LIBRARY . "workCalendar/workCalendar.class.php");
        $calendar = new WorkCalendar();

        $arSiteOrders = $this->getSiteOrders();
        $delTime      = intval(getSettingText('DELETE_ORDER_TIME'));
        $upTime       = intval(getSettingText('UPDATE_ORDER_TIME'));
        $now          = strtotime("now");
        
        foreach($arSiteOrders as $arOrder) {
            $addDays = !!$arOrder["PROLONGED_ORDER"] ? $delTime + $upTime : $delTime;
            printr($arOrder);

            $activeFrom = ($arOrder["DATE_ACTIVE_FROM"] ? $arOrder["DATE_ACTIVE_FROM"] : $arOrder["DATE_CREATE"]);
            $calendar->setDate($activeFrom);
            $calendar->addDaysToDate($addDays);

            $diff = strtotime($calendar->getDate()) - $now;
            if ($diff >= 0 && $diff <= 86400 && !$arOrder['ALERT_SENDED']) {
                $this->sendAlertEvent($arOrder);
            }
        }
    }
    
    protected function sendAlertEvent($order)
    {
        $dbUser = CUser::GetByID($order["USER_ID"]);
        if(!$arUser = $dbUser->Fetch()){    
            return false;
        }
        $arEventFields = array(
            "USER_NAME"     => $arUser['NAME'],
            "USER_EMAIL"    => $arUser['EMAIL'],
            "ORDER"         => $order['ORDER_ID']
        );
        if (!CEvent::Send('DELETE_ORDER_ALERT', SS_SITE_ID, $arEventFields)) {
            return false;
        }

        $this->setAlertSended($order['ID']);
        return true;
    }
    
    protected function setAlertSended($id)
    {
        $iblock_id  = self::IB_ORDERS;
        $code       = "ALERT_SENDED";                              // код свойства
        $values     = array('VALUE' => ALERT_SENDED_VALUE);        // значение свойства
        
        CIBlockElement::SetPropertyValuesEx($id, $iblock_id, array($code => $values));
    }
}