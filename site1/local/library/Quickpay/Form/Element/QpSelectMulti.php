<?php

class Quickpay_Form_Element_QpSelectMulti extends Zend_Form_Element_Multiselect {

    /**
     * @var Zend_Acl
     */
    protected $_acl;

    public function setOptions(array $options) {
        if (isset($options['acl'])) {
            $this->setAcl($options['acl']);
            unset($options['acl']);
        }
        parent::setOptions($options);
    }

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
                ->addDecorator('ElementWrapper')
                ->addDecorator('Clear', array());
        }
        return $this;
    }

    public function getAcl() {
        return $this->_acl;
    }

    public function setAcl($acl) {
        $this->_acl = $acl;
    }
}
