<?php
/**
 * @author sohorev
 */
class Quickpay_Form_Decorator_CommonFormErrors extends Zend_Form_Decorator_Abstract {

    /**
     * Default placement: surround content
     * @var string
     */
    protected $_placement = null;

    /**
     * @param  string $content
     * @return string
     */
    public function render($content) {

        $element  = $this->getElement();
        $messages = $element->getErrorMessages();
        if (empty($messages)) {
            return $content;
        }
        return $element->getView()->formErrors($messages) . $content;
    }
}
