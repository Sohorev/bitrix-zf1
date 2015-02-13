<?

/**
 * Class productsLightImport
 */
class productsLightImport extends productsImport {

    /**
     *
     */
    public function import($selectAll = false) {
        $selectFields = 'code, price, kurs, priceue, balance, reserv, ComingReserv';

        $query  = 'SELECT ' . $selectFields . ' FROM ' . self::IMPORT_PRODUCTS_TABLE;
        $query .= $selectAll ? '' : ' WHERE mark=0x01';

        $query = $this->toWindows($query);
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT: ' . mssql_get_last_message());
        }

        while ($row = mssql_fetch_assoc($result)) {
            $row = parent::clearArrayValues($row);
            $this->updateProps($row);
        }
    }

    /**
     * @param $itemsXmlIdsStr
     * @return bool
     */
    private function lightSync($itemsXmlIdsStr) {
        $selectFields = 'code, price, kurs, priceue, balance, reserv, ComingReserv';

        $query = 'SELECT ' . $selectFields . ' 
					FROM ' . self::IMPORT_PRODUCTS_TABLE . " 
					WHERE code IN (" . $itemsXmlIdsStr . ")";
        $query = $this->toWindows($query);
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT: ' . mssql_get_last_message());
        }

        while ($row = mssql_fetch_assoc($result)) {
            $row = parent::clearArrayValues($row);
            if (!$this->updateProps($row)) return false;
        }

        return true;
    }

    /**
     * @param $itemsCurrentBalance
     * @return bool
     */
    public function syncBasket($itemsCurrentBalance) {
        $success = false;
        $itemsXmlIds = array();
        foreach ($itemsCurrentBalance as $item) {
            $itemsXmlIds[] = "'" . $item['xml-id'] . "'";
        }
        $itemsXmlIdsStr = implode(",", $itemsXmlIds);

        $selectFields = 'code, balance, reserv';
        $query = "SELECT " . $selectFields . "
					FROM " . self::IMPORT_PRODUCTS_TABLE . " 
					WHERE code IN (" . $itemsXmlIdsStr . ")";
        $query = $this->toWindows($query);
        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT: ' . mssql_get_last_message());
        }
        $needSync = false;
        while ($row = mssql_fetch_assoc($result)) {
            foreach ($itemsCurrentBalance as $item) {
                if ($item['xml-id'] == $this->toUnicode($row['code'])) {
                    //echo $item['balance'] . " " . $row['balance'] . "|" . $item['reserve'] . " " . $row['reserv'];
                    if ($item['balance'] != $row['balance'] || $item['reserve'] != $row['reserv']) $needSync = true;
                }
            }
            if ($needSync) {
                $success = $this->lightSync($itemsXmlIdsStr);
                break;
            }
        }
        return $success;
    }
}