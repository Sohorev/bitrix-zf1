<?php

/*
 * @property int ID
 * Class Quickpay_Model_Bitrix_Webform
 */

class Quickpay_Model_Bitrix_Webform extends Quickpay_Model_Bitrix {
    /**
     * list field description of webform
     *
     * @var array $_cform_data
     */
    private $_cform_data = null;

    /**
     * list fields of webform
     *
     * @var array
     */
    private $_cform_elements = null;

    /**
     * Last error messages
     * @var string
     */
    private $_lastErrors = array();

    public function __construct($id, $config = array()) {
        if (!CModule::IncludeModule("form")) {
            throw new Zend_Exception('fail load bitrix module WebForm');
        }

        parent::__construct($config);

        if (!$this->getById($id)) {
            throw new Zend_Exception('webform not found');
        }
    }

    /**
     * @return mixed|void
     */
    public function init() {

    }

    /**
     * load webform by ID
     *
     * @param $id
     * @return bool
     */
    public function getById($id) {
        $cacheId = __METHOD__ . $id;
        $this->_cform_data = $this->_getCache($cacheId);
        if (is_array($this->_cform_data)) {
            return true;
        }

        $dbRes = CForm::GetByID($id);
        if ($arRes = $dbRes->Fetch()) {
            $this->_cform_data = $arRes;
            $this->_setCache($cacheId, $this->_cform_data);

            $this->_cform_elements = array();
            return true;
        }

        return false;
    }

    public function isUseCaptcha() {
        return $this->USE_CAPTCHA == 'Y';
    }

    /**
     * return field webform
     *
     * @param $key
     * @return mixed
     * @throws Zend_Exception
     */
    public function __get($key) {
        if (!isset($this->_cform_data[$key])) {
            throw new Zend_Exception('webform field [' . $key . '] not found');
        }

        return $this->_cform_data[$key];
    }

    /**
     * gets list questions on webform
     * load questiuons if questions list not loaded
     *
     * @return array
     */
    public function getElements() {
        if (!$this->_cform_elements) {
            $cacheId = __METHOD__ . $this->ID;

            $this->_cform_elements = $this->_getCache($cacheId);
            if (!is_array($this->_cform_elements)) {
                $rsElements = CFormField::GetList($this->ID, 'ALL', $by, $order, ["ACTIVE" => "Y"], $is_filtered);
                while ($arElement = $rsElements->Fetch()) {
                    $this->_cform_elements[$arElement['SID']] = $arElement;
                }
                $this->_loadAnswers();

                $this->_setCache($cacheId, $this->_cform_elements);
            }
        }

        return $this->_cform_elements;
    }

    /**
     * load answers for questions and add list to question data
     *
     * @return $this
     */
    private function _loadAnswers() {
        foreach ($this->_cform_elements as $ind => $element) {

            $rsAnswers = CFormAnswer::GetList(
                    $element['ID'], $by, $order, array('ACTIVE' => 'Y'), $is_filtered
            );

            $answers = array();
            while ($arAnswer = $rsAnswers->Fetch()) {
                $answers[] = $arAnswer;
            }

            $this->_cform_elements[$ind]['ANSWERS'] = $answers;
        }
        return $this;
    }

    /**
     * @param $key
     * @return mixed
     * @throws Zend_Exception
     */
    public function getElement($key) {
        if (!isset($this->_cform_elements[$key])) {
            throw new Zend_Exception('webform element [' . $key . '] not found');
        }

        return $this->_cform_elements[$key];
    }

    /**
     * validate request params on webform
     * returning array validate errors
     *
     * @param $data
     * @return array
     */
    public function check($data) {
        return CForm::Check($this->ID, $data, false, 'Y', 'Y');
    }

    /**
     * save new result webform
     *
     * @param $data
     * @return int|bool
     */
    public function addResult($data) {
        global $strError;
        $strError = '';
        $this->clearErrors();

        $result = CFormResult::Add($this->ID, $data);

        if ($result !== false) {
            CFormCRM::onResultAdded($this->ID, $result);
            CFormResult::SetEvent($result);
            CFormResult::Mail($result);
        }

        if ($result === false && empty($strError)) {
            $strError = 'Ошибка сохранения формы';
        }

        $this->setLastErrors($strError);

        return $result;
    }

    protected function clearErrors() {
        $this->_lastErrors = array();
    }

    protected function setLastErrors($message) {
        $messages = preg_split('/<br\s*\/?>/', $message, null, PREG_SPLIT_NO_EMPTY);
        $this->_lastErrors = $messages;
    }

    public function getLastErrors() {
        return $this->_lastErrors;
    }

    //// Quickpay admin methods start
    public function asParams() {

        $params = "";
        foreach ($this->getElements() as $element) {

            if ($element->getBelongsTo()) {
                if (is_array($element->getValue())) {

                    $values = $element->getValue();
                    foreach ($values as $value) {

                        $params .= $element->getBelongsTo() . '[' . $element->getName() . '][]=' . $value . '&';
                    }
                } else {

                    $params .= $element->getBelongsTo() . '[' . $element->getName() . ']=' . $element->getValue() . '&';
                }
            }
        }
        return trim($params, "&");
    }

    /**
     * Заменит ',' на '.' у тех элементов которые имеют валидатор Float
     * Уберет фильтр Float у этих элементов, т.к. он срабатывает не только перед валидацией
     */
    public function getValues($suppressArrayNotation = false) {

        $elements = $this->getAllElements($this);
        foreach ($elements as $element) {

            $validators = $element->getValidators();
            if (count($validators)) {

                foreach ($validators as $validator) {

                    if (is_a($validator, 'Zend_Validate_Float')) {

                        $element->setValue(str_replace(",", '.', $element->getValue()));
                        $filters = $element->getFilters();
                        unset($filters["QP_Filter_Float"]);
                        $element->setFilters($filters);
                    }
                }
            }
        }

        $values = parent::getValues($suppressArrayNotation);

        foreach ($this->_emptyToNullFields as $fieldName) {
            if (isset($values[$fieldName]) && $values[$fieldName] === "") {
                $values[$fieldName] = null;
            }
        }

        return $values;
    }

    public function getValuesForFilterValidate($suppressArrayNotation = false) {

        $values = parent::getValues($suppressArrayNotation);

        foreach ($this->_emptyToNullFields as $fieldName) {
            if (isset($values[$fieldName]) && $values[$fieldName] === "") {
                $values[$fieldName] = null;
            }
        }

        return $values;
    }

    public function getAllElements($form = null) {

        if ($form == null) {

            $form = $this;
        }
        $elements = array_values($form->getElements());
        $forms = $form->getSubForms();
        if (count($forms)) {

            foreach ($forms as $f) {

                $elements = array_merge($elements, $this->getAllElements($f));
            }
        }

        return $elements;
    }

    /**
     * Бежит по всем полям и снимает обязательность поля
     */
    public function setFieldsNotRequired() {

        $elements = $this->getAllElements();

        foreach ($elements as $element) {

            $element->setRequired(false);
        }
    }

    public function setEmptyToNullFields($emptyToNullFields) {
        $this->_emptyToNullFields = $emptyToNullFields;
    }
    //// Quickpay admin methods end
}
