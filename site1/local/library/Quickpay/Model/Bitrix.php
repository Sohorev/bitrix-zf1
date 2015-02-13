<?php
/**
 * abstract class with support bitrix cache
 * Class Quickpay_Model_Bitrix
 */
abstract class Quickpay_Model_Bitrix {

    /**
     * stored configuration
     * @var array
     */
    protected $config = array();

    /**
     * bxCache instance if configured
     * @var null
     */
    protected $bxCache = null;

    /**
     *
     */
    protected $defaultCacheTime = 36000;

    /**
     * model instance setup
     * @return mixed
     */
    abstract public function init();

    public function __construct($config = array()) {
        $this->setConfig($config);
        $this->init();
    }

    /**
     * Принудительно отключить использование кеша для текущего объекта модели
     */
    public function disableCache() {
        $this->bxCache = null;
    }

    /**
     * Включить (возможно принудительно) использование кеша для текущего объекта модели
     * @param bool $force
     */
    public function enableCache($force = false) {
        if (!$force) {
            if ($this->config['cache']['type'] == 'Y') {
                $this->bxCache = new CPHPCache();
            }
        } else {
            $this->setConfig(array('cache' => array('type' => 'Y')));
        }
    }

    /**
     * apply new model config
     * @param array $config
     */
    public function setConfig($config = array()) {
        if (!is_array($config)) {
            $config = array();
        }
        if (!isset($config['cache']) || !is_array($config['cache'])) {
            $config['cache'] = array();
        }

        $config['cache']['type'] = (isset($config['cache']['type']) ? $config['cache']['type'] : 'N');

        switch ($config['cache']['type']) {

            case 'A':
                $config['cache']['type'] = COption::getOptionString('main', 'component_cache_on', 'Y');
                break;

            case 'Y':
                break;

            default:
                $config['cache']['type'] = 'N';
                break;
        }

        $config['cache']['time'] = (isset($config['cache']['time']) && intval($config['cache']['time']) > 0 ? intval($config['cache']['time']) : $this->defaultCacheTime);

        $this->config = $config;

        if ($this->config['cache']['type'] == 'Y') {
            $this->bxCache = new CPHPCache();
        }
    }

    protected function getCacheFolder() {
        $folder = '/quickpay/';
        if (isset($this->_iBlockId) && !empty($this->_iBlockId)) {
            $folder .= $this->_iBlockId . '/';
        }
        $folder .= get_class($this) . '/';
        return $folder;
    }

    /**
     * read data from cache by key
     * @param $rawCacheId
     * @return array|null
     */
    protected function _getCache($rawCacheId) {
        $cacheId = $rawCacheId . '|' . CTimeZone::GetOffset();
        if ($this->bxCache === null) {
            return null;
        }

        if ($this->bxCache->InitCache($this->config['cache']['time'], $cacheId, $this->getCacheFolder())) {
            $data = $this->bxCache->GetVars();
            if (isset($data) && is_array($data)) {
                return $data;
            }
        }
        return null;
    }

    /**
     * save data to cache by key
     * @param $rawCacheId
     * @param $data
     * @return bool
     */
    protected function _setCache($rawCacheId, $data) {
        $cacheId = $rawCacheId . '|' . CTimeZone::GetOffset();
        if ($this->bxCache === null) {
            return false;
        }

        ob_start();
        if ($this->bxCache->StartDataCache($this->config['cache']['time'], $cacheId, $this->getCacheFolder())) {
            $this->bxCache->EndDataCache($data);
            ob_end_clean();
            return true;
        }
        ob_end_clean();
        return false;
    }

    /**
     * clear cache
     */
    protected function _clearCache() {
        BXClearCache(true, $this->getCacheFolder());
    }
}