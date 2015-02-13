<?
/**
 * Class Quickpay_Form_Auth
 */
class Quickpay_Form_Auth extends Quickpay_Form {

    /**
     *
     */
    public function init() {
        parent::init();

        $this->setAction($this->getView()->url(array(), 'userAuthProcess'));
        $this->setMethod('post');

        $login = new Zend_Form_Element_Text('login', array(
            'required'     => true,
            'label'        => 'Username or Email',
            'filters'      => array(
                array('StringTrim')
            ),
            'validators'  => array(
                array('NotEmpty', false, array('type' => Zend_Validate_NotEmpty::STRING))
            )
        ));

        $password = new Zend_Form_Element_Password('password', array(
            'required'     => true,
            'label'        => 'Password',
            'filters'      => array(
                array('StringTrim')
            ),
            'validators'  => array(
                array('NotEmpty', false, array('type' => Zend_Validate_NotEmpty::STRING))
            )
        ));

        $note = new Zend_Form_Element_Note('reminder', array(
            'value'        => '<a href="' . $this->getView()->url(array(), 'passwordRemind') . '">'.$this->getTranslator()->translate('Forgot your password?').'</a>'
        ));

        // submit button
        $send = new Zend_Form_Element_Submit('send_auth', array(
            'label' => 'Login',
        ));

        $this->addElement($login)
            ->addElement($password)
            ->addElement($note)
            ->addElement($send);
    }
}
