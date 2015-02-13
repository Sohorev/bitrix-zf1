<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/* ------------------------------------------------------------------------------------------
    ADD EVENTS HANDLERS
------------------------------------------------------------------------------------------ */

/**
 * Инициализируем класс для работы с правами пользователя
 */
AddEventHandler("main", "OnBeforeProlog", array("EventHandler", "OnBeforePrologHandler"));

/**
 * При инициализации меню в админ. части добавляем в него свою кнопку для импорта товаров
 */
AddEventHandler("main", "OnBuildGlobalMenu", array("EventHandler", "OnBuildGlobalMenuHandler"));

/**
 * Проверяем, не изменился ли атрибут "одобрен" или группа пользователя
 */
AddEventHandler("main", "OnBeforeUserUpdate", array("EventHandler", "OnBeforeUserUpdateHandler"));
/**
 * при смене атрибута "одобрен" или группы отправляем письмо
 */
AddEventHandler("main", "OnAfterUserUpdate", array("EventHandler", "OnAfterUserUpdateHandler"));

/**
 * При регистрации пользователя отправляем ему письмо с информацией для смены пароля
 */
// AddEventHandler("main", "OnAfterUserAdd", array("EventHandler", "OnAfterUserAddHandler"));

/**
 * При авторизации пользователя нужно прокинуть его текущую корзину в сессию авторизованного пользователя
 */
// AddEventHandler("main", "OnBeforeUserLogin", array("EventHandler", "OnBeforeUserLoginHandler"));

/**
 * При выходе пользователя с сайта нужно прокинуть его текущую корзину в сессию неавторизованного пользователя
 */
AddEventHandler("main", "OnBeforeUserLogout", array("EventHandler", "OnBeforeUserLogoutHandler"));

/**
 * При добавлении события аттачим к письму файл с остатками товаров
 */
AddEventHandler("main", "OnBeforeEventAdd", array("EventHandler", "OnBeforeEventAddHandler"));

/**
 * Перед отправкой письма если фамилия заполнена, добавляем ее к имени через пробел. Сделано для того, чтобы
 * избавиться от пробелов после имени, когда фамилия незаполнена.
 */
AddEventHandler("main", "OnBeforeEventSend", array("EventHandler", "OnBeforeEventSendHandler"));

/**
 * При удалении товара, удаляем связные картинки
 * При удалении заказа, удаляем связные товары
 */
AddEventHandler("iblock", "OnBeforeIBlockElementDelete", array("EventHandler", "OnBeforeIBlockElementDeleteHandler"));

/**
 * При добавлении элемента инфоблока добавляем свой обработчик
 */
AddEventHandler("iblock", "OnAfterIBlockElementAdd", array("EventHandler", "OnAfterIBlockElementAddHandler"));

/**
 * Перед отправкой письма из рассылки вставляем в шаблон параметры для генерации ссылки на отписку от рассылки
 */
AddEventHandler("subscribe", "BeforePostingSendMail", array("EventHandler", "BeforePostingSendMailHandler"));

/**
 * Перед выводом формы создания/редактирования выпуска почтовой рассылки добавляет новую вкладку с типовыми шаблонами
 */
AddEventHandler("main", "OnAdminTabControlBegin", "addTabMailTemaplates");

/**
 * При авторизации в сессию добавляется группа пользователя
 */
AddEventHandler("main", "OnAfterUserLogin", array("EventHandler", "OnAfterUserLoginHandler"));

/* ------------------------------------------------------------------------------------------
    HANDLERS
------------------------------------------------------------------------------------------ */

class EventHandler
{
    static private $fuser = NULL;

    static private $isAcceptedChange = false;
    static private $isUserGroupChange = false;

    function OnBeforePrologHandler()
    {
        global $USER;

        $euser = EUser::getInstance();
		$UG = UserHelper::getUserGroup();

		// Изменилась группа пользователя
		if ($_SESSION['INIT_UG']) {
			if ($_SESSION['INIT_UG'] != $UG['ID']) {
				$_SESSION['CHANGED_UG'] = $UG;
			} else {
				unset($_SESSION['CHANGED_UG']);
			}
		} else {
            $_SESSION['INIT_UG'] = $UG['ID'];
        }

        // Если у пользователя не отмечен последний выбранный контрагент,
        // подставляем первого найденного
        if ($USER->IsAuthorized()) {
            UserHelper::checkUserContractor();
        }
    }

    function OnBeforeIBlockElementDeleteHandler ($id)
    {
        global $DB;

        // (каталог) при удалении товара из каталога - удаляем картинки
        $arFilter = array('IBLOCK_ID' => IB_PRODUCTS, 'ID' => $id);
        $arSelect = array('XML_ID');

        $dbElement = CIBlockElement::GetList(null,$arFilter,null,null,$arSelect);
        if($arElement = $dbElement->Fetch()){
            $result = $DB->Query("SELECT * FROM `b_file` WHERE `ORIGINAL_NAME` LIKE '{$arElement['XML_ID']}%'");

            while($row = $result->Fetch()){
                CFile::Delete($row['ID']);
            }
        }

        // (магазин) при удалении заказа - удаляем связные товары
        $arFilter = array('IBLOCK_ID' => IB_ORDERS, 'ID' => $id, '!PROPERTY_MAIN_ORDER_ID' => false);
        $arSelect = array('PROPERTY_ORDER_ID');

        $ibElement = new CIBlockElement;
        $rsElement = $ibElement->GetList(null, $arFilter, null, null, $arSelect);
        if($arElement = $rsElement->Fetch()){

            $arFilter = array('IBLOCK_ID' => IB_ORDER_PRODUCTS, 'PROPERTY_ORDER_ID' => $arElement['PROPERTY_ORDER_ID_VALUE']);
            $arSelect = array('ID');

            $rsProducts = $ibElement->GetList(null, $arFilter, null, null, $arSelect);
            while($row = $rsProducts->Fetch()){
                $ibElement->Delete($row['ID']);
            }
        }

        return true;
    }

//    function Redirect404()
//    {
//        if (
//            !defined('ADMIN_SECTION') &&
//            (defined("ERROR_404") || (function_exists("http_response_code") && http_response_code() == 404)) &&
//            file_exists($_SERVER["DOCUMENT_ROOT"] . "/404.php")
//        ) {
//            global $APPLICATION;
//            $APPLICATION->RestartBuffer();
//            CHTTP::SetStatus("404 Not Found");
//            include($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/header.php");
//            include($_SERVER["DOCUMENT_ROOT"].PATH_TO_404);
//            include($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/footer.php");
//        }
//    }

    function OnBuildGlobalMenuHandler(&$adminMenu, &$moduleMenu)
    {
        global $APPLICATION, $USER;

        if ($USER->IsAdmin()) {
            $APPLICATION->SetAdditionalCSS(P_CSS . "import.css");
            $APPLICATION->AddHeadScript   (P_JS  . "libs/jquery-1.7.2.min.js");
            $APPLICATION->AddHeadScript   (P_JS  . "parts/admin.js");

            return array(
                "my_menu" => array(
                    "icon"       => "button_settings",
                    "page_icon"  => "settings_title_icon",
                    "index_icon" => "settings_page_icon",
                    "text"       => "Импорт товаров",
                    "title"      => "Импорт каталога из 1С",
                    "url"        => "xxx_settings_index.php?lang=".LANGUAGE_ID,
                    "sort"       => 5000,
                    "items_id"   => "my_menu",
                    "items"      => array(
                        array(
                            "text"        => "Импортировать каталог",
                            "url"         => "#importPopup",
                            "title"       => "Открыть окно импорта",
                            "parent_menu" => "global_menu_content",
                            "icon"        => "iblock_menu_icon_types",
                            "page_icon"   => "iblock_page_icon_types"
                        )
                    )
                )
            );
        } else {
            return true;
        }
    }

    function OnBeforeUserUpdateHandler (&$arFields)
    {
        global $USER;
        $user = UserHelper::GetByID($arFields["ID"]);

        /* Проверка на изменение атрибута "одобрен" */
        if ($user["UF_ACCEPTED"] != 1 && $arFields["UF_ACCEPTED"] == 1) {
            self::$isAcceptedChange = true;
        } else {
            self::$isAcceptedChange = false;
        }

        if ($arFields['GROUP_ID']) {
            /* Проверка на изменение групп пользователя */
            $arNewGroups = array(2);
            foreach ($arFields['GROUP_ID'] as $group) {
                $arNewGroups[] = $group['GROUP_ID'];
            }

            $arOldGroups = $USER->GetUserGroup($user['ID']);

            if (count($arOldGroups) == count($arNewGroups) && !array_diff($arOldGroups, $arNewGroups)) {
                self::$isUserGroupChange = false;
            } else {
                self::$isUserGroupChange = true;
            }
        }

    }

    function OnAfterUserUpdateHandler (&$arFields)
    {
        if (self::$isAcceptedChange) {
            $arEventFields = array(
                "USER_ID"      => $arFields["ID"],
                "LOGIN"        => $arFields["EMAIL"],
                "EMAIL"        => $arFields["EMAIL"],
                "NAME"         => $arFields["NAME"],
                "LAST_NAME"    => $arFields["LAST_NAME"],
                "CONFIRM_CODE" => UserHelper::GetCheckword($arFields["ID"], SS_SITE_ID)
            );

            if (!CEvent::Send("USER_ACCEPTED", array(SS_SITE_ID), $arEventFields)) {
                print_r("Произошла ошибка отправки письма пользователю!");
                return;
            }
        }

        $arResult['arUser']['GROUP'] = UserHelper::getUserGroup($arFields["ID"]);

        if (self::$isUserGroupChange) {
            $arEventFields = array(
                "USER_ID"      => $arFields["ID"],
                "LOGIN"        => $arFields["EMAIL"],
                "EMAIL"        => $arFields["EMAIL"],
                "NAME"         => $arFields["NAME"],
                "LAST_NAME"    => $arFields["LAST_NAME"],
                "USER_GROUP"   => $arResult['arUser']['GROUP']['NAME']
            );

            if (!CEvent::Send("USER_GROUP_CHANGED", array(SS_SITE_ID), $arEventFields)) {
                print_r("Произошла ошибка отправки письма пользователю!");
                return;
            }
        }
    }

    function OnAfterUserAddHandler (&$arFields)
    {
        // помечаем пользователя для отправки в 1С
        $arUpdate = array("UF_CST" => True);

        $user = new CUser;
        if(!$user->Update($arFields['ID'], $arUpdate)){
            die($user->LAST_ERROR);
        }
    }

    function OnAfterUserLoginHandler (&$arFields)
    {
		$UG = UserHelper::getUserGroup();
		// В сессионной переменной хранится идентификатор группы пользователя, которая была при авторизации
        $_SESSION['INIT_UG'] = $UG['ID'];
    }

    function OnBeforeUserLogoutHandler (&$arFields)
    {
		unset($_SESSION['INIT_UG']);
		unset($_SESSION['CHANGED_UG']);
    }

    function OnAfterIBlockElementAddHandler (&$arFields)
    {
        if ($arFields["IBLOCK_ID"] == IB_ORDERS && intval($arFields["ID"]) > 0 && !$arFields["PROPERTY_VALUES"]["MAIN_ORDER_ID"]) {
            $arUpdateFields = array(
                "PROPERTY_VALUES" => $arFields["PROPERTY_VALUES"]
            );
            CModule::IncludeModule('iblock');
            $iblock = new CIBlockElement;
            // Make unique order code
            $arUpdateFields["PROPERTY_VALUES"]["ORDER_ID"] = UserHelper::MakeOrderCode($arFields["ID"]);
            // mark element for export to 1C
            $arUpdateFields["PROPERTY_VALUES"]["CST"][]  = array('VALUE' => CST_OK_VALUE);

            $id = $iblock->Update($arFields["ID"], $arUpdateFields);
        }
    }

    function OnBeforeEventAddHandler($event, $lid, &$arFields)
    {
        if (($event == MAILING_BALANCES) || ($event == SALE_NEW_COMMERCIAL_OFFER) || ($event == SALE_REPEAT_OFFER)) {
            /* Attach file and send message */
            SendAttache($event, $lid, $arFields, $arFields['FILE']);

            /* Change links to disable sending message on next hit */
            return false;
        }
    }

    function OnBeforeEventSendHandler($arFields, $arTmplc)
    {
        if ($arFields["LAST_NAME"] && $arFields["LAST_NAME"] != '') {
            $arFields["NAME"] .= ' ' . $arFields["LAST_NAME"];
        }
    }

    function BeforePostingSendMailHandler($arFields)
    {
        $unsubscribe = '';

        $rs = CSubscription::GetByEmail($arFields["EMAIL"]);
        if ($ar = $rs->Fetch()) {
            $unsubscribeTop    = '--------------------------------------------------------';
            $unsubscribeText   = 'Вы можете отписаться от рассылки, перейдя по ссылке:'    ;
            $unsubscribeLink   = 'http://ebazaar.ru/'                                      ;
            $unsubscribeLink  .= '?SUBSCRIPTION_ID=' . $ar["ID"]                           ;
            $unsubscribeLink  .= '&SUBSCRIPTION_CONFIRM_CODE=' . $ar["CONFIRM_CODE"]       ;
            $unsubscribeLink  .= '&SUBSCRIBE_ACTION=UNSUBSCRIBE'                           ;
            $unsubscribeBottom = '--------------------------------------------------------';
        }

        $arFields["BODY"] = str_replace("#UNSUBSCRIBE_TOP#",    $unsubscribeTop,    $arFields["BODY"]);
        $arFields["BODY"] = str_replace("#UNSUBSCRIBE_TEXT#",   $unsubscribeText,   $arFields["BODY"]);
        $arFields["BODY"] = str_replace("#UNSUBSCRIBE_LINK#",   $unsubscribeLink,   $arFields["BODY"]);
        $arFields["BODY"] = str_replace("#UNSUBSCRIBE_BOTTOM#", $unsubscribeBottom, $arFields["BODY"]);

        return $arFields;
    }
}

function addTabMailTemaplates(&$form)
{
	if($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/posting_edit.php")
	{
		$form->tabs[] = array(
            "DIV" => "tab_edit_templates",
            "TAB" => "Шаблоны писем",
            "ICON"=>"main_user_edit",
            "TITLE"=>"Типовые шаблоны писем",
            "CONTENT"=>
                '<tr valign="top">
                    <td>Тема:</td>
                    <td>
                        <input type="text" name="template_subject" value="Новости от eBazaar" size="60"><br>
                    </td>
                </tr>
                <tr valign="top">
                    <td colspan="2">
                        <textarea name="template_body" style="width:100%; height:400px;">
<h3>Доброго дня, партнеры.</h3>
<p>

У&nbsp;нас хорошие новости:<br>

<Здесь не шаблонный краткий текст, набираемый вручную. Всю новость копировать не нужно.>

</p>

<p>Подробнее&nbsp;&mdash; у&nbsp;нас на&nbsp;сайте: <a href="http://ebazaar.ru/news/"><ссылка на новость></a></p>

<p>Вы&nbsp;всегда можете отписаться от&nbsp;новостей, для этого просто перейдите по&nbsp;<a href="#UNSUBSCRIBE_LINK#">ссылке</a>.</p>
<hr>
<i>Ваш eBazaar,<br>
дистрибьютор канцелярии лучших марок.<br><br>
Всегда на&nbsp;связи:: <br>
e-mail: <a href="mailto:info@ebazaar.ru">info@ebazaar.ru</a><br>
Телефон: <span class="phone">+7-495-775-51-10 </span><br>
Факс: <span class="phone">+7-495-775-51-09</span><br>
<strong>Сделайте заказ: <a href="http://ebazaar.ru/">ebazaar.ru</a></strong></i>
                        </textarea>
                    </td>
                </tr>
                <tr valign="top">
                	<td colspan="2"><hr></td>
                </tr>
                <tr valign="top">
                	<td>Тема:</td>
                	<td><input type="text" name="template_subject" value="Предлагаем сделать заказ по акции" size="60"><br></td>
                </tr>
                <tr valign="top">
                    <td colspan="2">
                        <textarea name="template_body" style="width:100%; height:400px;">
<h3>Доброго дня, партнеры.</h3>
<p>

У&nbsp;нас снова хорошие новости:<br>

<Здесь нешаблонный текст акции, специального предложения, информации о новой скидке.>

</p>

<p>Подробнее&nbsp;&mdash; у&nbsp;нас на&nbsp;сайте: <a href="http://ebazaar.ru/news/"><ссылка на новость></a></p>

<p>Вы&nbsp;всегда можете отписаться от&nbsp;новостей, для этого просто перейдите по&nbsp;<a href="#UNSUBSCRIBE_LINK#">ссылке</a>.</p>
<hr>
<i>Ваш eBazaar,<br>
дистрибьютор канцелярии лучших марок.<br><br>
Всегда на&nbsp;связи:: <br>
e-mail: <a href="mailto:info@ebazaar.ru">info@ebazaar.ru</a><br>
Телефон: <span class="phone">+7-495-775-51-10 </span><br>
Факс: <span class="phone">+7-495-775-51-09</span><br>
<strong>Сделайте заказ: <a href="http://ebazaar.ru/">ebazaar.ru</a></strong></i>                          
                        </textarea>
                    </td>
                </tr>
                '
		);
	}
}