<?php

/**
 * Class UserHelper
 */
class UserHelper {
    /**
     * Get user by id
     *
     * @param int $id
     * @return array
     */
    public static function GetByID($id) {
        $rsUser = CUser::GetByID($id);
        $arUser = $rsUser->Fetch();
        return ($arUser["ID"]) ? $arUser : false;
    }

    /**
     * Get user by email
     *
     * @param string $email
     * @return array
     */
    public static function GetByEmail($email) {
        $rsUser = CUser::GetList(($by = "id"), ($order = "desc"), array("EMAIL" => $email));
        $arUser = $rsUser->Fetch();
        return ($arUser["ID"]) ? $arUser : false;
    }

    /**
     * Get user by login
     *
     * @param string $login
     * @return array
     */
    public static function GetByLogin($login) {
        $rsUser = CUser::GetList(($by = "id"), ($order = "desc"), array("LOGIN_EQUAL" => $login));
        $arUser = $rsUser->Fetch();
        return ($arUser["ID"]) ? $arUser : false;
    }

    /**
     * Get only active users by email
     *
     * @param string $email
     * @return array
     */
    public static function GetActiveByEmail($email) {
        $rsUser = CUser::GetList(($by = "id"), ($order = "desc"), array("EMAIL" => $email, "ACTIVE" => "Y"));
        $arUser = $rsUser->Fetch();
        return ($arUser["ID"]) ? $arUser : false;
    }

    /**
     * Get only active users by login
     *
     * @param string $email
     * @return array
     */
    public static function GetActiveByLogin($login) {
        $rsUser = CUser::GetList(($by = "id"), ($order = "desc"), array("LOGIN" => $login, "ACTIVE" => "Y"));
        $arUser = $rsUser->Fetch();
        return ($arUser["ID"]) ? $arUser : false;
    }

    /**
     * Check user exists in the system by id
     *
     * @param int $id
     * @return boolean
     */
    public static function CheckExistByID($id) {
        return (self::GetByID($id)) ? true : false;
    }

    /**
     * Check user exists in the system by email
     *
     * @param string $email
     * @return boolean
     */
    public static function CheckExistByEmail($email) {
        return (self::GetByEmail($email)) ? true : false;
    }

    /**
     * Check user exists in the system by login
     *
     * @param string $login
     * @return boolean
     */
    public static function CheckExistsByLogin($login) {
        return (self::GetByLogin($login)) ? true : false;
    }

    /**
     * Check user exists in the system and user is active by email
     *
     * @param string $email
     * @return boolean
     */
    public static function CheckActiveByEmail($email) {
        return (self::GetActiveByEmail($email)) ? true : false;
    }

    /**
     * Check user exists in the system and user is active by email
     *
     * @param string $email
     * @return boolean
     */
    public static function CheckActiveByLogin($login) {
        return (self::GetActiveByLogin($login)) ? true : false;
    }

    /**
     * Generate random password for user
     *
     * @return string
     */
    public static function GeneratePassword() {
        return randString(SS_PASSWORD_LENGTH, array("abcdefghijklnmopqrstuvwxyz", "ABCDEFGHIJKLNMOPQRSTUVWXYZ", "0123456789"));
    }

    /**
     * Generate random confirmation code for user
     *
     * @return string
     */
    public static function GenerateConfirmCode() {
        return randString(SS_CONFIRM_CODE_LENGTH);
    }

    /**
     * Get subscription id by user's email
     *
     * @param string $email
     * @return array|boolean
     */
    public static function GetSubscriptionByEmail($email) {
        if (!CModule::IncludeModule("subscribe")) {
            return false;
        }
        $rsSubscription = CSubscription::GetByEmail($email);
        $arSubscription = $rsSubscription->Fetch();
        return ($arSubscription["ID"]) ? $arSubscription : false;
    }

    /**
     * Check email is subscribed
     *
     * @param string $email
     * @return boolean
     */
    public static function CheckSubscriptionExistByEmail($email) {
        return (self::GetSubscriptionByEmail($email)) ? true : false;
    }

    /**
     * Subscribe user by email
     *
     * @global object $APPLICATION
     * @param string $email
     * @return array
     */
    public static function Subscribe($email) {
        global $APPLICATION;
        $arResult["SUCCESS"] = true;
        $arFields = array("EMAIL" => $email, "FORMAT" => "html", "ACTIVE" => "Y", "RUB_ID" => array(SUBSCR_MAIN), "SEND_CONFIRM" => "Y", "CONFIRM_CODE" => UserHelper::GenerateConfirmCode());

        if (!CModule::IncludeModule("subscribe")) {
            $arResult["SUCCESS"] = false;
            $arResult["MESSAGE"] = "Модуль подписок не установлен на сайте";
        }

        $subscription = new CSubscription;
        if ($subscription->Add($arFields)) {
            $arResult["MESSAGE"] = "На указанный e-mail отправлено письмо для подтверждения подписки";
        } else {
            $arResult["SUCCESS"] = false;
            $arResult["MESSAGE"] = strip_tags($subscription->LAST_ERROR);
        }

        return $arResult;
    }

    /**
     * @param $email
     */
    public static function SubscribeWithoutConfirmation($email) {
        if (!CModule::IncludeModule("subscribe")) {
            $arResult["SUCCESS"] = false;
            $arResult["MESSAGE"] = "Модуль подписок не установлен на сайте";
        }
        $subscr = new CSubscription;
        $subscription = $subscr->GetByEmail($email);
        $arSubscriptionResult = $subscription->Fetch();

        /* If existing subscribtion */
        if (self::CheckSubscriptionExistByEmail($email)) {
            $subscr->Update($arSubscriptionResult['ID'], array("ACTIVE" => "Y", "SEND_CONFIRM" => "N", "CONFIRMED" => "Y", "RUB_ID" => array(SUBSCR_MAIN)));
            /* If new subscribtion */
        } else {
            $arFields = Array("EMAIL" => $email, "ACTIVE" => "Y", "CONFIRMED" => "Y", "SEND_CONFIRM" => "N", "RUB_ID" => array(SUBSCR_MAIN), "FORMAT" => "html");
            $ID = $subscr->Add($arFields);
        }
    }

    /**
     * @param $email
     */
    public function UnsubscribeWithoutConfirmation($email) {
        if (!CModule::IncludeModule("subscribe")) {
            $arResult["SUCCESS"] = false;
            $arResult["MESSAGE"] = "Модуль подписок не установлен на сайте";
        }
        $subscr = new CSubscription;
        $subscription = $subscr->GetByEmail($email);
        $arSubscriptionResult = $subscription->Fetch();

        if (self::CheckSubscriptionExistByEmail($email)) {
            $subscr->Update($arSubscriptionResult['ID'], array("ACTIVE" => "N"));
        }
    }

    /**
     * @param $email
     * @return bool
     */
    public static function checkActiveSubscription($email) {
        if (self::CheckSubscriptionExistByEmail($email)) {
            if (!CModule::IncludeModule("subscribe")) {
                $arResult["SUCCESS"] = false;
                $arResult["MESSAGE"] = "Модуль подписок не установлен на сайте";
            }
            $subscr = new CSubscription;
            $subscription = $subscr->GetByEmail($email);
            $arSubscriptionResult = $subscription->Fetch();

            if ($arSubscriptionResult['ACTIVE'] == "N") return false;
            return true;
        }
        return false;
    }

    /**
     * Get checkword for password changing
     *
     * @global object $DB
     * @param int $id
     * @param string $siteId
     * @return string
     */
    public static function GetCheckword($id, $siteId) {
        global $DB;

        $id = intval($id);
        $salt = randString(8);
        $checkword = randString(8);

        $query = "UPDATE b_user SET " . "   CHECKWORD = '" . $salt . md5($salt . $checkword) . "', " . "   CHECKWORD_TIME = " . $DB->CurrentTimeFunction() . ", " . "   LID = '" . $DB->ForSql($siteId, 2) . "' " . "WHERE ID = '" . $id . "'" . "   AND (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='') ";
        $DB->query($query, false, "FILE: " . __FILE__ . "<br> LINE: " . __LINE__);

        return $checkword;
    }

    /**
     * Checks user password
     *
     * @param int $userId
     * @param string $password
     * @return boolean
     */
    public static function CheckPassword($userId, $password) {
        $userData = CUser::GetByID($userId)->Fetch();

        $salt = substr($userData['PASSWORD'], 0, (strlen($userData['PASSWORD']) - 32));

        $realPassword = substr($userData['PASSWORD'], -32);
        $password = md5($salt . $password);

        return ($password == $realPassword);
    }

    /**
     * Check user can see private info about porducts: price, balance etc.
     *
     * @global object $USER
     * @return boolean
     */
    public static function CanSeePrivateInfo() {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return false;
        }

        $id = $USER->GetId();

        $arGroup = $USER->GetUserGroup($id);

        $rsUser = CUser::GetById($id);
        $arUser = $rsUser->Fetch();

        return (in_array(UG_ADMIN, $arGroup) || $arUser['UF_ACCEPTED']);
    }

    /**
     * Get user's discount on some brand
     *
     * @param int $userId
     * @param string $brandXMLId
     * @return float
     */
    public static function GetDiscount($userId, $brandXMLId) {
        if (!$userId || !$brandXMLId) {
            return 0;
        }

        $discount = 0;

        CModule::IncludeModule('iblock');

        /* CHECK! User must be a manager */
        $arGroups = CUser::GetUserGroup($userId);
        if (!in_array(UG_COMPANY_MANAGER, $arGroups)) {
            return 0;
        }

        $arFilter = array("IBLOCK_ID" => IB_DISCOUNT, "PROPERTY_CONTRACTOR_ID" => self::getUserContractor($userId), "PROPERTY_BRAND_ID" => $brandXMLId, "ACTIVE" => "Y");
        $rsDiscount = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "PROPERTY_DISCOUNT"));

        if ($arDiscount = $rsDiscount->Fetch()) {
            $discount = floatval($arDiscount["PROPERTY_DISCOUNT_VALUE"]);
        }

        return $discount ? $discount : 0;
    }

    /**
     * Get price with discount
     *
     * @param float $price
     * @param int $userId
     * @param string $brandXMLId
     * @return float
     */
    public static function GetDiscountedPrice($price, $userId, $brandXMLId) {
        return floatval($price)*floatval(1 - (self::GetDiscount($userId, $brandXMLId)/100));
    }

    /**
     * Transfers basket from one user to another one
     *
     * @param int $from
     * @param int $to
     * @global object $DB
     * @return boolean
     */
    public static function TransferBasket($from, $to) {
        global $DB;
        $from = intval($from);
        $to = intval($to);
        CModule::IncludeModule('sale');

        if (($to > 0) && (CSaleUser::GetList(array("ID" => $to)))) {
            $deleteQuery = "DELETE FROM b_sale_basket WHERE FUSER_ID = " . $to . " ";
            $updateQuery = "UPDATE b_sale_basket SET " . "    FUSER_ID = " . $to . " " . "WHERE FUSER_ID = " . $from . " ";
            $DB->Query($deleteQuery, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
            $DB->Query($updateQuery, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
            return true;
        }
        return false;
    }

    /**
     * Make order code for import
     *
     * @param int $id
     * @return string
     */
    public static function MakeOrderCode($id) {
        return SS_IMPORT_SITE_ID . intval($id);
    }

    /**
     * Get order status to display
     *
     * @param int $orderId
     * @return array
     */
    public static function GetDisplayOrderStatus($orderId) {
        CModule::IncludeModule('iblock');
        $arResult = array("CODE" => "", "NAME" => "");

        $arFilter = array("IBLOCK_ID" => IB_ORDERS, "ID" => intval($orderId));
        $arSelect = array("ID", "PROPERTY_CONFIRMED", "PROPERTY_COLLECTED", "PROPERTY_CLOSED");
        $rsOrder = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        if ($arOrder = $rsOrder->Fetch()) {
            if ($arOrder["PROPERTY_CLOSED_VALUE"]) {
                $arResult["CODE"] = "closed";
                $arResult["NAME"] = "Закрыт";
            } elseif ($arOrder["PROPERTY_COLLECTED_VALUE"]) {
                $arResult["CODE"] = "collected";
                $arResult["NAME"] = "Собран";
            } elseif ($arOrder["PROPERTY_CONFIRMED_VALUE"]) {
                $arResult["CODE"] = "confirmed";
                $arResult["NAME"] = "Подтвержден";
            }
        }

        return $arResult;
    }

    /**
     * Checks if order have status, then user can't delete products from order
     *
     * @param int $orderId
     * @return string
     */
    public static function CanDeleteProductFromOrder($orderId) {
        $arStatus = self::GetDisplayOrderStatus($orderId);
        return $arStatus["CODE"] ? false : true;
    }

    /**
     * Get quantity of basket product's articuls
     *
     * @return int
     */
    public static function GetBasketProductsQuantity() {
        $i = 0;

        $rsBasketProducts = CSaleBasket::GetList(array(), array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "LID" => SS_SITE_ID, "ORDER_ID" => "NULL"), false, false, array("ID"));
        while ($arItem = $rsBasketProducts->GetNext()) {
            $i++;
        }

        return $i;
    }

    /**
     * Checks product is in basket of current user and returns it's id
     *
     * @param int $productID
     * @return boolean
     */
    public static function idProductInBasket($productID) {
        global $DB;

        $id = intval($productID);
        $fuserID = CSaleBasket::GetBasketUserID();

        $strSql = "SELECT ID " . "FROM b_sale_basket " . "WHERE PRODUCT_ID = " . $id . " AND FUSER_ID = " . $fuserID . "";
        $rsBasket = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

        if ($arBasket = $rsBasket->Fetch()) return $arBasket["ID"] ? $arBasket["ID"] : false;
    }

    /**
     * Returns quantity of product in current user's basket
     *
     * @param int $productID
     * @return float
     */
    public static function getProductInBasketParams($productID) {
        global $DB;
        CModule::IncludeModule('sale');

        $id = intval($productID);
        $fuserID = CSaleBasket::GetBasketUserID();

        $strSql = "SELECT ID, QUANTITY " . "FROM b_sale_basket " . "WHERE PRODUCT_ID = " . $id . " AND FUSER_ID = " . $fuserID . "";
        $rsBasket = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

        if ($arBasket = $rsBasket->Fetch()) return array("ID" => $arBasket["ID"], "QUANTITY" => floatval($arBasket["QUANTITY"]));

        return false;
    }

    /**
     * Delete all products from aurrent user's basket
     *
     * @return boolean
     */
    public static function emptyBasket() {
        global $DB;
        CModule::IncludeModule('sale');

        $fuserID = CSaleBasket::GetBasketUserID();

        $strSql = "DELETE " . "FROM b_sale_basket " . "WHERE FUSER_ID = " . $fuserID . "";
        $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

        return true;
    }

    /**
     * @return bool
     */
    public static function getReservLimit() {
        global $USER;
        $id = intval($USER->GetID());

        $arUser = CUser::GetList(($by = "id"), ($order = "desc"), array("ID" => $id), array("SELECT" => array("UF_RESERVE_LIMIT")))->GetNext();
        $limit = $arUser["UF_RESERVE_LIMIT"];

        return $limit ? $limit : false;
    }

    /**
     *    Returns current user group name and id
     */
    public static function getUserGroup($userId = null) {
        global $USER;
        if (!$userId) {
            $id = intval($USER->GetID());
        } else {
            $id = $userId;
        }

        $arGroups = CUser::GetUserGroup($id);

        if (in_array(UG_COMPANY_ADMIN, $arGroups)) {
            $userGroup = array('ID' => UG_COMPANY_ADMIN, 'NAME' => "Администратор");
        } elseif (in_array(UG_COMPANY_MANAGER, $arGroups)) {
            $userGroup = array('ID' => UG_COMPANY_MANAGER, 'NAME' => "Менеджер");
        } else {
            $userGroup = array('ID' => UG_SITE_USER, 'NAME' => "Пользователь");
        }

        return $userGroup;
    }

    /**
     *    Sets or unsets 'send docs' user setting
     */
    public static function ChangeSendDocs($value) {
        global $USER;
        $id = intval($USER->GetID());

        if ($value) {
            $fields = array("UF_SEND_DOCS" => true);
        } else {
            $fields = array("UF_SEND_DOCS" => false);
        }
        return $USER->Update($id, $fields);
    }

    /**
     *    Sets or unsets 'send docs' user setting (for managers)
     */
    public static function ChangeManagerSendDocs($value) {
        global $USER;
        $id = intval($USER->GetID());

        if ($value) {
            $fields = array("UF_SEND_DOCS_M" => true);
        } else {
            $fields = array("UF_SEND_DOCS_M" => false);
        }
        return $USER->Update($id, $fields);
    }

    /**
     *    Sets or unsets 'payment notification' user setting
     */
    public static function ChangePaymentNotification($value) {
        global $USER;
        $id = intval($USER->GetID());

        if ($value) {
            $fields = array("UF_PAYMENT_NOTIFY" => true);
        } else {
            $fields = array("UF_PAYMENT_NOTIFY" => false);
        }
        return $USER->Update($id, $fields);
    }

    /**
     *    Sets or unsets 'payment notification' user setting (for managers)
     */
    public static function ChangeManagerPaymentNotification($value) {
        global $USER;
        $id = intval($USER->GetID());

        if ($value) {
            $fields = array("UF_PAYMENT_NOTIFY_M" => true);
        } else {
            $fields = array("UF_PAYMENT_NOTIFY_M" => false);
        }
        return $USER->Update($id, $fields);
    }

    /**
     *    Sets or unsets 'reserve limit notification' user setting
     */
    public static function ChangeReserveLimitNotification($value) {
        global $USER;
        $id = intval($USER->GetID());

        if ($value) {
            $fields = array("UF_AUTH_LIMIT_NOTIFY" => true);
        } else {
            $fields = array("UF_AUTH_LIMIT_NOTIFY" => false);
        }
        return $USER->Update($id, $fields);
    }

    /**
     *    Returns user notification settings
     */
    public static function getUserNotifications() {
        global $USER;
        $id = intval($USER->GetID());

        $arUser = CUser::GetList($by = "id", $order = "desc", array("ID" => $id), array("SELECT" => array("UF_SEND_DOCS", "UF_PAYMENT_NOTIFY", "UF_AUTH_LIMIT_NOTIFY", "UF_SEND_DOCS_M", "UF_PAYMENT_NOTIFY_M")))->GetNext();

        return $arUser;
    }

    /**
     * @param $contractorId
     * @param null $userId
     * @return bool
     */
    public static function setUserContractor($contractorId, $userId = null) {
        global $USER;
        $userId = (int)$userId ? $userId : $USER->GetID();

        if (!$userId) {
            return false;
        }

        return $USER->Update($userId, array("UF_LAST_CONTRACTOR" => $contractorId));
    }

    /**
     * @param null $userId
     * @return mixed
     */
    public static function getUserContractor($userId = null) {
        global $USER;
        $userId = (int)$userId ? $userId : $USER->GetID();

        $arUser = CUser::GetByID($userId)->Fetch();

        return $arUser["UF_LAST_CONTRACTOR"] ? $arUser["UF_LAST_CONTRACTOR"] : $arUser["UF_CONTRACTOR"][0];
    }

    /**
     * @param null $userId
     * @return bool
     */
    public static function checkUserContractor($userId = null) {
        global $USER;
        $userId = (int)$userId ? $userId : $USER->GetID();

        $arUser = CUser::GetByID($userId)->Fetch();

        $itsCode = stripos((string)$arUser["UF_LAST_CONTRACTOR"], "0") === 0;

        if (!$arUser["UF_LAST_CONTRACTOR"] || $itsCode) {
            $USER->Update($userId, array("UF_LAST_CONTRACTOR" => $arUser["UF_CONTRACTOR"][0]));
        }

        return true;
    }

    /**
     * @param $contractorId
     * @return bool
     */
    public static function getContractorCode1C($contractorId) {
        if (!(int)$contractorId) {
            return false;
        }

        CModule::IncludeModule("iblock");

        $arContractor = CIBlockElement::GetList(array("id" => "asc"), array("IBLOCK_ID" => IB_CONTRACTORS, "ID" => (int)$contractorId), false, array("nTopCount" => 1), array("ID", "XML_ID"))->GetNext();

        return $arContractor["XML_ID"] ? $arContractor["XML_ID"] : false;
    }

    /**
     * @param $orderId
     * @param $productCode
     * @return int
     */
    public static function getOrderProductCount($orderId, $productCode) {
        CModule::includeModule("iblock");

        $arElement = CIBlockElement::GetList(array("id" => "asc"), array("IBLOCK_ID" => IB_ORDER_PRODUCTS, "PROPERTY_ORDER_ID" => $orderId, "PROPERTY_PRODUCT_XML_ID" => $productCode), false, array("nTopCount" => 1), array("ID", "PROPERTY_AMOUNT"))->GetNext();

        return intval($arElement["PROPERTY_AMOUNT_VALUE"]);
    }

    /**
     * @param null $userId
     * @param bool $includeSelf
     * @return array
     */
    public static function getUserAdmins($userId = null, $includeSelf = false) {
        global $USER;
        $userId = (int)$userId ? $userId : $USER->GetID();
        $result = array();

        // Get user's contractors
        $arUser = CUser::GetByID($userId)->Fetch();
        $contractors = $arUser["UF_CONTRACTOR"];

        // Find user's contractors admins
        $adminFilter = array("ACTIVE" => "Y", "UF_CONTRACTOR" => $contractors, "GROUPS_ID" => UG_COMPANY_ADMIN, "UF_SEND_DOCS_M" => true);
        $rsAdmins = CUser::GetList($by = "id", $order = "asc", $adminFilter);

        while ($arAdmin = $rsAdmins->Fetch()) {
            if ($arAdmin["ID"] != $userId || $includeSelf) {
                $result[] = array("ID" => $arAdmin["ID"], "EMAIL" => $arAdmin["EMAIL"]);
            }
        }

        return $result;
    }

    /**
     * @param $orderId
     */
    public static function markOrderAsDeletedProduct($orderId) {
        CModule::IncludeModule("iblock");

        CIBlockElement::SetPropertyValuesEx($orderId, IB_ORDERS, array('PRODUCT_DELETED' => PRODUCT_DELETED));
    }
}