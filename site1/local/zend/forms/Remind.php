<?
/**
 * Class Quickpay_Form_Remind
 */
class Quickpay_Form_Remind extends Quickpay_Form {
    /**
     *
     */
    public function init() {
        $this->setAction(Zend_View_Helper_Url::url(array(), 'passwordRemind'));
        $this->setMethod('post');

        $email = new Zend_Form_Element_Text('email', array(
            'required'     => true,
            'label'        => 'Email',
            'filters'      => array(
                array('StringTrim')
            ),
            'validators'  => array('EmailAddress'),
        ));

        $send = new Zend_Form_Element_Submit('send_auth', array(
            'class' => 'button-link',
            'label' => 'Send'
        ));

        $this->addElement($email)
            ->addElement($send);
    }
}
