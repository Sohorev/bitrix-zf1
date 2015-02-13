<?
/**
 * Class Quickpay_Form_ChangePassword
 */
class Quickpay_Form_ChangePassword extends Quickpay_Form {

    /**
     *
     */
    public function init() {
        $this->setAction(Zend_View_Helper_Url::url(array(), 'changePassword', true))
            ->setMethod('post');

        $checkword = new Zend_Form_Element_Hidden('USER_CHECKWORD');
        $checkword->setValue(Zend_Controller_Front::getInstance()->getRequest()->getParam('USER_CHECKWORD'))
            ->clearDecorators();

        $login = new Zend_Form_Element_Hidden('USER_LOGIN');
        $login->setValue(Zend_Controller_Front::getInstance()->getRequest()->getParam('USER_LOGIN'))
            ->clearDecorators();

        $password = new Quickpay_Form_Element_Bitrix_InputPassword('new_password', array(
            'required'     => true,
            'label'        => 'New Password'
        ));

        $confirmPassword = new Zend_Form_Element_Password('new_password_confirm', array(
            'required'     => true,
            'label'        => 'Confirm Password',
            'validators'   => array(
                array('Identical', false, array('token' => 'new_password'))
            )
        ));

        $send = new Zend_Form_Element_Submit('send_auth', array(
            'label' => 'Enter'
        ));

        $this->addElement($login)
            ->addElement($checkword)
            ->addElement($password)
            ->addElement($confirmPassword)
            ->addElement($send);
    }
}
