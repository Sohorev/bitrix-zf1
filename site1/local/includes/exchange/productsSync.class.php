<?

class productsSync extends productsImport {

    public function sync()
    {
        $arDBProducts     = array();
        $arOrders         = array();
        $arDeleteProducts = array();
        $arTree           = array();

        $query = 'SELECT code FROM ' . self::IMPORT_PRODUCTS_TABLE;
        $query = $this->toWindows($query);

        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT: ' . mssql_get_last_message());
        }
        
        while ($row = mssql_fetch_assoc($result)) {
            $row = parent::clearArrayValues($row);
            $arDBProducts[$this->toUnicode($row["code"])] = 1;
        }

        if (!CModule::IncludeModule('iblock')) {
            die('Не удалось подключить модуль инфоблоки');
        }

        $arOrder  = array();
        $arFilter = array("IBLOCK_ID" => IB_PRODUCTS);
        $arSelect = array("ID", "XML_ID");

        $rsSiteProducts = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
        while ($arProduct = $rsSiteProducts->Fetch()) {
            if (! $arDBProducts[$arProduct["XML_ID"]]) {
                $arDeleteProducts[] = $arProduct["XML_ID"];

                // Удаляем товары с сайта
                CIBlockElement::Delete($arProduct["ID"]);
                CPicture::__delete($arProduct["XML_ID"], array());

                // Находим данные товары в списке заказанных.
                $rsOrderProducts = CIBlockElement::GetList(
                    array("id" => "asc"),
                    array(
                        "IBLOCK_ID"                => self::IB_ORDER_PRODUCTS,
                        "PROPERTY_PRODUCT_XML_ID"  => $arProduct["XML_ID"]
                    ),
                    false,
                    false,
                    array("ID", "PROPERTY_ORDER_ID")
                );

                // Находим все заказы и запросы, в которых фигурирует данный товар
                // TODO: ограничить выборку, как минимум, по дате
                while ($arOrderProduct = $rsOrderProducts->Fetch()) {
                    $rsOrdersToBlock = CIBlockElement::GetList(
                        array("id" => "asc"),
                        array(
                            "IBLOCK_ID" => self::IB_ORDERS,
                            "PROPERTY_ORDER_ID" => $arOrderProduct["PROPERTY_ORDER_ID_VALUE"],
                            "PROPERTY_CLOSED"          => false,
                            "PROPERTY_DELETED_ORDER"   => false,
                            "PROPERTY_PRODUCT_DELETED" => false
                        ),
                        false,
                        false,
                        array("ID", "PROPERTY_ORDER_ID", "PROPERTY_MAIN_ORDER_ID")
                    );

                    while ($arOrderToBlock = $rsOrdersToBlock->Fetch()) {
                        $arOrders[] = $arOrderToBlock["ID"];
                        UserHelper::markOrderAsDeletedProduct($arOrderToBlock["ID"]);

                        if ($arOrderToBlock["PROPERTY_MAIN_ORDER_ID_VALUE"]) {
                            $arTree[$arProduct["XML_ID"]][] = $arOrderToBlock["PROPERTY_ORDER_ID_VALUE"];
                        }
                    }
                }
            }
        }

        if (count($arTree)) {
            $message = "При синхронизации баз данных с сайта были удалены некоторые продукты, находившиеся в резервах.\n\n";
            foreach ($arTree as $productXMLID => $orders) {
                $message .= "Продукт: {$productXMLID}.\nРезервы:\n";
                foreach($orders as $order) {
                    $message .= "  - {$order}\n";
                }
                $message .= "\n";
            }

            CEvent::Send("DELETED_ORDER_PRODUCT", array(SS_SITE_ID), array("MESSAGE" => $message));
            //debug($message);
        }
    }
}

?>