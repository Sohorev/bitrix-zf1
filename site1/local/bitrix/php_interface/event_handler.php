<?php

// Добавление обработчика события
//AddEventHandler("sale", "OnOrderNewSendEmail", "bxModifySaleMails");

//-- Собственно обработчик события
if (!CModule::includeModule('sale')) {
    return;
}

function bxModifySaleMails($orderID, &$eventName, &$arFields) {

    $arOrder = CSaleOrder::GetByID($orderID);

    //-- получаем телефоны и адрес
    $arOrder = CSaleOrder::GetByID($orderID);
    $order_props = CSaleOrderPropsValue::GetOrderProps($orderID);
    # Свойства заказ, которые должны быть извлечены дополнительно
    $addingCode = array(
        "PHONE",
        "LOCATION",
        "INDEX",
        "ADDRESS"
    );
    while ($arProps = $order_props->Fetch()) {
        if (in_array($arProps["CODE"], $addingCode) && $arProps["VALUE"] != '') {
            $arFields['EXT_' . $arProps["CODE"]] = $arProps["VALUE"];
        }
    }
    $arLocs = CSaleLocation::GetByID($arFields["EXT_LOCATION"]);
    $fullAddress = "";
    if (!empty($arFields['EXT_INDEX'])) {
        $fullAddress .= $arFields['EXT_INDEX'] . ", ";
    }
    $fullAddress .= $arLocs["COUNTRY_NAME"] . "-" . $arLocs["CITY_NAME"] . ", " . $arFields["EXT_ADDRESS"];

    //-- получаем название службы доставки
    $arDeliv = CSaleDelivery::GetByID($arOrder["DELIVERY_ID"]);
    $deliveryName = "";
    if ($arDeliv) {
        $deliveryName = $arDeliv["NAME"];
    }

    //-- получаем название платежной системы
    $arPaySystem = CSalePaySystem::GetByID($arOrder["PAY_SYSTEM_ID"]);
    $paySystemName = "";
    $arFields["DETAILS_FOR_PAYMENT"] = '';
    $arFields["ATTACHED_FILES"] = '';
    if ($arPaySystem) {
        $paySystemName = $arPaySystem["NAME"];
        if ($paySystemName == 'Оплата по квитанции') {
            $arFields["DETAILS_FOR_PAYMENT"] = 'Внимание! Реквизиты для оплаты во вложении!';
            $file = file_get_contents('http://grillver.ru/personal/order/payment/index.php?ORDER_ID=' . $orderID . '&pdf=1&DOWNLOAD=Y&USER_ID=' . $arOrder['USER_ID']);
            file_put_contents(__DIR__ . '/../..' . str_replace('/local/', '/', SITE_TEMPLATE_PATH) . '/docs/doc' . $orderID . '.html', $file);
            $arFields["ATTACHED_FILES"] = str_replace('/local/', '/', SITE_TEMPLATE_PATH) . '/docs/doc' . $orderID . '.html';
        }
    }

    //-- добавляем новые поля в массив результатов
    $arFields["ORDER_DESCRIPTION"] = $arOrder["USER_DESCRIPTION"];
    $arFields["PHONE"] = $arFields['EXT_PHONE'];
    $arFields["DELIVERY_NAME"] = $deliveryName;
    $arFields["PAY_SYSTEM_NAME"] = $paySystemName;
    $arFields["FULL_ADDRESS"] = $fullAddress;
}
