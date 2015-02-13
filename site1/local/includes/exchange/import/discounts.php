<?php require_once P_EXCHANGE_CLASSES . "exchange.class.php";

class ImportDiscounts extends exchange
{
    public function __construct()
    {
        parent::__construct();
    }

    public function import()
    {
        require_once P_EXCHANGE_CLASSES . "models/tables/discounts.php";

        $discounts = new TableDiscounts();

        $result = $discounts->get(array(
            "ContactorSt" => "4548"
        ));

        while ($row = mssql_fetch_assoc($result)) {
            $this->_needUpdate[] = $this->clearArrayValues($row);
        }

        debug($this->_needUpdate);
    }
}