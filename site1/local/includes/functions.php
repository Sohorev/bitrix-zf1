<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Отправитель по-умолчанию
 * @return str
 */
function getMailTo() {
    static $mailTo;
    if (empty($mailTo)) {
        $rsSites = CSite::GetByID(SITE_ID);
        $arSite = $rsSites->Fetch();
        $mailTo = (empty($arSite['EMAIL']) ? DEFAULT_EMAIL_TO : $arSite['EMAIL']);
    }
    return $mailTo;
}

/**
 * Проверяет, находимся ли мы на главной странице
 * @return bool
 */
function isMain() {
    return ($_SERVER['PHP_SELF'] == "/index.php");
}

/**
 * Возвращает информацию о файле
 * @param int|array $fid ID файла, либо массив ID файлов
 * @return array - данные информация о файле
 */
function getFileData($fid) {
    if (!isset($fid)) return;

    if (is_array($fid)) {
        $rsFile = CFile::GetList(array(), array("@ID" => implode(",", $fid)));
    } else {
        $rsFile = CFile::GetByID($fid);
    }

    $ret = array();

    while ($ifile = $rsFile->Fetch()) {
        $ret[$ifile['ID']] = array("SRC" => P_UPLOAD . $ifile["SUBDIR"] . "/" . $ifile['FILE_NAME'], "WIDTH" => $ifile["WIDTH"], "HEIGHT" => $ifile["HEIGHT"], "DATA" => $ifile);
    }

    if (is_array($fid)) {
        return $ret;
    } else {
        return $ret[$fid];
    }
}

/**
 * Логирование в файл
 * @param $str
 * @param string $fileName
 */
function logToFile($str, $fileName = "") {
    if (empty($fileName)) {
        $f = fopen(P_LOG_FILE, "a");
    } else {
        $f = fopen(P_LOG_DIR . $fileName, "a");
    }
    fwrite($f, "[" . date("Y.m.d H:i:s") . "] " . $str . "\n");
    fclose($f);
}

/**
 * @param $msg
 */
function sibLog($msg) {
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/sib1.log", var_export($msg, true) . "\n", FILE_APPEND);
}

/**
 * Отладка
 * @param $value
 */
function debug($value) {
    while (ob_end_clean()) {
    }
    echo "<pre>";
    print_r($value);
    echo "</pre>";
    exit;
}

/**
 * @param $value
 */
function printr($value) {
    echo "<pre>";
    print_r($value);
    echo "</pre>";
}

/**
 * Возвращает строку, разделенную пробелами по 3 символа начиная с конца.
 * @param $str
 * @return mixed|string
 */
function priceFormat($str) {
    $str = number_format($str, 2, ',', ' ');
    $str = str_replace(' ', '&nbsp;', $str);
    return $str;
}

/**
 *  Возвращает название бренда по XmlId
 * @param XML_ID $xmlId
 * @return string
 */
function getBrandByXmlId($xmlId) {
    if (!$xmlId) return;

    $arFilter = array("IBLOCK_ID" => IB_BRANDS, "ACTIVE" => 'Y', "XML_ID" => $xmlId);
    $arSelect = array("NAME");
    $dbElement = CIBlockElement::GetList(array(), $arFilter, null, null, $arSelect);
    $arBrand = $dbElement->Fetch();
    return $arBrand['NAME'];
}

/**
 *  Возвращает название бренда по XmlId
 * @param XML_ID $xmlId
 * @return string
 */
function getArBrandByXmlId($xmlId) {
    if (!$xmlId) return;

    $arFilter = array("IBLOCK_ID" => IB_BRANDS, "ACTIVE" => 'Y', "XML_ID" => $xmlId);
    $arSelect = array("ID", "NAME", "CODE");
    $dbElement = CIBlockElement::GetList(array(), $arFilter, null, null, $arSelect);
    $arBrand = $dbElement->Fetch();
    return $arBrand;
}

/**
 *  Возвращает массив цветов по массиву идентификаторов XML_ID
 * @param array of XML_ID
 * @return array of color names
 */
function getColorsArray($arXmlId) {
    if (!count($arXmlId)) return;
    $arFilter = array("IBLOCK_ID" => IB_COLORS, "ACTIVE" => 'Y', "XML_ID" => $arXmlId);
    $arSelect = array("NAME");
    $dbElement = CIBlockElement::GetList(array(), $arFilter, null, null, $arSelect);
    $arColors = array();
    while ($row = $dbElement->Fetch()) {
        $arColors[] = $row['NAME'];
    }
    return $arColors;
}

/**
 * @param $order
 * @return string
 */
function sortOrderToggle($order) {
    return $order == 'asc' ? 'desc' : 'asc';
}

/**
 * @param $str
 * @return string
 */
function translitIt($str) {
    $tr = array("А" => "A", "Б" => "B", "В" => "V", "Г" => "G", "Д" => "D", "Е" => "E", "Ж" => "J", "З" => "Z", "И" => "I", "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N", "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T", "У" => "U", "Ф" => "F", "Х" => "H", "Ц" => "TS", "Ч" => "CH", "Ш" => "SH", "Щ" => "SCH", "Ъ" => "", "Ы" => "YI", "Ь" => "", "Э" => "E", "Ю" => "YU", "Я" => "YA", "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "j", "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y", "ы" => "yi", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya");
    return strtr($str, $tr);
}

/**
 * Функция, аналогичная CMain::GetCurPageParam, только умеет работать с любой переданной ссылкой и умеет удалять массивы параметров.
 */
function nfGetCurPageParam($strParam = '', $arParamKill = array(), $get_index_page = NULL, $uri = FALSE) {

    if (NULL === $get_index_page) {

        if (defined('BX_DISABLE_INDEX_PAGE')) $get_index_page = !BX_DISABLE_INDEX_PAGE; else
            $get_index_page = TRUE;

    }

    $sUrlPath = GetPagePath($uri, $get_index_page);
    $strNavQueryString = nfDeleteParam($arParamKill, $uri);

    if ($strNavQueryString != '' && $strParam != '') $strNavQueryString = '&' . $strNavQueryString;

    if ($strNavQueryString == '' && $strParam == '') return $sUrlPath; else
        return $sUrlPath . '?' . $strParam . $strNavQueryString;

}


/**
 * @param $arParam
 * @param bool $uri
 * @return mixed|string
 */
function nfDeleteParam($arParam, $uri = FALSE) {

    $get = array();
    if ($uri && ($qPos = strpos($uri, '?')) !== FALSE) {
        $queryString = substr($uri, $qPos + 1);
        parse_str($queryString, $get);
        unset($queryString);
    }

    if (sizeof($get) < 1) $get = $_GET;

    if (sizeof($get) < 1) return '';

    if (sizeof($arParam) > 0) {
        foreach ($arParam as $param) {
            $search = & $get;
            $param = (array)$param;
            $lastIndex = sizeof($param) - 1;

            foreach ($param as $c => $key) {
                if (array_key_exists($key, $search)) {
                    if ($c == $lastIndex) unset($search[$key]); else
                        $search = & $search[$key];
                }
            }
        }
    }

    return str_replace(array('%5B', '%5D'), array('[', ']'), http_build_query($get));

}

/**
 * Возвращает отформатированную строку с размером файла для загрузки
 *
 * @param int $size
 * @param int $round
 * @return float
 */
function GetStrFileSize($size, $round = 2) {
    $sizes = array('B', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb', 'Zb', 'Yb');
    for ($i = 0; $size > 1024 && $i < count($sizes) - 1; $i++) $size /= 1024;
    return round($size, $round) . " " . $sizes[$i];
}

/**
 * Возвращает отформатированную строку с типом файла для закгрузки
 *
 * @param string $fileName
 * @return string
 */
function GetStrFileType($fileName) {
    $type = explode(".", $fileName);
    $type = $type[1];
    switch ($type) {
        case "xls":
            return "xls";
            break;
        case "xlsx":
            return "xlsx";
            break;
        case "pdf":
            return "pdf";
            break;
        case "zip":
            return "zip";
            break;
        case "ppt":
            return "ppt";
            break;
        case "pptx":
            return "pptx";
            break;
        case "key":
            return "key";
            break;
        default:
            return "unknown";
    }
}

/**
 * Возвращает отформатированную строку с типом файла для закгрузки
 *
 * @param string $fileName
 * @return string
 */
function GetClassFileType($fileName) {
    $type = explode(".", $fileName);
    $type = $type[1];
    switch ($type) {
        case "xls":
            return "excel";
            break;
        case "xlsx":
            return "excel";
            break;
        case "pdf":
            return "pdf";
            break;
        case "zip":
            return "zip";
            break;
        case "ppt":
            return "powerpoint";
            break;
        case "pptx":
            return "powerpoint-x";
            break;
        case "key":
            return "key";
            break;
        default:
            return "unknown";
    }
}

/**
 * Функция отправки письма с аттачем
 *
 * @param string $event
 * @param string $lid
 * @param array $arFields
 * @param string $filePath
 * @return boolean
 */
function SendAttache($event, $lid, $arFields, $filePath) {
    global $DB;
    $lid = SS_SITE_ID;

    $event = $DB->ForSQL($event);
    $lid = $DB->ForSQL($lid);

    $rsMessTpl = $DB->Query("SELECT * FROM b_event_message WHERE EVENT_NAME='{$event}' AND LID='{$lid}';");
    while ($arMessTpl = $rsMessTpl->Fetch()) {
        // get charset
        $strSql = "SELECT CHARSET FROM b_lang WHERE LID='{$lid}' ORDER BY DEF DESC, SORT";
        $dbCharset = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
        $arCharset = $dbCharset->Fetch();
        $charset = $arCharset["CHARSET"];

        // additional params
        if (!isset($arFields["DEFAULT_EMAIL_FROM"])) {
            $arFields["DEFAULT_EMAIL_FROM"] = COption::GetOptionString("main", "email_from", "admin@" . $GLOBALS["SERVER_NAME"]);
        }
        if (!isset($arFields["SITE_NAME"])) {
            $arFields["SITE_NAME"] = COption::GetOptionString("main", "site_name", $GLOBALS["SERVER_NAME"]);
        }
        if (!isset($arFields["SERVER_NAME"])) {
            $arFields["SERVER_NAME"] = COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"]);
        }

        // replace
        $from = CAllEvent::ReplaceTemplate($arMessTpl["EMAIL_FROM"], $arFields);
        $to = CAllEvent::ReplaceTemplate($arMessTpl["EMAIL_TO"], $arFields);
        $message = CAllEvent::ReplaceTemplate($arMessTpl["MESSAGE"], $arFields);
        $subj = CAllEvent::ReplaceTemplate($arMessTpl["SUBJECT"], $arFields);
        $bcc = CAllEvent::ReplaceTemplate($arMessTpl["BCC"], $arFields);


        $from = trim($from, "\r\n");
        $to = trim($to, "\r\n");
        $subj = trim($subj, "\r\n");
        $bcc = trim($bcc, "\r\n");

        if (COption::GetOptionString("main", "convert_mail_header", "Y") == "Y") {
            $from = CAllEvent::EncodeMimeString($from, $charset);
            $to = CAllEvent::EncodeMimeString($to, $charset);
            $subj = CAllEvent::EncodeMimeString($subj, $charset);
        }

        $all_bcc = COption::GetOptionString("main", "all_bcc", "");
        if ($all_bcc != "") {
            $bcc .= (strlen($bcc) > 0 ? "," : "") . $all_bcc;
            $duplicate = "Y";
        } else {
            $duplicate = "N";
        }

        $strCFields = "";
        $cSearch = count($arSearch);
        foreach ($arSearch as $id => $key) {
            $strCFields .= substr($key, 1, strlen($key) - 2) . "=" . $arReplace[$id];
            if ($id < $cSearch - 1) $strCFields .= "&";
        }

        if (COption::GetOptionString("main", "CONVERT_UNIX_NEWLINE_2_WINDOWS", "N") == "Y") $message = str_replace("\n", "\r\n", $message);

        // read file(s)
        $arFiles = array();
        if (!is_array($filePath)) {
            $filePath = array($filePath);
        }
        foreach ($filePath as $fPath) {
            $arFiles[] = array("F_PATH" => $fPath, "F_LINK" => $f = fopen($fPath, "rb"));
        }

        $un = strtoupper(uniqid(time()));
        $eol = CAllEvent::GetMailEOL();
        $head = $body = "";

        // header
        $head .= "Mime-Version: 1.0" . $eol;
        $head .= "From: {$from}" . $eol;

        if (COption::GetOptionString("main", "fill_to_mail", "N") == "Y") {
            $header = "To: {$to}" . $eol;
        }

        $head .= "Reply-To: {$from}" . $eol;
        $head .= "X-Priority: 3 (Normal)" . $eol;
        $head .= "X-MID: {$messID}." . $arMessTpl["ID"] . "(" . date($DB->DateFormatToPHP(CLang::GetDateFormat("FULL"))) . ")" . $eol;
        $head .= "X-EVENT_NAME: ISALE_KEY_F_SEND" . $eol;
        if (strpos($bcc, "@") !== false) {
            $head .= "BCC: {$bcc}" . $eol;
        }
        $head .= "Content-Type: multipart/mixed; ";
        $head .= "boundary=\"----" . $un . "\"" . $eol . $eol;

        // body
        $body = "------" . $un . $eol;
        if ($arMessTpl["BODY_TYPE"] == "text") {
            $body .= "Content-Type:text/plain; charset=" . $charset . $eol;
        } else {
            $body .= "Content-Type:text/html; charset=" . $charset . $eol;
        }
        $body .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
        $body .= $message . $eol . $eol;

        foreach ($arFiles as $arF) {
            $body .= "------" . $un . $eol;
            $body .= "Content-Type: application/octet-stream; name=\"" . basename($arF["F_PATH"]) . "\"" . $eol;
            $body .= "Content-Disposition:attachment; filename=\"" . basename($arF["F_PATH"]) . "\"" . $eol;
            $body .= "Content-Transfer-Encoding: base64" . $eol . $eol;
            $body .= chunk_split(base64_encode(fread($arF["F_LINK"], filesize($arF["F_PATH"])))) . $eol . $eol;
        }
        $body .= "------" . $un . "--";

        // send
        if (!defined("ONLY_EMAIL") || $to == ONLY_EMAIL) {
            if (bxmail($to, $subj, $body, $head, COption::GetOptionString("main", "mail_additional_parameters", ""))) {
                return true;
            }
        }

        return false;
    } // while ($arMessTpl = $rsMessTpl->Fetch())
}

/**
 * @param $arColors
 * @return array
 * Возвращает массив названий цветов
 */
function getColors($arColors) {
    $res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => IB_COLORS, "ACTIVE" => "Y", "XML_ID" => $arColors), false, false, array("XML_ID", "NAME"));

    $arRet = array();
    while ($tmp = $res->Fetch()) {
        $arRet[$tmp['XML_ID']] = $tmp['NAME'];
    }

    return $arRet;
}

/**
 * @param $arFormats
 * @return array
 * Возвращает массив названий форматов
 */
function getFormats($arFormats) {
    $res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => IB_FORMAT2, "ACTIVE" => "Y", "XML_ID" => $arFormats), false, false, array("XML_ID", "NAME"));

    $arRet = array();
    while ($tmp = $res->Fetch()) {
        $arRet[$tmp['XML_ID']] = $tmp['NAME'];
    }

    return $arRet;
}

/**
 * @param $arMaterials
 * @return array
 * Возвращает массив материалов
 */
function getMaterials($arMaterials) {
    $res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => IB_MATERIALS, "ACTIVE" => "Y", "XML_ID" => $arMaterials), false, false, array("XML_ID", "NAME"));

    $arRet = array();
    while ($tmp = $res->Fetch()) {
        $arRet[$tmp['XML_ID']] = $tmp['NAME'];
    }

    return $arRet;
}

/**
 * @param $statusID
 * @return bool
 */
function getStatusDescription($statusID) {
    CModule::IncludeModule('iblock');
    $prop = null;

    if ($statusID == 'confirmed') {
        $prop = "PROPERTY_SD_CONFIRMED";
    } elseif ($statusID == 'collected') {
        $prop = "PROPERTY_SD_COLLECTED";
    } elseif ($statusID == 'closed') {
        $prop = "PROPERTY_SD_CLOSED";
    } else {
        $prop = "PROPERTY_SD_NEW";
    }

    $rsPyatnitsa = CIBlockElement::GetList(array(), array("IBLOCK_ID" => IB_EXCHANGE_SETTINGS, "ACTIVE" => "Y"), false, false, array("ID", $prop));
    $arPyatnitsa = $rsPyatnitsa->Fetch();

    return $arPyatnitsa[$prop . "_VALUE"] ? $arPyatnitsa[$prop . "_VALUE"]["TEXT"] : false;
}

/**
 * @return bool
 */
function isAjax() {
    return Zend_Controller_Front::getInstance()->getRequest()->isXmlHttpRequest();
}

/**
 * @param $string
 * @param $maxlen
 * @return string
 */
function cutString($string, $maxlen) {
    $len = (mb_strlen($string) > $maxlen) ? mb_strripos(mb_substr($string, 0, $maxlen), ' ') : $maxlen;
    $cutStr = mb_substr($string, 0, $len);
    return (mb_strlen($string) > $maxlen) ? $cutStr . '...' : $cutStr;
}

/**
 * @param $a
 * @param $b
 * @return bool
 */
function sort_compare($a, $b) {
    return $a['SORT'] > $b['SORT'];
}

/**
 * @param $a
 * @param $b
 * @return bool
 */
function sort_date_compare($a, $b) {
    return $a['DATE'] > $b['DATE'];
}

/**
 * @param $a
 * @param $b
 * @return int
 */
function color_compare($a, $b) {
    if ($a['SORT'] == $b['SORT']) {
        return 0;
    }

    return ($a['SORT'] < $b['SORT']) ? -1 : 1;
}

/**
 * @param $a
 * @param $b
 * @return int
 */
function name_compare($a, $b) {
    return strcmp($a['NAME'], $b['NAME']);
}


/**
 * @param $length
 * @param $width
 * @return mixed
 */
function getDisplayFormatName($length, $width) {
    $rsFormatNames = CIBlockElement::GetList(array(), array("IBLOCK_ID" => IB_FORMAT_NAME, "ACTIVE" => "Y", "PROPERTY_LENGTH" => $length, "PROPERTY_WIDTH" => $width), false, false, array("ID", "NAME"));
    $arFormatNames = $rsFormatNames->Fetch();
    return $arFormatNames["NAME"];
}

/**
 * Возвращает массив настройки сайта по ее символьному коду
 * @param  string $code
 * @param  int $iblockId
 * @return array
 */
function getSetting($code, $iblockId = IB_SETTINGS) {
    CModule::IncludeModule('iblock');

    $arSetting = CIBlockElement::GetList(
        array(),
        array(
            "IBLOCK_ID" => $iblockId,
            "ACTIVE" => "Y",
            "CODE" => $code
        ),
        false,
        false,
        array(
            "ID", "CODE", "ACTIVE_FROM", "SORT", "NAME", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE",
            "PREVIEW_PICTURE", "DETAIL_PICTURE", "DETAIL_TEXT", "DETAIL_TEXT_TYPE",
            "PROPERTY_FILE", "PROPERTY_TEXT"
        )
    )->Fetch();

    return $arSetting;
}

/**
 * @param $formCode
 * @param int $iblockId
 * @return array|bool|mixed
 * Возвращает настройки формы
 */
function getFormSettings($formCode, $iblockId = IB_FORM_SETTINGS) {
    CModule::IncludeModule('iblock');

    $arSetting = CIBlockElement::GetList(
        array(),
        array(
            "IBLOCK_ID" => $iblockId,
            "ACTIVE" => "Y",
            "ID" => $formCode
        ),
        false,
        false,
        array("ID", "PROPERTY_BUTTON_TEXT", "PROPERTY_FORM_TEXT", "PROPERTY_INPUT_LABEL_TEXT", "PROPERTY_FORM_HEADING")
    )->Fetch();

    return $arSetting;
}

/**
 * Возвращает строку, соответствующую настройке сайта
 * @param  string $code
 * @param  int $iblockId
 * @return string
 */
function getSettingText($code, $iblockId = IB_SETTINGS) {
    $arSetting = getSetting($code, $iblockId = IB_SETTINGS);
    return $arSetting["PROPERTY_TEXT_VALUE"];
}

/**
 * @return bool
 */
function isNoFixedHeader() {
    $settings = getSettingText('NO_FIXED_HEADER');
    $arSettings = explode(';', $settings);
    $url = $_SERVER['REQUEST_URI'];
    if (isMain()) return true;
    foreach ($arSettings as $path) {
        if (strpos($url, $path) !== false) return true;
    }
    return false;
}

/**
 * @param $str
 * @param string $enc
 * @return string
 */
function mb_ucfirst($str, $enc = 'utf-8') {
    return mb_strtoupper(mb_substr($str, 0, 1, $enc), $enc) . mb_substr($str, 1, mb_strlen($str, $enc), $enc);
}

/**
 * @return array
 */
function getAvailableOrdersFilter() {
    global $USER;
    $arUsers = getCurrentUserManagers();

    $arFilter = array("IBLOCK_ID" => IB_ORDERS, "ACTIVE" => "Y", "PROPERTY_USER_ID" => $arUsers, "!PROPERTY_MAIN_ORDER_ID" => false, "PROPERTY_COLLECTED" => false, "PROPERTY_CLOSED" => false, "PROPERTY_DELETED_ORDER" => false);

    require_once(P_LIBRARY . 'workCalendar/workCalendar.class.php');
    $calendar = new WorkCalendar();
    $calendar->addDaysToDate(-5);
    $arFilter[] = array("LOGIC" => "OR", array(">=DATE_ACTIVE_FROM" => ConvertDateTime($calendar->getDate(), "DD-MM-YYYY") . " 00:00:00"), array(">=DATE_CREATE" => ConvertDateTime($calendar->getDate(), "DD-MM-YYYY") . " 00:00:00"));

    return $arFilter;
}

/**
 * @return array
 */
function getCurrentUserManagers() {
    global $USER;
    return getUserManagers($USER->GetID());
}

/**
 * @param $id
 * @return array
 */
function getUserManagers($id) {
    global $USER;

    $groups = $USER->GetUserGroup($id);
    if (in_array(UG_COMPANY_ADMIN, $groups)) {
        $contractors = getUserContractors($id);
        return getContractorsManagers($contractors);
    } else {
        return array($id);
    }
}

/**
 * @param $id
 * @return mixed
 */
function getUserContractors($id) {
    $arUser = CUser::GetByID($id)->GetNext();
    return $arUser["UF_CONTRACTOR"];
}

/**
 * @param $contractors
 * @return array
 */
function getContractorsManagers($contractors) {
    $arFilter = array("ACTIVE" => "Y", "UF_CONTRACTOR" => $contractors);
    $arSelect = array("ID");

    $rsUsers = CUser::GetList(($by = "id"), ($order = "asc"), $arFilter, $arSelect);
    while ($arUser = $rsUsers->GetNext()) {
        $arUsers[] = $arUser["ID"];
    }

    return $arUsers;
}

/**
 * @param $xmlId
 * @param bool $onlyMainPicture
 * @return array
 */
function getProductImagesByXMLId($xmlId, $onlyMainPicture = false) {
    /* CFile::GetList - не принимает фильтры для подстроки, выбираем из базы напрямую */
    global $DB;
    $query = 'SELECT * FROM `b_file` WHERE `ORIGINAL_NAME` LIKE \'' . $xmlId . '%\' ORDER BY `ORIGINAL_NAME` ASC';
    if ($onlyMainPicture) {
        $query .= ' LIMIT 1';
    }
    $rsImages = $DB->Query($query);
    $arImages = array();
    while ($arImage = $rsImages->Fetch()) {
        $arImages[] = $arImage;
    }

    return $onlyMainPicture ? reset($arImages) : $arImages;
}

/**
 * @param $number
 * @return bool
 * Возвращает уникальный номер запроса по номеру 1С резерва
 */
function getMainOrderIdByNumber($number) {
    $arFilter = array("IBLOCK_ID" => IB_ORDERS, "ACTIVE" => "Y", "PROPERTY_NUMBER" => '%' . $number . '%', "!PROPERTY_MAIN_ORDER_ID" => false);
    $rs = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "PROPERTY_MAIN_ORDER_ID"));
    $arOrders = array();
    while ($tmp = $rs->GetNext()) {
        $arOrders[] = $tmp['PROPERTY_MAIN_ORDER_ID_VALUE'];
    }

    return count($arOrders) ? $arOrders : false;
}

/**
 * @param $number
 * @return array
 * Возвращает уникальный номер запроса по его 1С номеру
 */
function getOrderIdByNumber($number) {
    $arFilter = array("IBLOCK_ID" => IB_ORDERS, "ACTIVE" => "Y", "PROPERTY_NUMBER" => '%' . $number . '%', "PROPERTY_MAIN_ORDER_ID" => false);
    $rs = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "PROPERTY_ORDER_ID"));
    $arOrders = array();
    while ($tmp = $rs->GetNext()) {
        $arOrders[] = $tmp['PROPERTY_ORDER_ID_VALUE'];
    }

    return $arOrders;
}

/**
 * @param $reserveFilter
 * @return array
 * Функция возвращает массив id запросов, фильтрованных по параметрам их резервов
 */
function getMainOrderIds($reserveFilter) {
    $arOrders = array();
    switch ($reserveFilter) {
        /**
         * Выбор всех запросов, у которых есть резервы
         */
        case "noReserves":
        case "reserves":
            $arFilter = array("IBLOCK_ID" => IB_ORDERS, "ACTIVE" => "Y", "!PROPERTY_MAIN_ORDER_ID" => false);
            $rs = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "PROPERTY_MAIN_ORDER_ID"));
            while ($tmp = $rs->GetNext()) {
                $arOrders[] = $tmp['PROPERTY_MAIN_ORDER_ID_VALUE'];
            }
            break;

        /**
         * Выбор всех запросов, у резервов которых статусы "собран", "подтвержден"
         */
        case "inProgress":
            $arFilter = array("IBLOCK_ID" => IB_ORDERS, "ACTIVE" => "Y", "!PROPERTY_MAIN_ORDER_ID" => false, array("LOGIC" => "OR", "PROPERTY_CONFIRMED" => CONFIRMED_OK_VALUE, "PROPERTY_COLLECTED" => COLLECTED_OK_VALUE), "PROPERTY_CLOSED" => false);
            $rs = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "PROPERTY_MAIN_ORDER_ID"));
            while ($tmp = $rs->GetNext()) {
                $arOrders[] = $tmp['PROPERTY_MAIN_ORDER_ID_VALUE'];
            }
            break;

        /**
         * Выбор всех запросов, у резервов которых статусы "выполнен"
         */
        case "archive":
            $arFilter = array("IBLOCK_ID" => IB_ORDERS, "ACTIVE" => "Y", "!PROPERTY_MAIN_ORDER_ID" => false, "PROPERTY_CLOSED" => CLOSED_OK_VALUE);
            $rs = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "PROPERTY_MAIN_ORDER_ID"));
            while ($tmp = $rs->GetNext()) {
                $arOrders[] = $tmp['PROPERTY_MAIN_ORDER_ID_VALUE'];
            }
            break;

    }
    return $arOrders;
}

/**
 * @return int
 * Возвращает кол-во позиций в корзине
 */
function getBasketProductsCount() {
    $dbBasketItems = CSaleBasket::GetList(array(), array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "LID" => SITE_ID, "ORDER_ID" => "NULL"), false, false, array());
    $i = 0;
    while ($tmp = $dbBasketItems->GetNext()) {
        $i++;
    }
    return $i;
}

/**
 * @param $products
 * @return int
 * Возвращает кол-во товаров из массива, которые есть в корзине
 */
function basketProductsCountFromArray($products) {
    $dbBasketItems = CSaleBasket::GetList(array(), array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "LID" => SITE_ID, "ORDER_ID" => "NULL", "PRODUCT_ID" => $products), false, false, array());
    $i = 0;
    while ($tmp = $dbBasketItems->Fetch()) {
        $i++;
    }
    return $i;
}

/**
 * @param $products
 * @return array
 * Возвращает массив id товаров из массива, которые есть в корзине
 */
function basketProductsIdsFromArray($products) {
    $dbBasketItems = CSaleBasket::GetList(array(), array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "LID" => SITE_ID, "ORDER_ID" => "NULL", "PRODUCT_ID" => $products), false, false, array("PRODUCT_ID"));

    $arRet = array();
    while ($tmp = $dbBasketItems->Fetch()) {
        $arRet[] = $tmp['PRODUCT_ID'];
    }
    return $arRet;
}

function getResizedFileData($fid, $size = array(), $flag = BX_RESIZE_IMAGE_PROPORTIONAL) {
    if (!isset($fid)) {
        return;
    }

    if (is_array($fid)) {
        $rsFile = CFile::GetList(array(), array("@ID" => implode(",", $fid)));
    } else {
        $rsFile = CFile::GetByID($fid);
    }

    $ret = array();

    while ($ifile = $rsFile->Fetch()) {
        if ($size) {
            $ret[$ifile['ID']] = CFile::ResizeImageGet($ifile['ID'], $size, $flag);
            $ret[$ifile['ID']]['SRC'] = $ret[$ifile['ID']]['src'];
        } else {
            $ret[$ifile['ID']] = array("SRC" => P_UPLOAD . $ifile["SUBDIR"] . "/" . $ifile['FILE_NAME'], "WIDTH" => $ifile["WIDTH"], "HEIGHT" => $ifile["HEIGHT"], "DATA" => $ifile);
        }

    }

    if (is_array($fid)) {
        return $ret;
    } else {
        return $ret[$fid];
    }
}

function is3DModelExist($code) {
    if (!file_exists(P_DR . P_UPLOAD_3D_MODEL . '/' . $code . '.html')) {
        return false;
    }
    return true;
}

function get3DModelLink($code) {
    return P_UPLOAD_3D_MODEL . '/' . $code . '.html';
}

function textSwitch($text, $from, $to) {
    if ($from == "ru" && $to == "en") {
        $strSearch = array("й", "ц", "у", "к", "е", "н", "г", "ш", "щ", "з", "х", "ъ", "ф", "ы", "в", "а", "п", "р", "о", "л", "д", "ж", "э", "я", "ч", "с", "м", "и", "т", "ь", "б", "ю");
        $strReplace = array("q", "w", "e", "r", "t", "y", "u", "i", "o", "p", "[", "]", "a", "s", "d", "f", "g", "h", "j", "k", "l", ";", "'", "z", "x", "c", "v", "b", "n", "m", ",", ".");
    } else {
        $strSearch = array("q", "w", "e", "r", "t", "y", "u", "i", "o", "p", "[", "]", "a", "s", "d", "f", "g", "h", "j", "k", "l", ";", "'", "z", "x", "c", "v", "b", "n", "m", ",", ".");
        $strReplace = array("й", "ц", "у", "к", "е", "н", "г", "ш", "щ", "з", "х", "ъ", "ф", "ы", "в", "а", "п", "р", "о", "л", "д", "ж", "э", "я", "ч", "с", "м", "и", "т", "ь", "б", "ю");
    }
    return str_replace($strSearch, $strReplace, $text);
}

function stringIsEn($text) {
    $text = mb_strtolower($text);
    $strSearch = array("q", "w", "e", "r", "t", "y", "u", "i", "o", "p", "[", "]", "a", "s", "d", "f", "g", "h", "j", "k", "l", ";", "'", "z", "x", "c", "v", "b", "n", "m", ",", ".");
    foreach (str_split($text) as $symbol) {
        if (in_array($symbol, $strSearch)) {
            return true;
        }
    }
    return false;
}