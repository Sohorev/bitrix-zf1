<?
$APPLICATION->IncludeComponent(
    "bitrix:catalog.section", "other_page", array(
    "IBLOCK_TYPE" => "main_sections",
    "IBLOCK_ID" => "17",
    "SECTION_ID" => $_REQUEST["SECTION_ID"],
    "SECTION_CODE" => "",
    "SECTION_USER_FIELDS" => array(
        0 => "",
        1 => "",
    ),
    "ELEMENT_SORT_FIELD" => "sort",
    "ELEMENT_SORT_ORDER" => "asc",
    "ELEMENT_SORT_FIELD2" => "id",
    "ELEMENT_SORT_ORDER2" => "asc",
    "FILTER_NAME" => "arrFilter",
    "INCLUDE_SUBSECTIONS" => "Y",
    "SHOW_ALL_WO_SECTION" => "N",
    "PAGE_ELEMENT_COUNT" => "30",
    "LINE_ELEMENT_COUNT" => "3",
    "PROPERTY_CODE" => array(
        0 => "",
        1 => "",
    ),
    "OFFERS_LIMIT" => "5",
    "TEMPLATE_THEME" => "blue",
    "ADD_PICT_PROP" => "-",
    "LABEL_PROP" => "-",
    "MESS_BTN_BUY" => "Купить",
    "MESS_BTN_ADD_TO_BASKET" => "В корзину",
    "MESS_BTN_SUBSCRIBE" => "Подписаться",
    "MESS_BTN_COMPARE" => "Сравнить",
    "MESS_BTN_DETAIL" => "Подробнее",
    "MESS_NOT_AVAILABLE" => "Нет в наличии",
    "SECTION_URL" => "",
    "DETAIL_URL" => "",
    "SECTION_ID_VARIABLE" => "SECTION_ID",
    "AJAX_MODE" => "N",
    "AJAX_OPTION_JUMP" => "N",
    "AJAX_OPTION_STYLE" => "Y",
    "AJAX_OPTION_HISTORY" => "N",
    "CACHE_TYPE" => "A",
    "CACHE_TIME" => "36000000",
    "CACHE_GROUPS" => "Y",
    "SET_TITLE" => "Y",
    "SET_BROWSER_TITLE" => "Y",
    "BROWSER_TITLE" => "-",
    "SET_META_KEYWORDS" => "Y",
    "META_KEYWORDS" => "-",
    "SET_META_DESCRIPTION" => "Y",
    "META_DESCRIPTION" => "-",
    "ADD_SECTIONS_CHAIN" => "N",
    "SET_STATUS_404" => "N",
    "CACHE_FILTER" => "N",
    "ACTION_VARIABLE" => "action",
    "PRODUCT_ID_VARIABLE" => "id",
    "PRICE_CODE" => array(
    ),
    "USE_PRICE_COUNT" => "N",
    "SHOW_PRICE_COUNT" => "1",
    "PRICE_VAT_INCLUDE" => "Y",
    "BASKET_URL" => "/personal/basket.php",
    "USE_PRODUCT_QUANTITY" => "N",
    "ADD_PROPERTIES_TO_BASKET" => "Y",
    "PRODUCT_PROPS_VARIABLE" => "prop",
    "PARTIAL_PRODUCT_PROPERTIES" => "N",
    "PRODUCT_PROPERTIES" => array(
    ),
    "DISPLAY_COMPARE" => "N",
    "PAGER_TEMPLATE" => ".default",
    "DISPLAY_TOP_PAGER" => "N",
    "DISPLAY_BOTTOM_PAGER" => "Y",
    "PAGER_TITLE" => "Товары",
    "PAGER_SHOW_ALWAYS" => "N",
    "PAGER_DESC_NUMBERING" => "N",
    "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
    "PAGER_SHOW_ALL" => "N",
    "AJAX_OPTION_ADDITIONAL" => "",
    "PRODUCT_QUANTITY_VARIABLE" => "quantity"
    ), false
);
?>
<footer>
    <div class="footer">
        <div class="sitetext"><span class="whitetext">Сайт разработан студией "4D проект"</span></div>
        <?
        $APPLICATION->IncludeComponent("bitrix:search.form", "bottom", array(
            "PAGE" => "#SITE_DIR#search/index.php",
            "USE_SUGGEST" => "N"
            ), false
        );
        ?>
    </div>
</footer>
</body>
</html>