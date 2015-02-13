<?

class xmlProductsQuantityExchange extends xmlExchange {

    public function __construct(){
        parent::__construct();
    }
    
    public function export(){
        
        $arFilter = array('IBLOCK_ID' => IB_PRODUCTS);
        $arFields = array('IBLOCK_ID', 'ID', 'PROPERTY_ARTICLE', 'PROPERTY_BALANCE', 'PROPERTY_RESERV');
        
        $dbProducts = CIBlockElement::GetList(null, $arFilter, null, null, $arFields);
        
        $xml = new DOMDocument('1.0', 'utf-8');
        
        $document =  $xml->createElement('document');
        $xml->appendChild($document);
        
        while($row = $dbProducts->Fetch()){
            
            $articul = $xml->createElement('articul', $row['PROPERTY_ARTICLE_VALUE']);
            $quantity = $xml->createElement('quantity', $row['PROPERTY_BALANCE_VALUE']);
            $quantityFree = $xml->createElement('quantity_free', ($row['PROPERTY_BALANCE_VALUE'] - $row['PROPERTY_RESERV_VALUE']));

            $product = $xml->createElement('product');
            $document->appendChild($product);
            
            $product->appendChild($articul);
            $product->appendChild($quantity);
            $product->appendChild($quantityFree);
            
        }
        
        $fileName =  P_DR . $this->storeFolder . 'products-quantity.xml';
        
        file_put_contents($fileName, $xml->saveXML());
        
    }

}

?>