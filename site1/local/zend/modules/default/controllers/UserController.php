<?php
/**
 * Class UserController
 */
class UserController extends Quickpay_Controller {

    /**
     * Форма авторизации
     * если существует параметр 'change_password'
     * произведем внутренний редирект на восстановление пароля
     */
    public function authAction() {
        if ($this->getParam('change_password') == 'yes') {
            return $this->forward('change-password');
        }

        $form = new Quickpay_Form_Auth();
        /**
         * восстановим значения form из сессии
         */
        $form->restoreContext();

        $this->view->headTitle('Авторизация');
        $this->view->form = $form;
    }

    /**
     * обработка формы авторизации
     */
    public function authProcessAction() {
        $form = new Quickpay_Form_Auth();

        /**
         * очистим сохраненные значения в сессии
         */
        $form->clearContext();
        if ($form->isValid($this->getAllParams())) {
            $model  = new Quickpay_Model_User();
            $result = $model->loginByEmail($form->getValue('login'), $form->getValue('password'));
            if (true === $result) {
                /**
                 * если прошла валидация и все действия успешны
                 * редирект на страницу благодарности/подтверждения/успеха и т.п.
                 */
                $this->redirect($this->_helper->url->url(array(), 'userAuthSuccess', true));
                return;
            } else {
                $form->getElement('login')->addErrors($result);
                $form->markAsError();
            }
        }

        /**
         * если валидация не прошла и не произошло перехода
         * на страницу подтвеждения принятия формы
         * сохраняем текущий контекст формы (ошибки и значения)
         * возвращяемся на экшен вывода формы
         */
        $form->storeContext();
        $this->redirect($this->_helper->url->url(array(), 'userAuth', true));
    }

    /**
     * сообщение об успешной отправке формы
     */
    public function authSuccessAction() {}

    /**
     * форма запроса на восстановления пароля
     * обработка формы сдесь же
     *
     * если во вьюхе будет форма выведется она
     * если нет, то выведется сообщение об успешной отправке формы
     */
    public function passwordRemindAction() {
        $form = new Quickpay_Form_Remind();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getAllParams())) {
                /**
                 * отправка письма на введеный емейл
                 * NOTICE:
                 * во вьюху не пробрасывается форма
                 */
                $model = new Quickpay_Model_User();
                return $model->remindPassword($form->getValue('email'));
            }
        }

        $this->view->headTitle('Восстановление пароля');

        /**
         * если форма не отправлялась
         * либо произошла ошибка валидации
         *
         * в вьюху пробрасываем объект формы
         */
        $this->view->form = $form;
    }

    /**
     * форма восстановления пароля
     */
    public function changePasswordAction() {
        $form = new Quickpay_Form_ChangePassword();

        if ($this->getRequest()->isPost()) {
            /**
             * валидируются параметры
             */
            if ($form->isValid($this->getAllParams())) {
                $model  = new Quickpay_Model_User();
                $result = $model->changePassword(
                    $form->getValue('USER_LOGIN'),
                    $form->getValue('USER_CHECKWORD'),
                    $form->getValue('new_password'));

                /**
                 * попытка сменить пароль через модель пользователя
                 */
                if (true === $result) {
                    /**
                     * если все ок
                     * отрендерим шаблон views/scripts/user/change-password-success.phtml
                     * и завершим обработку ответа
                     */
                    $this->render('change-password-success');
                    return;
                }

                if (is_array($result)) {
                    /**
                     * если вернулся набор ошибок пробрасываем их во вьюху
                     */
                    $this->view->errors = $result;
                }

                /**
                 * в случае ошибки при смене пароля
                 * отрендерим шаблон views/scripts/user/change-password-fail.phtml
                 */
                $this->render('change-password-fail');
                return;
            }
        }

        /**
         * если не пройдена валидация, либо форма не отправлялась
         * будет отрендерен дефолтный шаблон (views/scripts/user/change-password.phtml)
         * в него пробрасываем объект формы
         */
        $this->view->form = $form;
    }
}
