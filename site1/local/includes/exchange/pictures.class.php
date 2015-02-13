<?

class CPicture {

    static protected function __isSaved($fileName){
        
            global $DB;
        
        $result = $DB->Query("SELECT `ID` FROM `b_file` WHERE `ORIGINAL_NAME` = '{$fileName}'");
        if(!$row = $result->Fetch()){
            return false;
        }
    
        if($row['ID']){
            return $row['ID'];
        } else {
            return false;
        }
    }
    
    static public function __delete($xmlId, $arExclude){
        global $DB;

        $sqlExclude = '';
        if(count($arExclude)){
            foreach($arExclude as $value){
                $sqlExclude .= " AND `ORIGINAL_NAME` <> '{$value}'";
            }
        }
        
        $xmlId = exchange::toUnicode($xmlId);
        $result = $DB->Query("SELECT `ID` FROM `b_file` WHERE `ORIGINAL_NAME` LIKE '{$xmlId}%'{$sqlExclude}");
        while($row = $result->Fetch()){
            CFile::Delete($row['ID']);
        }
        return true;
    }
    
    static public function save($fileName){
        $arFile = CFile::MakeFileArray($fileName);
        $arFile['MODULE_ID'] = 'iblock';

        $arFile['old_file'] = self::__isSaved($arFile['name']);
        $arFile['del'] = 'Y';
        
        $path = '/imported/';
        if($id = CFile::SaveFile($arFile, $path)){
            return $id;
        } else {
            return false;
        }
    }

    static public function exists($productXMLId) {
        global $DB;
        $result = $DB->Query("SELECT * FROM `b_file` WHERE `ORIGINAL_NAME` LIKE '{$productXMLId}%' ORDER BY ORIGINAL_NAME ASC");

        if ($result->Fetch()) {
            return true;
        }

        return false;
    }

}

?>