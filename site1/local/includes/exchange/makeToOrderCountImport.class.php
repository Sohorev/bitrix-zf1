<?

class MakeToOrderCountImport extends productsImport {

    const propertyCode = 'MAKE_TO_ORDER_COUNT';

    public function import() {

        if (! CModule::IncludeModule('iblock')) {
            die('Модуль инфоблоки не установлен на сайте');
        }

        $selectFields = 'code, balance, C1C';
        $query  = 'SELECT ' . $selectFields . ' FROM ' . self::IMPORT_MAKE_TO_ORDER_COUNT;
        $query  = $this->toWindows($query);

        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT: ' . mssql_get_last_message());
        }

        /**
         * Получаем все элементы таблицы NaZakaz
         */
        $arRows = array();
        while ($row = mssql_fetch_assoc($result)) {
            $row = $this->clearArrayValues($row);
            $arRows[$this->toUnicode($row["code"])] = $row["balance"];
        }

        if (!$arRows) {
            die('Нечего обновлять');
        }

        /**
         * Выберем все товары на сайте
         * Если XML_ID товара есть в выборке из базы - обновляем свойство, иначе обнуляем
         */
        $arXmlIds = array_keys($arRows);
        $arSort = array();
        $arFilter = array(
            'IBLOCK_ID' => self::IB_PRODUCTS,
        );
        $arSelect = array(
            'ID', 'XML_ID'
        );

        $rsProduct = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
        while ($arProduct = $rsProduct->Fetch()) {
            $value = false;
            if (in_array($arProduct['XML_ID'], $arXmlIds)) {
                $value = $arRows[$arProduct['XML_ID']];
            }
            CIBlockElement::SetPropertyValues($arProduct['ID'], self::IB_PRODUCTS, $value, self::propertyCode);
        }

        return true;
    }
}