<?php

/**
 * Class filesExport
 */
class filesExport extends export {
    /**
     * @var null
     */
    public $orderId = null;

    /**
     * @var null
     */
    public $fromHistory = null;

    /**
     * @return array
     */
    public function export() {
        $success = true;
        $message = 'Файлы успешно прикреплены';
        if (!$this->fromHistory) {
            $arFiles = $_SESSION["USER_FILES"];
        } else {
            $arFiles = $_SESSION["USER_FILES_" . $this->orderId];
        }

        if (!is_array($arFiles)) {
            $message = 'Список файлов пуст!';
            return array("success" => $success, "message" => $message, "files" => $arFiles);
        }

        $table   = self::IMPORT_FILES_TABLE;
        $orderId = self::SITE_ID . $this->orderId;

        foreach ($arFiles as $fileName => $origFileName) {
            $sql = "INSERT INTO {$table} (OrderID, FileID, FileName) VALUES ('{$orderId}', '{$fileName}', '{$origFileName}')";
            $sql = $this->toWindows($sql);
            if (!mssql_query($sql, $this->rsMsSQL)) {
                $success = false;
                $message = ('Не могу выполнить SELECT: ' . mssql_get_last_message());
            }
        }

        if (!$this->fromHistory) {
            unset($_SESSION["USER_FILES"]);
        } else {
            unset($_SESSION["USER_FILES_" . $this->orderId]);
        }

        return array("success" => $success, "message" => $message, "files" => $arFiles);
    }
}