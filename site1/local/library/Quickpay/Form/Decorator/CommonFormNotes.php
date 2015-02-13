<?php
/**
 * @author sohorev
 */
class Quickpay_Form_Decorator_CommonFormNotes extends Zend_Form_Decorator_Abstract {

    /**
     * Default placement: surround content
     * @var string
     */
    protected $_placement = null;

    /**
     * @param  string $content
     * @return string
     */
    public function render($content) {

        $element  = $this->getElement();
        $messages = $element->getNotes();
        if (empty($messages)) {
            return $content;
        }
        $str = "<font class='notetext'>";
        foreach ($messages as $message) {
            $str .= $message . "<br/>";
        }
        $str .= "</font>";

        return $str . $content;
    }
}
