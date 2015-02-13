<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

Quickpay_ZendManager::Bootstrap();
$labelToPlaceholder = true;
if (isset($arParams["LABEL_TO_PLACEHOLDER"])) {
    if ($arParams["LABEL_TO_PLACEHOLDER"] == "N" || $arParams["LABEL_TO_PLACEHOLDER"] == "No") {
        $labelToPlaceholder = false;
    }
}

$formParams = [
    "webFormId" => $arParams["WEB_FORM_ID"],
    "labelToPlaceholder" => $labelToPlaceholder,
];

if ($arParams["AJAX_MODE"] === "Y") {
//    CJSCore::Init("ajax");
    CAjax::Init();
    $bitrixAjaxId = CAjax::GetComponentID('quickpay:form', '.default', "");
    $formParams["bitrixAjaxId"] = $bitrixAjaxId;
}

$form = new Examples_Form_Feedback($formParams);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($form->isValid($_REQUEST)) {

        // check user session
        if (check_bitrix_sessid()) {
            // add result
            $resultId = $form->getModel()->addResult($form->getValues());
            if ($resultId) {
                $arResult["FORM_RESULT"] = 'addok';
                // send email notifications
                CFormCRM::onResultAdded($arParams["WEB_FORM_ID"], $resultId);
                CFormResult::SetEvent($resultId);
                // CFormResult::Mail($resultId); // два раза отправит почту, если мы повисли не евент через админку - и уже шлем почту
            }

            if ($arParams["AJAX_MODE"] === "Y") {
                $formNote = str_replace("#RESULT_ID#", $resultId, GetMessage('FORM_NOTE_ADDOK'));
                $form->addNote($formNote);
            } else {
                if (!empty($arParams['SUCCESS_URL'])) {
                    return LocalRedirect($arParams['SUCCESS_URL']);
                } else {
                    return LocalRedirect($_SERVER["REQUEST_URI"]);
                }
            }
        }
    }
}

if ($form->getElement("captcha_word") !== null) {
    $form->getElement("captcha_word")->setValue("");
}

$arResult["form"] = $form;

$this->IncludeComponentTemplate();
