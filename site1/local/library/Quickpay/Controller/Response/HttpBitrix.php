<?php

class Quickpay_Controller_Response_HttpBitrix extends Zend_Controller_Response_Http {

    /**
     * Whether or not to stop Bitrix engine; off by default
     * @var boolean
     */
    protected $_stopBitrix = false;

    /**
     * Send all headers
     *
     * Sends any headers specified. If an {@link setHttpResponseCode() HTTP response code}
     * has been specified, it is sent with the first header.
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function sendHeaders() {
        if ($this->stopBitrix()) {
            return parent::sendHeaders();// edited by sohorev
        }
        return $this;
    }

    /**
     * Send the response, including all headers, rendering exceptions if so
     * requested. Terminates the script to control is not returned to the
     * Bitrix.
     *
     * @return void
     */
    public function sendResponse() {
        if (!$this->stopBitrix()) {
            ob_start();
        }

        parent::sendResponse();

        if (!$this->stopBitrix()) {
            $content = ob_get_clean();
            Zend_Registry::get('BX_APPLICATION')->AddViewContent('ZEND_OUTPUT', $content, '');

            $titles = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view')->headTitle();
            if (count($titles)) {
                $parts = array();
                foreach ($titles as $title) {
                    $parts[] = $title;
                }
                Zend_Registry::get('BX_APPLICATION')->SetTitle(implode(' — ', $parts));
            }

            return;
        }

        die();
    }

    /**
     * Whether or not to stop Bitrix engine (off by default)
     *
     * If called with no arguments or a null argument, returns the value of the
     * flag; otherwise, sets it and returns the current value.
     *
     * @param boolean $flag Optional
     * @return boolean
     */
    public function stopBitrix($flag = null) {
        if (null !== $flag) {
            $this->_stopBitrix = $flag ? true : false;
        }

        if (!$this->_stopBitrix && ($this->_hasRedirect() || $this->_hasJsonHeader())) {

            return true;
        }

        return $this->_stopBitrix;
    }

    /**
     * has redirect
     * @return bool
     */
    protected function _hasRedirect() {
        return (floor($this->getHttpResponseCode() / 100) == 3);
    }

    /**
     * has Json Response Header
     * @return bool
     */
    protected function _hasJsonHeader() {
        foreach ($this->getHeaders() as $header) {
            if (strtolower($header['name']) == 'content-type' && strtolower($header['value']) == 'application/json') {
                return true;
            }
        }

        return false;
    }

    /**
     * Fire ERROR_404 flag for Bitrix engine.
     *
     * @return void
     */
    public function setBitrix404() {
        // Set ERROR_404 to "Y"
        if (!defined('ERROR_404')) {
            define('ERROR_404', 'Y');
        }
    }
}
