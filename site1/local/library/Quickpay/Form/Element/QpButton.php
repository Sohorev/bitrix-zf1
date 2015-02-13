<?php

/**
 *
 * @author sohorev
 */
class Quickpay_Form_Element_QpButton extends Zend_Form_Element_Button {

    /**
     * Load default decorators
     *
     * @return Zend_Form_Element
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $this->addPrefixPath('Quickpay_Form_Decorator_', 'Quickpay/Form/Decorator/', 'decorator');

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('ViewHelper')
                ->addDecorator('HtmlTag', array(
                    'tag' => 'div',
                    'class' => 'submitButton',
                    'id'  => array('callback' => array(get_class($this), 'resolveElementId'))
                ))
                ->addDecorator('ElementWrapper')
                ->addDecorator('Clear', array());
        }
        return $this;
    }
}
