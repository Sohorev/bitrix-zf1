<?

/**
 * Class Quickpay_ZendManager
 */
class Quickpay_ZendManager {
    /**
     *
     */
    protected static $_zv;

    /**
     *
     */
    public static function Bootstrap() {
        // Define path to application directory
        defined('APPLICATION_PATH') || define('APPLICATION_PATH', P_APP_PATH . 'zend');

        if (!defined("APPLICATION_ENV")) {
            print_r("define APPLICATION_ENV in ZendManager!");
            die();
        }

        try {
            // Zend_Application
            require_once 'Zend/Application.php';

            // Create application, bootstrap, and run
            $application = new Zend_Application(
                APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini'
            );

            $application->bootstrap()->run();
//            $req = Zend_Controller_Front::getInstance()->getRequest();
        } catch (Exception $exc) {
            echo("<pre>");
            echo $exc->getTraceAsString();
            echo("</pre>");
            die();
        }
    }

    /**
     * call zend view helper
     */
    public static function __callStatic($_name, $_param) {
        $view = self::_getZendView();

        try {
            $helper = $view->getHelper($_name);
        } catch (Exception $e) {
            echo "<div style='color: red; font-weight: bold;'>" . $e->getMessage() . "</div>";
            return false;
        }

        // call the helper method
        return call_user_func_array(
            array($helper, $_name), $_param
        );
    }

    /**
     * get and store zend view instance
     */
    private static function _getZendView() {
        if (self::$_zv)
            return self::$_zv;

        self::$_zv = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
        return self::$_zv;
    }
}
