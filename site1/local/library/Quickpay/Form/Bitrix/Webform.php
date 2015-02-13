<?php

/**
 * Class Quickpay_Form_Bitrix_Webform
 */
class Quickpay_Form_Bitrix_Webform extends Quickpay_Form_Abstract {
    /**
     * @var Quickpay_Model_Bitrix_Webform
     */
    private $_model;

    /**
     * Помещать ли лабел внутрь поля ввода (placeholder)
     * @var boolean
     */
    private $_labelToPlaceholder = false;

    /**
     * Загружать ли форму в конструкторе
     * @var boolean
     */
    private $_autoLoad = true;

    /**
     * Добавлять ли кнопку отправить
     * @var boolean
     */
    private $_addSubmit = true;

    /**
     * Если null то форма без битрикс аякс, если не null содержит CAjax::GetComponentID
     * @var boolean
     */
    private $_bitrixAjaxId = null;

    /**
     * Зеленые сообщения над формой
     * @var array
     */
    private $_notes = [];

    /**
     * @var int $_webFormId
     */
    protected $_webFormId;

    /**
     * @param array|Zend_Config|null $options
     * @throws Zend_Exception
     */
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->getDecorators();
        $this->setDecorators([
            'CommonFormErrors',
            'CommonFormNotes',
            'FormElements',
            array('HtmlTag', array('tag' => 'div', 'class' => 'form')),
            'Form',
            array('Clear', array()),
        ]);
    }

    /**
     * Set form state from options array
     *
     * @param  array $options
     * @throws Zend_Exception
     * @return Zend_Form
     */
    public function setOptions(array $options) {
        parent::setOptions($options);

        if (isset($options["webFormId"])) {
            $this->setWebformId($options["webFormId"]);
            $this->setAttrib("id", $options["webFormId"]);
        }
        if (isset($options["labelToPlaceholder"])) {
            $this->setLabelToPlaceholder($options["labelToPlaceholder"]);
        }
        if (isset($options["autoLoad"])) {
            $this->setAutoLoad($options["autoLoad"]);
        }
        if (isset($options["addSubmit"])) {
            $this->setAddSubmit($options["addSubmit"]);
        }
        if (isset($options["bitrixAjaxId"])) {
            $this->setBitrixAjaxId($options["bitrixAjaxId"]);
        }

        if ($this->_autoLoad) {
            // Prevent double load
            $this->_autoLoad = false;

            if (!$this->loadWebForm()) {
                throw new Zend_Exception('not load webform');
            }
        }

        return $this;
    }

    /**
     * load webform model with element
     *
     * @return bool
     */
    public function loadWebForm() {
        if ($this->_model) {
            return true;
        }
        if (!$this->_webFormId) {
            return false;
        }

        $this->_model = new Quickpay_Model_Bitrix_Webform($this->_webFormId);
        foreach ($this->_model->getElements() as $question) {
            $this->buildElement($question);
        }

        $this->buildCaptchaElement();

        if ($this->_addSubmit) {
            $submit = new Quickpay_Form_Element_QpButtonSubmit('web_form_submit', [
                'label' => $this->_model->BUTTON,
            ]);
            $this->addElement($submit);
        }

        $this->addElement(new Zend_Form_Element_Hidden("sessid", [
            "value" => bitrix_sessid(),
            "decorators" => ["ViewHelper"]
        ]));

        if ($this->_bitrixAjaxId !== null) {
            $this->addElement(new Zend_Form_Element_Hidden("bxajaxid", [
                "value" => $this->_bitrixAjaxId,
                "decorators" => ["ViewHelper"]
            ]));
            $this->setAttrib("onsubmit", "return jsAjaxUtil.InsertFormDataToNode(this, 'comp_" . $this->_bitrixAjaxId . "', true)");
        }

        $this->setDescription($this->_model->NAME);

        return true;
    }

    /**
     * @param $options
     * @return $this
     * @throws Zend_Exception
     */
    public function buildElement($options) {
        $method = 'build' . ucfirst(strtolower($options['ANSWERS'][0]['FIELD_TYPE'])) . 'Element';
        if (method_exists($this, $method)) {
            $this->$method($options);
        } else {
            throw new Zend_Exception("add new method '$method' to class '" . __CLASS__ . "' for convert current field type to Zend element");
        }

        return $this;
    }

    protected function getElementOptions($options) {

        $elementOptions = [
            'label' => $options['TITLE'],
            'required' => $options['REQUIRED'] === 'Y',
            'description' => $options['COMMENTS'],
        ];
        if ($this->_labelToPlaceholder) {
            $elementOptions['placeholder'] = $options['TITLE'];
            $elementOptions['removeLabelDecorator'] = true;
        }
        return $elementOptions;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildHiddenElement($options) {
        $elementOptions = array(
//            'label' => $options['TITLE'],
//            'required' => $options['REQUIRED'] === 'Y'
        );
        $name = 'form_hidden_' . $options['ANSWERS'][0]['ID'];
        $element = new Zend_Form_Element_Hidden($name, $elementOptions);
        $this->addElement($element);

        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildFileElement($options) {
        $name = 'form_file_' . $options['ANSWERS'][0]['ID'];
        $element = new Quickpay_Form_Element_QpFile($name, $this->getElementOptions($options));
        $this->addElement($element);

        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildTextElement($options) {
        $name = 'form_text_' . $options['ANSWERS'][0]['ID'];
        $element = new Quickpay_Form_Element_QpText($name, $this->getElementOptions($options));
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildDateElement($options) {
        $name = 'form_date_' . $options['ANSWERS'][0]['ID'];
        $element = new Quickpay_Form_Element_QpText($name, $this->getElementOptions($options));
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildEmailElement($options) {
        $name = 'form_email_' . $options['ANSWERS'][0]['ID'];
        $element = new Quickpay_Form_Element_QpText($name, $this->getElementOptions($options));
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildTextareaElement($options) {
        $name = 'form_textarea_' . $options['ANSWERS'][0]['ID'];
        $element = new Quickpay_Form_Element_QpTextarea($name, $this->getElementOptions($options));
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildCheckboxElement($options) {

        $elementOptions = $this->getElementOptions($options);
        $elementOptions['multiOptions'] = array();
        foreach ($options['ANSWERS'] as $answer) {
            $elementOptions['multiOptions'][$answer['ID']] = $answer['MESSAGE'];
        }

        $name = 'form_checkbox_' . $options['SID'];
        $element = new Zend_Form_Element_MultiCheckbox($name, $elementOptions); // Quickpay_Form_Element_Qp?
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildDropdownElement($options) {

        $elementOptions = $this->getElementOptions($options);
        $elementOptions['multiOptions'] = array();
        foreach ($options['ANSWERS'] as $answer) {
            $elementOptions['multiOptions'][$answer['ID']] = $answer['MESSAGE'];
        }

        $name = 'form_dropdown_' . $options['SID'];
        $element = new Quickpay_Form_Element_QpSelect($name, $elementOptions);
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildRadioElement($options) {

        $elementOptions = $this->getElementOptions($options);
        $elementOptions['multiOptions'] = array();
        foreach ($options['ANSWERS'] as $answer) {
            $elementOptions['multiOptions'][$answer['ID']] = $answer['MESSAGE'];
        }

        $name = 'form_radio_' . $options['SID'];
        $element = new Quickpay_Form_Element_QpRadio($name, $elementOptions);
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $options
     * @return $this
     */
    public function buildMultiselectElement($options) {

        $elementOptions = $this->getElementOptions($options);
        $elementOptions['multiOptions'] = array();
        foreach ($options['ANSWERS'] as $answer) {
            $elementOptions['multiOptions'][$answer['ID']] = $answer['MESSAGE'];
        }

        $name = 'form_multiselect_' . $options['SID'];
        $element = new Quickpay_Form_Element_QpSelectMulti($name, $elementOptions);
        $this->addElement($element);
        return $element;
    }

    public function buildCaptchaElement() {

        if (!$this->_model->isUseCaptcha()) {
            return null;
        }

        $elementOptions = $this->getElementOptions([
            "TITLE" => "Введите символы с картинки",
            "REQUIRED" => 'Y',
        ]);

        $name = 'captcha_word';

        $captcha = new Quickpay_Form_Element_QpCaptcha($name, $elementOptions);
        $this->addElement($captcha);

        return $captcha;
    }

    /**
     * @param $sid
     * @return bool|string
     */
    public function getFieldNameBySid($sid) {
        foreach ($this->_model->getElements() as $element) {
            if ($element['SID'] === (string) $sid) {
                $answer = $element['ANSWERS'][0];
                switch ($answer['FIELD_TYPE']) {
                    case 'multiselect':
                    case 'dropdown':
                    case 'radio':
                    case 'checkbox': {
                            return "form_" . $answer['FIELD_TYPE'] . "_" . $element['SID'];
                        }
                    default: {
                            return "form_" . $answer['FIELD_TYPE'] . "_" . $answer['ID'];
                        }
                }
            }
        }

        return false;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function isValid($data) {
        parent::isValid($data);

        if ($this->isArray()) {
            $eBelongTo = $this->getElementsBelongTo();
            $data = $this->_dissolveArrayValue($data, $eBelongTo);
        }

        $result = $this->_model->check($data);
        $formErrors = [];
        foreach ($result as $sid => $message) {
            $fieldName = $this->getFieldNameBySid($sid);
            if ($fieldName !== false && $this->getElement($fieldName) !== null) {
                $this->getElement($fieldName)->setErrors(array($message));
            } elseif ($message == "Неверно введены символы с картинки") {
                $this->getElement("captcha_word")->setErrors(array($message));
            } else {
                $formErrors[] = $message;
            }
        }
        $this->setErrors($formErrors);

        if (!empty($result)) {
            $this->markAsError();
        }

        return !$this->hasErrors();
    }

    /**
     * @return int
     */
    public function getWebFormId() {
        return $this->_webFormId;
    }

    /**
     * set webform id
     * @param $id
     */
    public function setWebformId($id) {
        $this->_webFormId = $id;
    }

    /**
     * @return Quickpay_Model_Bitrix_Webform
     */
    public function getModel() {
        return $this->_model;
    }

    public function setLabelToPlaceholder($labelToPlaceholder) {
        $this->_labelToPlaceholder = $labelToPlaceholder;
    }

    public function setAutoLoad($autoLoad) {
        $this->_autoLoad = $autoLoad;
    }

    public function setAddSubmit($addSubmit) {
        $this->_addSubmit = $addSubmit;
    }

    public function setBitrixAjaxId($bitrixAjaxId) {
        $this->_bitrixAjaxId = $bitrixAjaxId;
    }

    public function setNotes($notes) {
        $this->_notes = $notes;
    }

    public function addNote($note) {
        $this->_notes[] = $note;
    }

    public function getNotes() {
        return $this->_notes;
    }
}
