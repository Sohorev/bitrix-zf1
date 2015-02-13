<?

class xmlExchange {

    public $storeFolder = '';

    public function __construct()
    {
        $arFilter = array("IBLOCK_ID" => IB_EXCHANGE_SETTINGS);
        $arSelect = array("IBLOCK_ID", "ID", "PROPERTY_XML_STORE_FOLDER");
        $rsSettings = CIBlockElement::GetList(array(),$arFilter,null,null,$arSelect);

        while($row = $rsSettings->Fetch()){
            $arSettings = $row;
        }

        $this->storeFolder = $arSettings['PROPERTY_XML_STORE_FOLDER_VALUE'];
    }
}

?>