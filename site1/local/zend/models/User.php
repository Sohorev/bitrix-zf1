<?
/**
 * Class Quickpay_Model_User
 */
class Quickpay_Model_User extends Quickpay_Model_Bitrix {

    /**
     * @var CUser
     */
    protected $_bx_user;

    /**
     * @var array
     */
    private $_login_errors;

    /**
     * @return mixed|void
     */
    public function init() {
        $this->_bx_user = Zend_Registry::get('BX_USER');
    }

    /**
     * @param $login
     * @param $password
     * @return bool|array
     */
    public function login($login, $password) {
        $errors = $this->_bx_user->Login($login, $password);
        if (!is_array($errors)) {
            return true;
        }

        $filter = new Zend_Filter_StripTags();
        return array($errors['ERROR_TYPE'] => $filter->filter($errors['MESSAGE']));
    }

    /**
     * @param $email
     * @param $password
     * @return bool|array
     */
    public function loginByEmail($email, $password) {
        if (!check_email($email)) {
            return $this->login($email, $password);
        }

        $filter = array(
            "ACTIVE" => "Y",
            "EMAIL"  => $email
        );

        $fields = array("LOGIN");

        $rsUsers = $this->_bx_user->GetList($by, $order, $filter, array("FIELDS" => $fields));
        if ($arUser = $rsUsers->Fetch()) {
            $login = $arUser['LOGIN'];
            if ($this->login($login, $password)) {
                return true;
            }
        }

        return $this->login($email, $password);
    }

    /**
     * @return array
     */
    public function getLoginErrors() {
        return $this->_login_errors;
    }

    /**
     * @param $id
     * @return array
     */
    public function findById($id) {
        $rsUser = CUser::GetByID($id);
        return $rsUser->Fetch();
    }

    /**
     * @param $email
     * @return array|bool
     */
    public function findByEmail($email) {
        $filter = array(
            "ACTIVE" => "Y",
            "EMAIL"  => $email
        );

        $rsUsers = $this->_bx_user->GetList($by, $order, $filter);
        if ($arUser = $rsUsers->Fetch()) {
            return $arUser;
        }

        return false;
    }

    /**
     * @param $login
     * @param $checkword
     * @param $password
     * @return array|bool
     */
    public function changePassword($login, $checkword, $password) {
        $result = $this->_bx_user->ChangePassword($login, $checkword, $password, $password);
        if ($result['TYPE'] === 'OK') {
            return true;
        }

        return array($result['FIELD'] => $result['MESSAGE']);
    }

    /**
     * @param $email
     * @return bool
     */
    public function remindPassword($email) {
        $user = $this->findByEmail($email);
        if ($user) {
            $result = $this->_bx_user->SendPassword($user['LOGIN'], $email);
            if ($result['TYPE'] === 'OK') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $fields
     * @return bool
     */
    public function save($fields) {
        return false;
    }
}
