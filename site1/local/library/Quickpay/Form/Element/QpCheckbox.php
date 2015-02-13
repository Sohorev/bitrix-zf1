<?php

/**
 * Свой чекбокс, для того, чтобы рисовать звездочку для необходимых элементов
 *
 * @author sohorev
 */
class Quickpay_Form_Element_QpCheckbox extends Zend_Form_Element_Checkbox {

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
            $this
                ->addDecorator('ViewHelper')
                ->addDecorator('Errors')
                ->addDecorator('HtmlTag', array(
                    'tag' => 'div',
                    'class' => 'checkboxElement',
                    'id'  => array('callback' => array(get_class($this), 'resolveElementId'))
                ))
                ->addDecorator('Label', array(
                    'tag' => 'div',
                    'tagClass' => 'checkboxLabel',
                    'requiredSuffix' => '&nbsp;<span class="asterisk">*</span>',
                    'escape' => false,
                    "placement" => Zend_Form_Decorator_Abstract::APPEND
                ))
                ->addDecorator('Description', array(
                    'tag' => 'p',
                    "placement" => Zend_Form_Decorator_Abstract::APPEND,
                    'class' => 'inputDescription'
                ))
                ->addDecorator('ElementWrapper')
                ->addDecorator('Clear', array())
                ;
        }
        return $this;
    }
}
