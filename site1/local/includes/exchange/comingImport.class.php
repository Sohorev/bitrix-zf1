<?

class comingImport extends productsImport 
{
    public function import()
    {
        $selectFields = 'code, date, in_stock';

        $query  = 'SELECT ' . $selectFields . ' FROM ' . self::IMPORT_COMING_TABLE;
        $query  = $this->toWindows($query);

        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT: ' . mssql_get_last_message());
        }

        /**
         * Получаем все элементы инфоблока. Записываем в специальный ассоциативный массив для сравнения
         */
        if (! CModule::IncludeModule('iblock')) { die('Модуль инфоблоки не установлен на сайте'); }

        $arOrder  = array();
        $arFilter = array("IBLOCK_ID" => self::IB_COMING, "ACTIVE" => "Y");
        $arSelect = array("ID", "NAME", "PROPERTY_PRODUCT_XML_ID", "PROPERTY_DATE", "PROPERTY_QUANTITY");

        $rsComing = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
        while ($arComing = $rsComing->GetNext()) {
            $arResult[$arComing["PROPERTY_PRODUCT_XML_ID_VALUE"]][$arComing["PROPERTY_DATE_VALUE"]] = array(
                "ID"       => $arComing["ID"],
                "QUANTITY" => $arComing["PROPERTY_QUANTITY_VALUE"]
            );
        }

        /**
         * В таком же виде получаем все записи из таблицы
         */
        while ($row = mssql_fetch_assoc($result)) {
            $row = parent::clearArrayValues($row);
            
            //$ts = MakeTimeStamp($row["date"], "YYYY-MM-DD");
            $ts = strtotime($row["date"]);
            $convertedDate = ConvertTimeStamp($ts, "SHORT");
            
            $arRows[$this->toUnicode($row["code"])][$convertedDate] = array(
                "QUANTITY" => $row["in_stock"]
            );
        }

        $iblock = new CIBlockElement();

        /**
         * Начинаем сравнивать массивы. Все совпавшие элементы обновляем при необходимости, удаляя из массива.
         * Удаляем их для того, чтобы оставшиеся затем удалить с сайта.
         */
        foreach ($arRows as $code => $arValues) {
            foreach ($arValues as $date => $arQuantity) {
                if ($siteArQuantity = $arResult[$code][$date]) {
                    // Если существует запись с текущим кодом и датой прихода, проверяем, не изменено ли количество. 
                    // Если изменено, то переписываем.
                    if ($siteArQuantity["QUANTITY"] !== $arQuantity["QUANTITY"]) {
                        // update
                        CIBlockElement::SetPropertyValuesEx(
                            $siteArQuantity["ID"], 
                            false, 
                            array("QUANTITY" => $arQuantity["QUANTITY"])
                        );
                    }
                    unset($arResult[$code][$date]);
                } else {
                    // Если записи с текущим кодом и датой прихода не существут, добавляем
                    $arNewFields = array(
                        "IBLOCK_ID"       => self::IB_COMING,
                        "NAME"            => $code,
                        "ACTIVE"          => "Y",
                        "PROPERTY_VALUES" => array(
                            "PRODUCT_XML_ID" => $code,
                            "DATE"           => $date,
                            "QUANTITY"       => $arQuantity["QUANTITY"]
                        )
                    );
                    if (!$newId = $iblock->Add($arNewFields)) { die($iblock->LAST_ERROR); }
                }
            }
        }

        /**
         * Теперь перебираем все записи из Битрикса, удаляем те, которые не были затронуты во время импорта.
         * Это означает, что данных записей нет в mssql-базе и они должны быть удалены с сайта.
         */
        foreach ($arResult as $code => $array) {
            if ($array) {
                foreach ($array as $date => $ar) {
                    CIBlockElement::Delete($ar["ID"]);
                }
            }
        }
    }
}

?>