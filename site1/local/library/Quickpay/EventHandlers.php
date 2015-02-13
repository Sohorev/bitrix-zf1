<?
/**
 * Class Quickpay_EventHandlers
 */
class Quickpay_EventHandlers {

    /**
     * Инициализировать базовые события
     */
    static function init() {
        // Старт зенда (закомменчен т.к. не нужен пока что)
        AddEventHandler("main", "OnBeforeProlog", array("Quickpay_ZendManager", "Bootstrap"));
        // Обработка 404 ([х.з. зачем, но зато требует папку layout c симлинками на header и footer)
//        AddEventHandler("main", "OnEpilog", array("Quickpay_EventHandlers", "Redirect404"));
    }

    static function Redirect404() {
        if (
            !defined('ADMIN_SECTION') &&
            (defined("ERROR_404") || (function_exists("http_response_code") && http_response_code() == 404)) &&
            file_exists($_SERVER["DOCUMENT_ROOT"] . "/404.php")
        ) {
            global $APPLICATION;
            $APPLICATION->RestartBuffer();
            define("ERROR_404", "Y");

            include($_SERVER["DOCUMENT_ROOT"] . P_LAYOUT . "header.php");
            include($_SERVER["DOCUMENT_ROOT"] . "/404.php");
            include($_SERVER["DOCUMENT_ROOT"] . P_LAYOUT . "footer.php");
        }
    }
}
