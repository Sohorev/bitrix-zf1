<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); ?>
<? $this->setFrameMode(true); ?>
<div class="find">
    <form action="<?=$arResult["FORM_ACTION"]?>">
        <div><input class="search" type="text" value="Поиск"/></div>
        <div class="loupediv"><input class="loupe" type="image" src="<?= SITE_TEMPLATE_PATH ?>/images/loupe.png"/></div>
    </form>
</div>
