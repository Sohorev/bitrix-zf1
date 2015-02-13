<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$zend = $APPLICATION->GetViewContent('ZEND_OUTPUT');
if ((!defined('ERROR_404') || ERROR_404 != 'Y') && !empty($zend)) {
    echo($zend);
} else {

    CHTTP::SetStatus("404 Not Found");
    @define("ERROR_404","Y");
    $APPLICATION->SetTitle("404 Not found");

    ?>
    <div class="textContent notFound">
        <h2>404</h2>
        <h1>Запрашиваемая страница не найдена</h1>
        <div class="notFoundText">
            <p>Возможно, это случилось по одной из этих причин:</p>
            <ul>
                <li>вы ошиблись при наборе адреса страницы (URL);</li>
                <li>перешли по неработающей ссылке;</li>
                <li>запрашиваемая страница была удалена.</li>
            </ul>
            <p>Мы просим прощения за доставленные неудобства&nbsp;и&nbsp; предлагаем следующие пути:</p>
            <ul>
                <li>вернуться назад при помощи кнопки браузера <a href="javascript:history.back()">Назад</a>;</li>
                <li>проверить правильность написания адреса страницы;</li>
                <li>перейти на <a href="/">главную страницу</a> сайта;</li>
            </ul>
        </div>
    </div>

<?
}
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");?>
