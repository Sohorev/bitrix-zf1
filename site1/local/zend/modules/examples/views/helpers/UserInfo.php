<?
/**
 * Class Zend_View_Helper_UserInfo
 */
class Zend_View_Helper_UserInfo extends Zend_View_Helper_Abstract {

    /**
     * @return string
     */
    public function userInfo() {
        $model = new Quickpay_Model_User();
        $this->view->user = $model->findById(1);
        return $this->view->render('blocks/userinfo.phtml');
    }
}
