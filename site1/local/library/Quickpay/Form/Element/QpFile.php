<?php

/**
 *
 * @author sohorev
 */
class Quickpay_Form_Element_QpFile extends Zend_Form_Element_File {

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
            	->addDecorator('File')
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
		        ->addDecorator('ElementWrapper')
		        ->addDecorator('Clear', array())
                ;
        }
        return $this;
    }
}
