<?php
/**
 * Class Quickpay_Controller_Action_Helper_QuickpayAjaxContext
 */
class Quickpay_Controller_Action_Helper_QuickpayAjaxContext extends Zend_Controller_Action_Helper_AjaxContext {

    /**
     * JSON post processing
     * JSON serialize view variables to response body
     * @return void
     * @throws Zend_Controller_Action_Exception
     */
    public function postJsonContext() {
        if (!$this->getAutoJsonSerialization()) {
            return;
        }

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;
        if ($view instanceof Zend_View_Interface) {
            /**
             * @see Zend_Json
             */
            if(method_exists($view, 'getVars')) {
                require_once 'Zend/Json.php';

                $_vars = $view->getVars();
                foreach ($_vars as $_varKey => $_var) {
                    if (is_object($_var) && method_exists($_var, 'toJson')) {
                        $_vars[$_varKey] = $_var->toJson();
                    }
                }

                $vars = Zend_Json::encode($_vars);

                $this->getResponse()->setBody($vars);
            } else {
                require_once 'Zend/Controller/Action/Exception.php';
                throw new Zend_Controller_Action_Exception('View does not implement the getVars() method needed to encode the view into JSON');
            }
        }
    }
}
