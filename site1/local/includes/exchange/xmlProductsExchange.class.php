<?php

/**
 * Class xmlProductsExchange
 */
class xmlProductsExchange extends xmlExchange {

    /**
     * @var array
     */
    public $arProductFilter = array('IBLOCK_ID' => IB_PRODUCTS, 'ACTIVE' => 'Y');

    /**
     * @var array
     */
    public $arProductFields = array(
        'IBLOCK_ID', 'ID', 'XML_ID', 
        'NAME', 'PROPERTY_ARTICLE', 'PROPERTY_PRICE', 'PROPERTY_BALANCE', 'PROPERTY_RESERV', 
        'IBLOCK_SECTION_ID', 'PROPERTY_BRAND_ID', 'PROPERTY_COLLECTION_ID',
        'DETAIL_TEXT', 'PROPERTY_COUNTRY_ID', 'PROPERTY_COLOR1_ID', 'PROPERTY_WIDTH',
        'PROPERTY_HEIGHT', 'PROPERTY_LENGTH'
    );

    /**
     * Images sizes
     * @var integer
     */
    public $smallImageW  = 138;
    /**
     * @var int
     */
    public $smallImageH  = 138;
    /**
     * @var int
     */
    public $mediumImageW = 258;
    /**
     * @var int
     */
    public $mediumImageH = 258;

    /**
     * @var string
     */
    public $serverName = 'http://ebazaar.ru';

    /**
     * construct
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Main function
     */
    public function export()
    {
        // START XML
        $count = 0;
        $xml = new DOMDocument('1.0', 'utf-8');
        
        // CORE
        $document = $xml->createElement('document');
        $document = $xml->appendChild($document);

        // PRODUCTS
        $products = $xml->createElement('products');
        $products = $document->appendChild($products);

        $rsProducts = $this->getProductList();
        while ($arProduct = $rsProducts->Fetch())
        {
            // PRODUCT
            $product = $xml->createElement('product');
            $product = $products->appendChild($product);

            // TITLE
            $title = $xml->createElement('title');
            $title = $product->appendChild($title);

            $text = $xml->createTextNode($arProduct["NAME"]);
            $text = $title->appendChild($text);

            // ARTICUL
            $articul = $xml->createElement('articul');
            $articul = $product->appendChild($articul);

            $text = $xml->createTextNode($arProduct["PROPERTY_ARTICLE_VALUE"]);
            $text = $articul->appendChild($text);

            // PRICE
            $price = $xml->createElement('price');
            $price = $product->appendChild($price);

            $text = $xml->createTextNode($arProduct["PROPERTY_PRICE_VALUE"]);
            $text = $price->appendChild($text);

            // QUANTITY
            $quantity = $xml->createElement('quantity');
            $quantity = $product->appendChild($quantity);

            $text = $xml->createTextNode($arProduct["PROPERTY_BALANCE_VALUE"]);
            $text = $quantity->appendChild($text);

            // QUANTITY_FREE
            $quantity_free = $xml->createElement('quantity_free');
            $quantity_free = $product->appendChild($quantity_free);

            $text = $xml->createTextNode($arProduct["PROPERTY_RESERV_VALUE"]);
            $text = $quantity_free->appendChild($text);

            // CATEGORY_ID
            $category_id = $xml->createElement('category_id');
            $category_id = $product->appendChild($category_id);

            $text = $xml->createTextNode($arProduct["IBLOCK_SECTION_ID"]);
            $text = $category_id->appendChild($text);

            // BRAND
            $rsBrand = CIBlockElement::GetList(
                array(),
                array("IBLOCK_ID" => IB_BRANDS, "ACTIVE" => "Y", "XML_ID" => $arProduct["PROPERTY_BRAND_ID_VALUE"]),
                false,
                false,
                array("ID", "NAME")
            );
            $arBrand = $rsBrand->Fetch();

            $brand = $xml->createElement('brand');
            $brand = $product->appendChild($brand);

            $text = $xml->createTextNode($arBrand["NAME"]);
            $text = $brand->appendChild($text);

            // COLLECTION
            $rsCollection = CIBlockElement::GetList(
                array(),
                array("IBLOCK_ID" => IB_COLLECTIONS, "ACTIVE" => "Y", "XML_ID" => $arProduct["PROPERTY_COLLECTION_ID_VALUE"]),
                false,
                false,
                array("ID", "NAME")
            );
            $arCollection = $rsCollection->Fetch();

            $collection = $xml->createElement('collection');
            $collection = $product->appendChild($collection);

            $text = $xml->createTextNode($arCollection["NAME"]);
            $text = $collection->appendChild($text);

            // DESCRIPTION
            $description = $xml->createElement('description');
            $description = $product->appendChild($description);

            $text = $xml->createTextNode($arProduct["DETAIL_TEXT"]);
            $text = $description->appendChild($text);

            // MANUFACTURER
            $manufacturer = $xml->createElement('manufacturer');
            $manufacturer = $product->appendChild($manufacturer);

            // COLOR
            $rsColor = CIBlockElement::GetList(
                array(),
                array("IBLOCK_ID" => IB_COLORS, "ACTIVE" => "Y", "XML_ID" => $arProduct["PROPERTY_COLOR1_ID_VALUE"]),
                false,
                false,
                array("ID", "NAME")
            );
            $arColor = $rsColor->Fetch();

            $color = $xml->createElement('color');
            $color = $product->appendChild($color);

            $text = $xml->createTextNode($arColor["NAME"]);
            $text = $color->appendChild($text);

            // SIZE
            $strSize = '';
            $strSize .= $arProduct["PROPERTY_WIDTH_VALUE"] . '/';
            $strSize .= $arProduct["PROPERTY_HEIGHT_VALUE"] . '/';
            $strSize .= $arProduct["PROPERTY_LENGTH_VALUE"];

            $size = $xml->createElement('size');
            $size = $product->appendChild($size);

            $text = $xml->createTextNode($strSize);
            $text = $size->appendChild($text);

            // IMAGES
            $arImages = $this->getProductImagesSrc($arProduct["XML_ID"]);

            // SMALL_IMAGE
            $smallImagePath = $this->getFullPath($arImages["SMALL"]);
            
            $small_image = $xml->createElement('small_image');
            $small_image = $product->appendChild($small_image);

            $text = $xml->createTextNode($smallImagePath);
            $text = $small_image->appendChild($text);

            // MIDDLE_IMAGE
            $middleImagePath = $this->getFullPath($arImages["MIDDLE"]);
            
            $middle_image = $xml->createElement('middle_image');
            $middle_image = $product->appendChild($middle_image);

            $text = $xml->createTextNode($middleImagePath);
            $text = $middle_image->appendChild($text);

            // BIG_IMAGE
            $bigImagePath = $this->getFullPath($arImages["BIG"]);
            
            $big_image = $xml->createElement('big_image');
            $big_image = $product->appendChild($big_image);

            $text = $xml->createTextNode($bigImagePath);
            $text = $big_image->appendChild($text);

            // ADDITIONAL IMAGES
            $add_images = $xml->createElement('add_images');
            $add_images = $product->appendChild($add_images);

            foreach ($arImages["ADDITIONAL"] as $addImageKey => $addImage) {
                $addImagePath = $this->getFullPath($addImage);

                $add_image = $xml->createElement('image');
                $add_image = $add_images->appendChild($add_image);

                $text = $xml->createTextNode($addImagePath);
                $text = $add_image->appendChild($text);
            }

            $count++;
        }

        // SECTIONS
        $categories = $xml->createElement('categories');
        $categories = $document->appendChild($categories);

        $rsSections = CIBlockSection::GetList(
            array("left_margin" => "asc"),
            array("IBLOCK_ID" => IB_PRODUCTS, "ACTIVE" => "Y", "ELEMENT_SUBSECTIONS" => "Y", "CNT_ACTIVE" => "Y"),
            true
        );

        while ($arSection = $rsSections->Fetch())
        {
            if (intval($arSection["ELEMENT_CNT"]) > 0) 
            {
                $category = $xml->createElement('category');
                $category = $categories->appendChild($category);

                $attr = $xml->createAttribute('id');
                $attr->value = $arSection["ID"];
                $attr = $category->appendChild($attr);

                $attr = $xml->createAttribute('parent_id');
                $attr->value = $arSection["IBLOCK_SECTION_ID"] ? $arSection["IBLOCK_SECTION_ID"] : 0;
                $attr = $category->appendChild($attr);

                $attr = $xml->createAttribute('title');
                $attr->value = $arSection["NAME"];
                $attr = $category->appendChild($attr);
            }
        }

        $fileName =  P_DR . $this->storeFolder . 'products.xml';
        file_put_contents($fileName, $xml->saveXML());
    }

    /**
     * @return object
     */
    public function getProductList()
    {
        $rsProducts = CIBlockElement::GetList(array(), $this->arProductFilter, false, false, $this->arProductFields);
        return $rsProducts;
    }


    /**
     * @param $xmlID
     * @return array
     */
    public function getProductImagesSrc($xmlID)
    {
        $arImages = array();
        $images = $this->getProductImagesID($xmlID);

        if (count($images)) {
            $image = array_shift($images);

            $arImages = array(
                "SMALL"  => $this->getProductSmallImageSrc($image),
                "MIDDLE" => $this->getProductMediumImageSrc($image),
                "BIG"    => $this->getProductMainImageSrc($image)
            );

            foreach ($images as $image) {
                $arImages["ADDITIONAL"][] = $this->getProductMainImageSrc($image);
            }
        }

        return $arImages;
    }


    /**
     * @param $xmlID
     * @return mixed
     */
    public function getProductMainImageID($xmlID)
    {
        global $DB;
                        
        $result = $DB->Query("SELECT * FROM `b_file` WHERE `ORIGINAL_NAME` LIKE '{$xmlID}%' ORDER BY `ORIGINAL_NAME` ASC LIMIT 1");
        $arImage = $result->Fetch();

        return $arImage["ID"];
    }

    /**
     * @param $xmlID
     * @return array
     */
    public function getProductImagesID($xmlID)
    {
        global $DB;
        $arImages = array();

        $result = $DB->Query("SELECT * FROM `b_file` WHERE `ORIGINAL_NAME` LIKE '{$xmlID}%' ORDER BY `ORIGINAL_NAME` ASC");
        while ($arImage = $result->Fetch()) {
            $arImages[] = $arImage["ID"];
        }

        return $arImages;
    }


    /**
     * @param $mainImageID
     * @return mixed
     */
    public function getProductMainImageSrc($mainImageID)
    {
        $src = CFile::GetPath($mainImageID);
        return $src;
    }


    /**
     * @param $mainImageID
     * @return mixed
     */
    public function getProductSmallImageSrc($mainImageID)
    {
        $arImage = CFile::ResizeImageGet($mainImageID, array("width" => $this->smallImageW, "height" => $this->smallImageH));
        return $arImage["src"];
    }


    /**
     * @param $mainImageID
     * @return mixed
     */
    public function getProductMediumImageSrc($mainImageID)
    {
        $arImage = CFile::ResizeImageGet($mainImageID, array("width" => $this->mediumImageW, "height" => $this->mediumImageH));
        return $arImage["src"];
    }

    /**
     * @param  string $path
     * @return string
     */
    public function getFullPath($path)
    {
        $path = preg_replace('#//+#', '/', $path);
        return $this->serverName . $path;
    }
}