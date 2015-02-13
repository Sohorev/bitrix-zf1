<?php
/**
 * @author sohorev
 */
class Quickpay_Form_Decorator_ElementWrapper extends Zend_Form_Decorator_Abstract {

    public function render($content) {

        $elementName = $this->getElement()->getName();
        return '<div id="' . $elementName . '-wrapper" class="elementWrap">' . $content . '</div>';
    }
}
