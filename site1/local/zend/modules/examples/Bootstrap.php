<?php

/**
 * Class Examples_Bootstrap
 * Бутстрап для модуля примеров кода
 */
class Examples_Bootstrap extends Zend_Application_Module_Bootstrap {

    /**
     * Регистрация путей для хелперов, объявленных в этом модуле
     */
    public function _initPaths() {
        $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;

        $view->addHelperPath(APPLICATION_PATH . '/modules/examples/views/helpers', 'Zend_View_Helper');
        $view->addScriptPath(APPLICATION_PATH . '/modules/examples/views/scripts');
    }

    /**
     * Загрузка и инициализация роутов
     */
    public function _initRoutes() {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/modules/examples/configs/routes.ini', APPLICATION_ENV);
        Zend_Controller_Front::getInstance()->getRouter()->addConfig($config, 'routes');
    }

    public function _initExampleNavigation() {
        $pages = array(
            array(
                'label' => 'Examples',
                'title' => 'Examples module info',
                'route' => 'examples',
                'pages' => array(
                    array(
                        'label' => 'News',
                        'title' => 'News list',
                        'route' => 'news'
                    ),
                    array(
                        'label' => 'Feedback webform',
                        'title' => 'Feedback results',
                        'route' => 'feedback'
                    ),
                    array(
                        'label' => 'Auth user ajax',
                        'title' => 'Auth user ajax',
                        'route' => 'userAuthAjax'
                    )
                )
            ),
        );

        $container = new Zend_Navigation($pages);
        Zend_Registry::set('Zend_Navigation', $container);
    }
}
