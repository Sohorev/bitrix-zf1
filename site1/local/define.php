<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Здесь лежат все константы для путей к файлам
define("P_APP",       "/local/");
define("P_CSS",       P_APP . "css/");
define("P_JS",        P_APP . "js/");
define("P_IMAGES",    P_APP . "images/");
define("P_TEMPLATES", P_APP . "templates/");
define("P_LAYOUT",    P_APP . "layout/");
define("P_PICTURES",  P_APP . "pictures/");

define("P_AJAX",      P_APP . "ajax/");
define("P_UPLOAD",    "/" . COption::GetOptionString("main", "upload_dir", "upload") . "/");

define("P_DR",        $_SERVER["DOCUMENT_ROOT"]);
define("P_APP_PATH",  P_DR . P_APP);

define("P_INCLUDES",  P_APP_PATH . "includes/");
define("P_LIBRARY",   P_APP_PATH . "library/");

define("P_LOG_DIR",   P_APP_PATH . "logs/");
define("P_LOG_FILE",  P_LOG_DIR . "app.log");

define("SS_SITE_ID",  "s1");

// Define application environment
$environment = getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production';
defined('APPLICATION_ENV') || define('APPLICATION_ENV', $environment);

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(P_LIBRARY),
    get_include_path(),
)));

require_once(P_LIBRARY . 'Quickpay/EventHandlers.php');
require_once(P_LIBRARY . 'Quickpay/ZendManager.php');
Quickpay_EventHandlers::init();

// Логирование изменений
if (defined('APPLICATION_ENV') && APPLICATION_ENV === 'development') {
    require_once(P_LIBRARY . 'ChangeLogger/class.ChangeLogger.php');
    ChangeLogger::getInstance();
}

// Сжиматель админки
if (defined('ADMIN_SECTION') && ADMIN_SECTION) {
    $APPLICATION->SetAdditionalCSS(P_CSS . 'admin/admin-small.css');
}

// Константы - ID инфоблоков
define('IB_NEWS',     10);   // НОВОСТИ
