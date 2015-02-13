<?php

/**
 * Функционал form контроллера вынесен в трайт для повторного использования в других иерархиях
 *
 * @author sohorev
 */
trait Quickpay_Controller_FormControllerTrait {

    protected $_object = null;

    protected $_successUrl = '';

    protected $_currentUrl = '';

    protected function assignFormToView() {

        $this->view->form = $this->getForm();
        $this->view->object = $this->getObject();
    }

    protected function onSubmit() {
        $this->getObject()->setfromArray($this->getForm()->getValues());
        $this->getObject()->save();
    }

    public function indexAction() {

        if ($this->_hasParam('cancel') || $this->hasParam('cancel_double')) {
            $this->_redirect($this->getSuccessUrl());
        }

        try {
            $isValid = true;
            if ($this->getRequest()->isPost()) {
                if ($this->getForm()->isValid($this->_getAllParams())) {
                    $this->onSubmit();

//                    if ($this->_hasParam('save_and_back') || $this->hasParam('save_and_back_double')) {
                        $this->_redirect($this->getSuccessUrl());
//                    }

//                    $this->_redirect($this->getCurrentUrl());
                } else {

                    $isValid = false;
                }
            } else {

                $this->populate();
            }
        } catch(Exception $e) {
            $this->getForm()->addErrorMessage($e->getMessage());
        }

        $this->view->isValid = $isValid;
    }

    public function populate() {

        if ($this->getObject()) {

            $this->getForm()->populate($this->getObject()->toArray());
        }

        if ($this->getObject() === null || $this->getObject()->id === null) {
            $this->getForm()->populate((array)$this->_getParam('__default'));
        }
    }

    public function getObject() {
        return $this->_object;
    }

    /**
     *
     * @return QP_Form_QPForm
     */
    public function getForm() {
        return $this->_form;
    }

    public function getSuccessUrl() {
        return $this->_successUrl;
    }

    public function getCurrentUrl() {
        return $this->_currentUrl;
    }

    public function setForm($form) {
        $this->_form = $form;
    }

    public function setSuccessUrl($url) {
        $this->_successUrl = $url;
    }

    public function setCurrentUrl($url) {
        $this->_currentUrl = $url;
    }

    public function setObject($object) {
        $this->_object = $object;
    }
}
