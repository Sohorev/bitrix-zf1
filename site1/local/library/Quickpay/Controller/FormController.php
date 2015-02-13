<?php

/**
 *
 * @author sohorev
 */
class Quickpay_Controller_FormController extends Quickpay_Controller_Abstract {

    use Quickpay_Controller_FormControllerTrait;

    /**
     *
     * @var Zend_Form
     */
    protected $_form = null;

    public function  preDispatch() {
        parent::preDispatch();
        $this->assignFormToView();
    }
}
