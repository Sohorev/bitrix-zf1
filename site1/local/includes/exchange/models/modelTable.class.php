<?php require_once P_EXCHANGE_CLASSES . "models/exchangeModel.class.php";

class ModelTable extends ExchangeModel
{
    protected $_table;

    public function get($condition = array())
    {
        if (! $result = $this->doSelect(
            $this->_table,
            $condition,
            null,
            "Произошла ошибка выборки из таблицы {$this->_table}"
        )) {
            return false;
        }

        return $result;
    }

    public function add($fields)
    {
        if (! $this->doInsert(
            $this->_table,
            $fields,
            "Произошла ошибка добавления записи в таблицу {$this->_table}"
        )) {
            return false;
        }

        return true;
    }

    public function update($fields, $condition = array())
    {
        if (! $this->doUpdate(
            $this->_table,
            $fields,
            $condition,
            "Произошла ошибка обновления записи в таблице {$this->_table}"
        )) {
            return false;
        }

        return true;
    }
}