<?php
/**
 * Class Examples_UserController
 * @property Quickpay_Controller_Response_HttpBitrix $_response
 **/
class Examples_UserController extends Quickpay_Controller_Abstract {

    /**
     * для экшена auth-popup
     * в случае ajax запроса
     * обязываем отвечать в json
     */
    public function init() {
        $this->_helper->getHelper('QuickpayAjaxContext')
            ->addActionContext('auth-popup', 'json')
            ->initContext('json');
    }

    /**
     * AJAX SAMPLE
     * очень похоже на passwordRemindAction
     * отличия
     * 1. $form->setAction т.к. в самой форме назначается другой обработчик
     *    обработка формы перенаправлена на этот-же экшен
     * 2. в случае ajax запросов данного экшена
     *    не будет рендера дефолтного шаблона (views/scripts/user/auth-popup.phtml)
     *    в методе init() для этого случая прописано правило - addActionContext('auth-popup', 'json')
     *    !!!
     *    ответ будет состоять из сериализованных в JSON переменных которые добавлены во вьюху
     *    в данном случае это $this->view->form = $form;
     *    форма сериализуется в массив ошибок
     */
    public function authPopupAction() {
        $form = new Quickpay_Form_Auth();
        $form->setAction($this->_helper->url->url(array('module' => 'examples', 'controller' => 'user', 'action' => 'auth-popup')));

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getAllParams())) {
                $model  = new Quickpay_Model_User();
                $result = $model->loginByEmail($form->getValue('login'), $form->getValue('password'));
                if (true !== $result) {
                    $form->getElement('login')->addErrors($result);
                    $form->markAsError();
                }
            }
        }

        $this->view->form = $form;
    }
    /**
     * END AJAX SAMPLE
     */
}
