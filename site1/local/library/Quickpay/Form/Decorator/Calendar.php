<?php
class Quickpay_Form_Decorator_Calendar extends Zend_Form_Decorator_Abstract
{

    /**
     * Получение кода ссылки и изображения каледаря. Настройка календаря
     *
     * @return string
     */
    private function _getCalendarLink()
    {
        $showTime = $this->getOption('showTime');
        $calendarLink = '
            <button type="button" id="' . $this->getElement()->getName() . '_calendar">...</button>
            <script type="text/javascript">
                Calendar.setup(
                  {
                    inputField  : "' . $this->getElement()->getAttrib('id') . '",
                    button      : "' . $this->getElement()->getName() . '_calendar",
                    ifFormat    : "%d.%m.%Y'.($showTime ? ' %H:%M':'').'",
                    daFormat    : "%d.%m.%Y'.($showTime ? ' %H:%M':'').'"
                    '.
                    ($showTime ?
                    ',showsTime  : true,
                    timeFormat    : "24"':'')
                    .'
                  }
                );
            </script>
			';

        return $calendarLink;
    }


    /**
     * Рендеринг декоратора
     *
     * @param string $content
     * @return string
     */
    public function render($content)
    {
        // Получаем объект элемента к которому применяется декоратор
        $element = $this->getElement();
        if (!$element instanceof Zend_Form_Element) {
            return $content;
        }
        // Проверяем объект вида зарегистрированного для формы
        if (null === $element->getView()) {
            return $content;
        }

        // Расположение декоратора, "после" или "перед" элементом, по умолчанию "после"
        $placement = $this->getPlacement();
        // Разделитель между элементом и декоратором
        $separator = $this->getSeparator();

        // Взависимости от настроек расположения декоратора возвращаем содержимое
        switch  ($placement) {
            // После элемента
            case  'APPEND':
                return $content . $separator . $this->_getCalendarLink();
            // Перед элементом
            case  'PREPEND':
                return $this->_getCalendarLink() . $separator . $content;
            case  null:
            // По умолчанию просто возвращаем содержимое календаря
            default:
                return $this->_getCalendarLink();
        }

    }

}