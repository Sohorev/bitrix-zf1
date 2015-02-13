<?php
/**
 * @author sohorev
 */
class Quickpay_Form_Decorator_Clear extends Zend_Form_Decorator_Abstract {

    /**
     * Default placement: surround content
     * @var string
     */
    protected $_placement = null;

    /**
     * Render
     *
     * Renders as the following:
     * $content<div class="clear"></div>
     *
     * @param  string $content
     * @return string
     */
    public function render($content) {

        return $content . '<div class="clear"></div>';
    }
}
