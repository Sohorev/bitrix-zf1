<?php

class SpecialImport extends productsImport {
    const propertyCode = 'SPECIAL';
    const propertyId   = ENUM_SPECIAL_PROPERTY_ID;
    public function import() {
        if (! CModule::IncludeModule('iblock')) {
            die('Модуль инфоблоки не установлен на сайте');
        }

        /**
         * Выбираем все значения спецпредложений на сайте
         */
        $arSort     = array(
            'XML_ID' => 'ASC'
        );
        $arFilter   = array(
            'IBLOCK_ID' => self::IB_PRODUCTS,
            'CODE'      => self::propertyCode
        );
        $rsSiteSpecial = CIBlockPropertyEnum::GetList($arSort, $arFilter);
        $arSiteSpecials = array();
        while ($arSiteSpecial = $rsSiteSpecial->Fetch()) {
            $arSiteSpecials[$arSiteSpecial['XML_ID']] = $arSiteSpecial['ID'];
        }

        /**
         * Выберем все доступные Спецпредложения в буферной базе
         * Сразу смотрим если такого спецпредложения нет на сайте - добавляем
         */
        $selectFields = 'code, descr';
        $query  = 'SELECT ' . $selectFields . ' FROM ' . self::IMPORT_FILTERS;
        $query  = $this->toWindows($query);

        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT: ' . mssql_get_last_message());
        }

        $arSpecials     = array();
        $arSiteXmlIds   = array_keys($arSiteSpecials);
        $ibpEnum        = new CIBlockPropertyEnum;
        while ($row = mssql_fetch_assoc($result)) {
            $row    = $this->clearArrayValues($row);
            $code   = $this->toUnicode($row["code"]);
            $name   = $this->toUnicode($row["descr"]);

            if ($name && !in_array($code, $arSiteXmlIds)) {
                /* Если спецпредложения нет на сайте - добавляем */
                $arFields = array(
                    'PROPERTY_ID'   => self::propertyId,
                    'VALUE'         => $name,
                    'XML_ID'        => $code
                );

                if ($enumId = $ibpEnum->Add($arFields)) {
                    /* При успешном добавлении добавляем к массиву Спецпредложений сайта */
                    $arSiteSpecials[$code] = $enumId;
                    $arSiteXmlIds = array_keys($arSiteSpecials);
                } else {
                    die('Ошибка при обновлении свойства типа список в ИБ');
                }
            }

            /* Тут связь код_спецпредложения => название */
            $arSpecials[$code] = $name;
        }

        /**
         * Выберем все связи товаров и спецпредложения в буферной базе
         */
        $selectFields = 'code, descr';
        $query  = 'SELECT ' . $selectFields . ' FROM ' . self::IMPORT_FILTERS_REL;
        $query  = $this->toWindows($query);

        if (!$result = mssql_query($query, $this->rsMsSQL)) {
            die('Не могу выполнить SELECT: ' . mssql_get_last_message());
        }

        $arSpecialsRel = array();
        while ($row = mssql_fetch_assoc($result)) {
            $row = $this->clearArrayValues($row);
            /* Тут связь код_товара => array(код_спецпредложения) */
            $arSpecialsRel[$this->toUnicode($row["code"])][] = $this->toUnicode($row["descr"]);
        }

        /**
         * Выберем все товары на сайте
         */
        $arSpecialsRelXmlIds = array_keys($arSpecialsRel);
        $arSort     = array(
            'NAME' => 'ASC'
        );
        $arFilter   = array(
            'IBLOCK_ID' => self::IB_PRODUCTS,
        );
        $arSelect   = array(
            'ID', 'XML_ID'
        );
        $rsProduct = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
        /* Смотрим есть ли обновления для данного товара, если есть - обновляем, нет - обнуляем */
        while ($arProduct = $rsProduct->Fetch()) {
            $value = array();
            if (in_array($arProduct['XML_ID'], $arSpecialsRelXmlIds)) {
                foreach ($arSpecialsRel[$arProduct['XML_ID']] as $specialCode) {
                    $value[] = array("VALUE" => $arSiteSpecials[$specialCode]);
                }
            }
            CIBlockElement::SetPropertyValues($arProduct['ID'], self::IB_PRODUCTS, $value, self::propertyCode);
        }
    }
}