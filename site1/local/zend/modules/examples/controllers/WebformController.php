<?php
/**
 * Class Examples_WebformController
 * @property Quickpay_Controller_Response_HttpBitrix $_response
 **/
class Examples_WebformController extends Quickpay_Controller_FormController {

    protected $_successUrl = "/examples/webform/success";

    public function preDispatch() {
        $this->_form = new Examples_Form_Feedback([
            "webFormId" => 2,
            "labelToPlaceholder" => true
        ]);
        parent::preDispatch();
    }

    public function successAction() {
        $this->view->resultId = $this->getParam("result");
    }

    protected function onSubmit() {

        $this->resultId = $this->_form->getModel()->addResult($this->_form->getValues());
    }

    public function getSuccessUrl() {
        return $this->_successUrl . "/result/" . $this->resultId;
    }
}
