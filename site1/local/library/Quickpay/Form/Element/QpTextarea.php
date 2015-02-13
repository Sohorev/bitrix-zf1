<?php

/**
 * Свой текстовый элемент, для того, чтобы рисовать звездочку для необходимых элементов
 *
 * @author sohorev
 */
class Quickpay_Form_Element_QpTextarea extends Zend_Form_Element_Textarea {

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
                ->addDecorator('Description', array(
                    'tag' => 'div',
                    'class' => 'inputDescription'
                ))
                ->addDecorator('Errors')
                ->addDecorator('HtmlTag', array(
                    'tag' => 'div',
                    'class' => 'inputElement',
                    'id'  => array('callback' => array(get_class($this), 'resolveElementId'))
                ))
                ->addDecorator('Label', array(
                    'tag' => 'div',
                    'tagClass' => 'inputLabel',
                    'requiredSuffix' => '&nbsp;<span class="asterisk">*</span>',
                    'escape' => false
                ))
                ->addDecorator('Clear', array());
        }
        if (isset($this->removeLabelDecorator) && $this->removeLabelDecorator) {
            $this->removeDecorator("Label");
        }
        return $this;
    }
}
