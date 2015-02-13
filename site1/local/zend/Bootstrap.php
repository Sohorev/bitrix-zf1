<?php
/**
 * Class Bootstrap
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    /**
     * Сохранение переменных Битрикса в реестр
     */
    public function _initBitrix() {
        Zend_Registry::set('BX_APPLICATION', $GLOBALS['APPLICATION']);
        Zend_Registry::set('BX_USER', $GLOBALS['USER']);

        Zend_Controller_Front::getInstance()->setResponse('Quickpay_Controller_Response_HttpBitrix');

        //$this->_useBitrixDbConn();
    }

    /**
     * hack for support bitrix session
     */
    public function _initSession() {
        $sessionId = Zend_Session::$_unitTestEnabled;
        Zend_Session::$_unitTestEnabled = true;
        Zend_Session::start();
        Zend_Session::$_unitTestEnabled = $sessionId;
    }

    /**
     * Загрузка и инициализация роутов
     */
    public function _initRoutes() {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routes.ini', APPLICATION_ENV);
        Zend_Controller_Front::getInstance()->getRouter()->addConfig($config, 'routes');
    }

    /**
     * Инициализация транслятора
     */
    public function _initTranslate() {
        $translator = new Zend_Translate('Ini', APPLICATION_PATH . '/configs/translate.ini', 'ru_RU');
        $translator->getAdapter()->setLocale(new Zend_Locale('ru_RU'));
        Zend_Validate_Abstract::setDefaultTranslator($translator);
    }

    /**
     * Инициализация обработчиков событий битрикса
     */
    public function _initCallbacks() {
        // Здесь вешать AddEventHandler для обработки в Zend. Пример:
        // AddEventHandler('iblock', 'OnIBlockPropertyBuildList', array('Quickpay_Model_PropertyFactory', 'init'));
        AddEventHandler("form", "onFormValidatorBuildList", array("Quickpay_Validate_Bitrix_Webform_EmailAddress", "getDescription"));
        AddEventHandler("form", "onFormValidatorBuildList", array("Quickpay_Validate_Bitrix_Webform_Regex", "getDescription"));
    }

    /**
     * Пробрасывание коннекшна к БД из Битрикса
     */
    private function _useBitrixDbConn() {

        print_r("Change Whitewashing ti zend DB");
        die();
        $bxDb = $GLOBALS['DB'];
        include_once 'Whitewashing/Db/Adapter/Mysql.php';
        $options = array(
            'adapter'               => 'Mysql',
            'isDefaultTableAdapter' => true,
            'params'                => array(
                'adapterNamespace' => 'Whitewashing_Db_Adapter',
                'host'             => $bxDb->DBHost,
                'username'         => $bxDb->DBLogin,
                'password'         => $bxDb->DBPassword,
                'dbname'           => $bxDb->DBName,
                'charset'          => 'utf8'
            )
        );

        $this->_loadPluginResource('db', $options);
        $this->getPluginResource('db')
            ->getDbAdapter()
            ->setConnectionResource($bxDb->db_Conn);
    }
}
