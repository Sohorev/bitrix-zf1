<?php

class Quickpay_Form_CustomCaptcha extends Quickpay_Form_Bitrix_Webform {

    public function buildCaptchaElement() {

        $captcha = parent::buildCaptchaElement();
        $captcha->setDecorators([['ViewScript', [
            'viewScript' => 'decorators/captcha.phtml',
            'placement' => false,
            'class' => 'elementWrap',
        ]]]);

        return $captcha;
    }
}
