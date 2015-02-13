<?php

/**
 * Набор общих хелперов
 */
class Quickpay_Helper {
    /**
     * @var bool
     */
    private static $log = false;
    protected static $siteData;

    /**
     * Логирование
     * @return bool
     */
    static function log() {
        if (self::$log === false) {
            self::$log = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('Log');
        }
        return self::$log;
    }

    /**
     * Получение опций из конфига
     * @param $name
     * @param null $section
     * @return null
     */
    static function opt($name, $section = null) {
        $options = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption($name);
        if ($section) {
            $options = (isset($options[$section]) ? $options[$section] : null);
        }
        return $options;
    }

    /**
     * Проверяет, находимся ли мы на главной странице
     * @return bool
     */
    public static function isMain() {
        return ($GLOBALS['APPLICATION']->GetCurPage(false) == '/');
    }

    /**
     * форматирует цену, разбивает на разряды
     * @param int $price
     * @return string
     */
    public static function price($price) {
        return number_format((float) $price, 0, '.', ' ');
    }

    /**
     * Получает все настройки сайта
     */
    protected static function getData() {
        if (self::$siteData === null) {
            $rsSites = CSite::GetByID(SITE_ID);
            self::$siteData = $rsSites->Fetch();
        }
        return self::$siteData;
    }

    /**
     * Получает значение параметра сайта по ключу
     * @param string $key
     * @return string
     */
    public static function get($key) {
        $data = self::getData();
        if (isset($data[$key])) {
            return $data[$key];
        }

        switch ($key) {
            case 'HOST':
                return $_SERVER['HTTP_HOST'];
        }

        return null;
    }

    /**
     * Возвращает массив настройки сайта по ее символьному коду
     *
     * @param  string $code
     * @param  int    $iblockId
     * @return array
     */
    public static function getSetting($code, $iblockId = IB_SETTINGS) {
        CModule::IncludeModule('iblock');

        $obCache = new CPHPCache;                     // Объект класса CPHPCache
        $lifeTime = 36000000;                          // Время жизни кэша
        $cacheId = $code . $iblockId;                   // ID Кэша
        $cachePath = '/' . SS_SITE_ID . '/settings/';   // Путь сохранения
        // Если значение есть в кэше, берем оттуда
        if ($obCache->InitCache($lifeTime, $cacheId, $cachePath)) {

            $vars = $obCache->GetVars();
            $arSetting = $vars["SETTING"];

            // Иначе делаем запрос в базу и сохраням в кэш
        } else {

            if ($obCache->StartDataCache()) {

                $arSetting = CIBlockElement::GetList(
                        array(), array(
                        "IBLOCK_ID" => $iblockId,
                        "ACTIVE" => "Y",
                        "CODE" => $code
                        ), false, false, array(
                        "ID",
                        "CODE",
                        "ACTIVE_FROM",
                        "SORT",
                        "NAME",
                        "PREVIEW_TEXT",
                        "PREVIEW_TEXT_TYPE",
                        "PREVIEW_PICTURE",
                        "DETAIL_PICTURE",
                        "PROPERTY_FILE",
                        "PROPERTY_TEXT"
                        )
                    )->Fetch();

                $obCache->EndDataCache(array("SETTING" => $arSetting));
            }
        }
        return $arSetting;
    }

    public static function getSettingName($code) {

        $arSetting = Quickpay_Helper::getSetting($code);
        return $arSetting['NAME'];
    }

    public static function getSettingText($code) {

        $arSetting = Quickpay_Helper::getSetting($code);
        return $arSetting['PREVIEW_TEXT'];
    }

    /**
     * JS константы для добавления в шапку
     * @return string
     */
    public static function jsApp() {

        $jsApp = array(
            'USER' => array(
                'IS_LOGGED' => $GLOBALS['USER']->IsAuthorized()
            ),
            'PAGE' => Array(
                'TITLE_ADD' => ' — ' . Quickpay_Helper::get('NAME')
            )
        );

        return '<script type="text/javascript">var APP = ' . json_encode($jsApp) . '; </script>';
    }

    /**
     * Определение города по IP
     * Заносит CODE города в сессию
     * Возвращает CODE
     * @return intеger
     */
    public static function getCity() {

        // Если в сессии уже указан город, возвращаем его и выходим
        if ($_SESSION["CITY"]["CODE"]) {
            return $_SESSION["CITY"];
        }

        // Определение города по IP пользователя
        $city = Quickpay_Helper::getCityByIP($_SERVER["REMOTE_ADDR"]);

        if (!$city) {
            return array();
        }

        // Выбор города из инфоблока "Адреса" по имени
        $arCity = Quickpay_Helper::getCityByName($city);


        if (!$arCity) {
            $arCity["ID"] = $arDefault["ID"];
            $arCity["NAME"] = $arDefault["NAME"];
            $arCity["CODE"] = $arDefault["PREVIEW_TEXT"];
        }


        Quickpay_Helper::setCitySession($arCity);

        return Array("ID" => $arCity["ID"], "CODE" => $arCity["CODE"]);
    }

    /**
     * Записывает город в сессию
     * @return bool
     */
    public static function setCitySession($arCity) {
        if (!empty($arCity)) {
            $_SESSION["CITY"]["ID"] = $arCity["ID"];
            $_SESSION["CITY"]["NAME"] = $arCity["NAME"];
            $_SESSION["CITY"]["CODE"] = $arCity["CODE"];
            $_SESSION["CITY"]["REGION"] = $arCity["IBLOCK_SECTION_ID"];
        }

        return true;
    }

    /**
     * Определение города по IP
     * Возвращает массив
     * @return array
     */
    public static function getCityByIP($ip) {
        $f = file_get_contents("http://ipgeobase.ru:7020/geo?ip=" . $ip); // Томск: 92.243.119.137 Барнаул: 80.247.107.10

        $dom = new domDocument();
        $dom->loadXML($f);
        $xml = simplexml_import_dom($dom);
        $city = ucfirst(mb_strtolower(trim($xml->ip->city), 'UTF-8'));
        return $city;
    }

    /**
     * Определение города по названию
     * Возвращает массив
     * @return array
     */
    public static function getCityByName($name) {
        $arFilter = array("IBLOCK_ID" => IB_ADDRESS, "ACTIVE" => "Y", "NAME" => $name, "DEPTH_LEVEL" => 2);
        $arSelect = array("ID", "NAME", "CODE", "IBLOCK_SECTION_ID");
        $res = CIBlockSection::GetList(array(), $arFilter, false, $arSelect);
        $arCity = $res->Fetch();
        return $arCity;
    }

    /**
     * Определение города по ID
     * Возвращает массив
     * @return array
     */
    public static function getCityByID($id) {
        if (!empty($id)) {
            $arFilter = array("IBLOCK_ID" => IB_ADDRESS, "ACTIVE" => "Y", "ID" => $id, "DEPTH_LEVEL" => 2);
            $arSelect = array("ID", "NAME", "CODE", "IBLOCK_SECTION_ID");
            $res = CIBlockSection::GetList(array(), $arFilter, false, $arSelect);
            $arCity = $res->Fetch();
            return $arCity;
        }
        return null;
    }

    /**
     * Заменяет город в сессии по его ID
     * Возвращает массив
     * @return array
     */
    public static function setCitySessionByID($id) {

        $arCity = Quickpay_Helper::getCityByID($id);

        if (!empty($arCity)) {
            Quickpay_Helper::setCitySession($arCity);
        }

        return true;
    }
}
