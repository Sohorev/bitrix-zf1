<?php

/**
 * Class EUser
 */
class EUser {
    /**
     * @var
     */
    private static $_instance;

    /**
     * @var null
     */
    private $_id;

    /**
     * @var int
     */
    private $_group = UG_NOT_AUTH;

    /**
     * @var array
     */
    private $_allGroups = array(
        UG_COMPANY_ADMIN,
        UG_COMPANY_MANAGER,
        UG_SITE_USER,
        UG_NOT_AUTH
    );

    /**
     * @var array
     */
    private $_rights = array();

    /**
     * @var array
     */
    private $_allRights = array(
        UG_NOT_AUTH        => array(
            "showAuthNotification"
        ),
        UG_SITE_USER       => array(
            "canAddToBasket",
            "canSeeProductInfo",
            "canAddClientCard",
            "showManagerOffer"
        ),
        UG_COMPANY_MANAGER => array(
            "canAddToBasket",
            "canSeeProductInfo",
            "canDoOrders",
            "canSeeUsefulLinks",
            "canSeePresentations",
            "canSeeActions",
            "canAddContractors",
            "canSeeBonus",
            "canConstructor"
        ),
        UG_COMPANY_ADMIN   => array(
            "canAddToBasket",
            "canSeeProductInfo",
            "canDoOrders",
            "canSeeCompanyOrders",
            "canShipOrders",
            "canSeeUsefulLinks",
            "canSeePresentations",
            "canSeeActions",
            "canAddContractors",
            "canSeeBonus",
            "canConstructor",
            "canSeeStatistics"
        )
    );

    /**
     * @var array|bool|mixed
     */
    private $_user = array();

    /**
     *
     */
    final private function __construct() {
        global $USER;

        $this->_id      = $USER->GetID();
        $this->_user    = $USER->GetByID($this->_id)->Fetch();
        $this->_groups  = $USER->GetUserGroup($this->_id);

        $this->setGroup()->setRights();
    }

    /**
     *
     */
    final private function __clone() {
    }

    /**
     *
     */
    final private function __wakeup() {
    }

    /**
     * @return EUser
     */
    public static function getInstance() {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @return array|bool|mixed
     */
    public function getUserData() {
        return $this->_user;
    }

    /**
     * @return null
     */
    public function getUserId() {
        return $this->_id;
    }

    /**
     * @return mixed
     */
    public function getWorkCompany() {
        return $this->_user['WORK_COMPANY'];
    }

    /**
     * @return $this
     */
    public function resetId() {
        global $USER;

        $this->_id = $USER->GetID();

        return $this;
    }

    /**
     * @return $this
     */
    private function setGroup() {
        foreach ($this->_allGroups as $group) {
            if ($this->InGroup($group)) {
                $this->_group = $group;
                break;
            }
        }

        return $this;
    }

    /**
     * @param $group
     * @return bool
     */
    private function InGroup($group) {
        return in_array($group, $this->_groups);
    }

    /**
     * @return $this
     */
    private function setRights() {
        $this->_rights = $this->_allRights[$this->_group];

        return $this;
    }

    /**
     * @param $right
     * @return bool
     */
    private function checkRight($right) {
        return in_array($right, $this->_rights);
    }

    /**
     * @return bool
     */
    public function canAddToBasket() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canSeeProductInfo() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canDoOrders() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canSeeCompanyOrders() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canShipOrders() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canAddClientCard() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canSeeUsefulLinks() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canSeePresentations() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canSeeActions() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canConstructor()
    {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function showAuthNotification() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function showManagerOffer() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canAddContractors() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canSeeBonus() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function canSeeStatistics() {
        return $this->checkRight(__FUNCTION__);
    }

    /**
     * @return mixed
     */
    public function getUserContractors() {
        $arUser = CUser::GetByID($this->_id)->Fetch();

        return $arUser["UF_CONTRACTOR"];
    }

    /**
     * @return array
     */
    public function getUserContractorsNames() {
        $contractors = $this->getUserContractors();
        $contractorsNames = array();

        if (count($contractors) > 0) {
            $arSort = array("NAME" => "asc", "ID" => "asc");
            $arFilter = array("IBLOCK_ID" => IB_CONTRACTORS, "ID" => $contractors);
            $arSelect = array("ID", "NAME", "XML_ID");
            $res = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
            while ($arTmp = $res->Fetch()) {
                $contractorsNames[] = $arTmp;
            }
        }

        return $contractorsNames;
    }

    /**
     * @return string
     */
    public function getUserContractorsNamesString() {
        $contractorsNames = $this->getUserContractorsNames();
        $result = '';
        foreach ($contractorsNames as $contractor) {
            $result .= $contractor['NAME'] . ', ';
        }
        $result = rtrim($result, ', ');
        return $result;
    }

    /**
     * @return array
     */
    public function getContractorsManagers() {
        $arManagers = array();

        foreach ($this->getUserContractors() as $contractorId) {
            foreach ($this->getContractorManagers($contractorId) as $key => $val) {
                $arManagers[$key] = $val;
            }
        }

        return $arManagers;
    }

    /**
     * @param $id
     * @return array
     */
    public function getContractorManagers($id) {
        $filter = array("UF_CONTRACTOR" => array($id));
        $rsUsers = CUser::GetList($by = "LAST_NAME", $order = "asc", $filter, array("SELECT" => array("UF_CONTRACTOR")));

        $arUsers = array();
        while ($arUser = $rsUsers->Fetch()) {
            $arUsers[$arUser["ID"]] = $arUser["LAST_NAME"] . " " . $arUser["NAME"];
        }

        return $arUsers;
    }

    /**
     * @return array
     */
    public function getAdminManagers() {
        return $this->getContractorsManagers();
    }

    /**
     * @return bool
     */
    protected function getLastContractor() {
        $contractorId = UserHelper::getUserContractor($this->_id);

        $arFilter = array("IBLOCK_ID" => IB_CONTRACTORS, "ACTIVE" => "Y", "ID" => $contractorId);

        $arContractor = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "PROPERTY_RESERV_LIMIT", "XML_ID"))->Fetch();

        return $arContractor;
    }

    /**
     * @return mixed
     */
    public function getContractorReserveLimit() {
        $arContractor = $this->getLastContractor();
        return $arContractor["PROPERTY_RESERV_LIMIT_VALUE"];
    }

    /**
     * @return bool
     */
    public function isReserveUnlimited() {
        return (floatval($this->getContractorReserveLimit()) == 0);
    }

    /**
     * @param null $arContractor
     * @return float
     */
    public function getReserveLimitBalance($arContractor = null) {
        if (!isset($arContractor)) {
            $arContractor = $this->getLastContractor();
        }

        return floatval($arContractor["PROPERTY_RESERV_LIMIT_VALUE"]) - $this->getReservedSum($arContractor["ID"]);
    }

    /**
     * @param null $contractor
     * @return bool|float|int
     */
    public function getReservedSum($contractor = null) {
        if (!$contractor) {
            return false;
        }

        CModule::IncludeModule('iblock');

        $arSort = array("active_from" => "desc");
        $arFilter = array("IBLOCK_ID" => IB_ORDERS, "ACTIVE" => "Y", "!ID" => $_SESSION[SESS_EDIT_ORDER]["ID"], "!PROPERTY_MAIN_ORDER_ID" => false, "PROPERTY_CLOSED" => false, "PROPERTY_DELETED_ORDER" => false,);
        $arSelect = array("ID", "NAME", "PROPERTY_MAIN_ORDER_ID", "PROPERTY_NUMBER", "PROPERTY_ORDER_ID");

        if ((int)$contractor) {
            $arFilter["PROPERTY_CONTRACTOR"] = $contractor;
        }

        $rsOrders = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
        $arOrderIds = array();
        while ($arOrder = $rsOrders->Fetch()) {
            $arOrderIds[] = $arOrder["PROPERTY_ORDER_ID_VALUE"];
//            $s += $this->getReservedProductsSum($arOrder["PROPERTY_ORDER_ID_VALUE"]);
        }

        $s = $this->getReservedProductsSum($arOrderIds);

        return $s;
    }

    /**
     * @param $orderId
     * @return float|int
     */
    public function getReservedProductsSum($orderId) {
        if (!$orderId) {
            return 0;
        }

        $arSort = array("active_from" => "desc");
        $arFilter = array("IBLOCK_ID" => IB_ORDER_PRODUCTS, "ACTIVE" => "Y", "PROPERTY_ORDER_ID" => $orderId);
        $arSelect = array("ID", "PROPERTY_SUMMA");

        $s = 0;
        $rsProducts = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
        while ($arProduct = $rsProducts->Fetch()) {
            $s += floatval($arProduct["PROPERTY_SUMMA_VALUE"]);
        }

        return $s;
    }

    /**
     * @param $contractorId
     * @return string
     * Возвращает менеджера контрагента
     */
    public function getContractorManager($contractorId) {
        $arSort = array();
        $arFilter = array("IBLOCK_ID" => IB_CONTRACTORS, "ACTIVE" => "Y", "ID" => $contractorId);
        $arSelect = array('ID', 'NAME', "PROPERTY_MANAGER");

        $rsManagerName = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);

        if ($arManagerName = $rsManagerName->Fetch()) {
            // Получаем e-mail менеджера
            $arFilter = array("IBLOCK_ID" => IB_MANAGERS, "ACTIVE" => "Y", "NAME" => $arManagerName['PROPERTY_MANAGER_VALUE']);
            $arSelect = array("PROPERTY_EMAIL");
            $rsManagerName = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);

            if ($arManagerEmail = $rsManagerName->Fetch()) {
                return array_merge($arManagerName, $arManagerEmail);
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isShowHelpUsPopup() {
        return $this->_id && !$this->_user['UF_NOT_SHOW_HELP_US'];
    }
}