<?php
/**
 * Class Quickpay_Form_Element_Bitrix_InputPassword
 */
class Quickpay_Form_Element_Bitrix_InputPassword extends Zend_Form_Element_Password {

    /**
     *
     */
    public function init() {
        $this->addValidator(new Quickpay_Validate_Bitrix_Password());
        $this->addFilter('StringTrim');
    }
}
