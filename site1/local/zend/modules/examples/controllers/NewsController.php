<?php
/**
 * Class Examples_NewsController
 * @property Quickpay_Controller_Response_HttpBitrix $_response
 **/
class Examples_NewsController extends Quickpay_Controller_Abstract {

    /**
     * @return bool
     * @throws Zend_Exception
     */
    public function indexAction() {
        $modelNews = new Examples_Model_News();
        $result = $modelNews->getList();
        if (!is_array($result)) {
            throw new Zend_Exception('This page does not exist', 404);
        }

        $this->view->headTitle('Список новостей');
        $this->view->newsList = $result['items'];
        $this->view->pager    = $result['pager'];

        return true;
    }

    /**
     * @throws Zend_Exception
     * @return bool
     */
    public function detailAction() {

        $modelNews = new Examples_Model_News();
        $elementId = $this->_getParam('elementId', false);
        if (!$elementId) {
            throw new Zend_Exception('This page does not exist', 404);
        }

        $result = $modelNews->getDetail(array('ID' => $elementId));
        if (!is_array($result) || !isset($result['element']) || !is_array($result['element'])) {
            throw new Zend_Exception('This page does not exist', 404);
        }

        $this->view->headTitle($result['element']['NAME']);
        $this->view->element = $result['element'];
        $this->view->pager   = $result['pager'];
        return true;
    }
}
