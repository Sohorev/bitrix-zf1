<?
/**
 * Class Quickpay_Model_News
 */
class Quickpay_Model_News extends Quickpay_Model_Bitrix_Iblock {
    /**
     *
     */
    const IBLOCK_ID = IB_NEWS;

    /**
     * Инициализация модели
     */
    public function init() {
        if (!isset($this->config['elements']) || !is_array($this->config['elements'])) {
            $this->config['elements'] = array();
        }
        $confCount = $this->config['elements']['count'];
        $this->config['elements']['count'] = (isset($confCount) && intval($confCount) >= 0 ? intval($confCount) : 10);
    }

    public function getElement($params = array()) {}
    public function getElements($params = array()) {}

    /**
     * Получение списка новостей
     * @param array $params
     * @return array
     */
    public function getList($params = array()) {
        CPageOption::SetOptionString('main', 'nav_page_in_session', 'N');

        // Базовый фильтр
        $filter = array(
            'IBLOCK_ID' => self::IBLOCK_ID,
            'ACTIVE'    => 'Y'
        );

        // Пользовательский фильтр
        $filter = array_merge($filter, $params);

        $cacheId = 'getList_' . md5(serialize($filter));

        $data = $this->_getCache($cacheId);
        if (is_array($data)) {
            return $data;
        }

        $arNavParams = array(
            "nPageSize"          => $this->config['elements']['count'],
            "bDescPageNumbering" => '',
            "bShowAll"           => false,
        );

        $dbResult = CIBlockElement::GetList(
            array(
                'ACTIVE_FROM' => 'DESC',
                'ID'          => 'DESC'
            ),
            $filter,
            false,
            $arNavParams,
            array(
                'IBLOCK_ID',
                'ID',
                'NAME',
                'PREVIEW_TEXT',
                'PREVIEW_PICTURE',
                'CODE',
                'DATE_ACTIVE_FROM'
            ));

        $data = array(
            'items' => array(),
            'pager' => ''
        );

        while ($element = $dbResult->GetNext()) {
            $this->addPanelButtons($element, $element["IBLOCK_ID"], $element["ID"]);
            $data['items'][] = $element;
        }

        $data['pager'] = $dbResult->GetPageNavString('', '', $arNavParams['bShowAll']);

        $this->_setCache($cacheId, $data);
        return $data;
    }

    /**
     * Получение деталей одной новости
     * @param array $params
     * @return array|bool
     */
    public function getDetail($params = array()) {
        // Базовый фильтр
        $filter = array(
            'IBLOCK_ID' => self::IBLOCK_ID,
            'ACTIVE'    => 'Y'
        );

        // Пользовательский фильтр
        $filter = array_merge($filter, $params);

        // Обязательные параметры пользовательского фильтра
        if (isset($params['ID'])) {
            $filter['ID'] = $params['ID'];
            unset($params['ID']);
        } elseif (isset($params['CODE'])) {
            $filter['CODE'] = $params['CODE'];
            unset($params['CODE']);
        } else {
            return false;
        }

        $cacheId = 'getDetail' . md5(serialize($filter));

        $data = $this->_getCache($cacheId);
        if (is_array($data)) {
            return $data;
        }

        $dbResult = CIBlockElement::GetList(
            array(),
            $filter,
            false,
            false,
            array(
                'IBLOCK_ID',
                'ID',
                'NAME',
                'DETAIL_TEXT',
                'PREVIEW_PICTURE',
                'CODE',
                'DATE_ACTIVE_FROM'
            )
        );

        $data = array(
            'element' => array(),
            'pager'   => array()
        );

        $data['element'] = $dbResult->GetNext();

        if (!$data['element']) {
            return false;
        }

        $this->addPanelButtons($data['element'], $data['element']['IBLOCK_ID'], $data['element']['ID']);

        $this->_setCache($cacheId, $data);

        return $data;
    }

    public function addPanelButtons(&$element, $iblockId, $elementId) {
        $arButtons = CIBlock::GetPanelButtons(
            $iblockId,
            $elementId,
            0,
            array(
                'SECTION_BUTTONS' => false,
                'SESSID'          => false
            )
        );
        $element["EDIT_LINK"]   = $arButtons["edit"]["edit_element"]["ACTION_URL"];
        $element["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];
    }
}
