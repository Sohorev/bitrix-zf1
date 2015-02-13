<?
/**
 * Class Zend_View_Helper_News
 */
class Zend_View_Helper_News extends Zend_View_Helper_Abstract {

    /**
     * @return $this
     */
    public function News() {
        return $this;
    }

    /**
     * @param string $date
     * @return string
     */
    public function formatDate($date = '') {
        $dateObj = new Zend_Date($date, 'dd.MM.yyyy hh:mi:ss');
        return $dateObj->toString('dd-MM-yyyy');
    }

    /**
     * @param int|array $image
     * @return string
     */
    public function imagePreview($image) {
        $newFile = CFile::ResizeImageGet(
            $image,
            array(
                'width'  => 120,
                'height' => 80
            ),
            BX_RESIZE_IMAGE_EXACT,
            true
        );
        return $newFile['src'];
    }

    /**
     * @param int|array $image
     * @return string
     */
    public function imageDetail($image) {
        $newFile = CFile::ResizeImageGet(
            $image,
            array(
                'width'  => 150,
                'height' => 150
            ),
            BX_RESIZE_IMAGE_EXACT,
            true
        );
        return $newFile['src'];
    }
}
