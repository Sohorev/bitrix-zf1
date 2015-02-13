<?php

include_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/captcha.php");

/**
 * Свой текстовый элемент, для того, чтобы рисовать звездочку для необходимых элементов
 *
 * @author sohorev
 */
class Quickpay_Form_Element_QpCaptcha extends Zend_Form_Element_Text {

    private $_captchaSid = "";

    /**
     * Load default decorators
     *
     * @return Zend_Form_Element
     */
    public function loadDefaultDecorators() {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $this->addPrefixPath('Quickpay_Form_Decorator_', 'Quickpay/Form/Decorator/', 'decorator');

        $cpt = new CCaptcha();
        $cpt->SetCodeLength(6);  //устанавливаем длину кода на картинке
        // Код ниже можно поместить в кастомную капча captcha2.php
//        $cpt->SetImageSize(90,30);
//        $cpt->SetEllipsesNumber(10); //отключил фон из кружочков, и линии поверх изображения
//        $cpt->SetLinesNumber(20);
//        $cpt->SetWaveTransformation(true);
//        $cpt->SetTextWriting($cpt->angleFrom, $cpt->angleTo, 5, 10, 16, 18);
        $cpt->SetCode();

        $this->_captchaSid = $cpt->GetSID();

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this
                ->addDecorator('ViewHelper')
                ->addDecorator('Errors')
                ->addDecorator('HtmlTag', array(
                    'tag' => 'div',
                    'class' => 'inputElement',
                    'id' => array('callback' => array(get_class($this), 'resolveElementId'))
                ))
                ->addDecorator('Label', array(
                    'tag' => 'div',
                    'tagClass' => 'inputLabel',
                    'requiredSuffix' => '&nbsp;<span class="asterisk">*</span>',
                    'escape' => false
                ))
                ->addDecorator(["img" => "HtmlTag"], array(
                    "tag" => "img",
                    'src' => '/bitrix/tools/captcha.php?captcha_sid=' . htmlspecialcharsbx($this->_captchaSid),
                ))
                ->addDecorator(["input" => "HtmlTag"], array(
                    "tag" => "input",
                    "type" => "hidden",
                    "name" => "captcha_sid",
                    "value" => $this->_captchaSid,
                ))
                ->addDecorator('ElementWrapper')
                ->addDecorator('Clear', array())
            ;
        }
        if (isset($this->removeLabelDecorator) && $this->removeLabelDecorator) {
            $this->removeDecorator("Label");
        }

        return $this;
    }

    public function getCaptchaSid() {
        return $this->_captchaSid;
    }
}
