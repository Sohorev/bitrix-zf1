<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<? $i = 0 ?>
<? foreach ($arResult["ITEMS"] as $cell => $arElement): ?>
    <?
    $this->AddEditAction($arElement['ID'], $arElement['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
    $this->AddDeleteAction($arElement['ID'], $arElement['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCT_ELEMENT_DELETE_CONFIRM')));
    ?>
    <? if (empty($arElement['PROPERTIES']['FONE']['VALUE'])): ?>
        <div class="process">
            <div class="logos">
                <img
                    border="0"
                    src="<?= $arElement["DETAIL_PICTURE"]["SRC"] ?>"
                    width="<?= $arElement["DETAIL_PICTURE"]["WIDTH"] ?>"
                    height="<?= $arElement["DETAIL_PICTURE"]["HEIGHT"] ?>"
                    alt="<?= $arElement["DETAIL_PICTURE"]["ALT"] ?>"
                    title="<?= $arElement["DETAIL_PICTURE"]["TITLE"] ?>"
                    />
            </div>
            <div class="processing">
                <a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="processingtext"><?= $arElement["NAME"] ?></a>
                <span><?= $arElement["DETAIL_TEXT"] ?></span>
                <div><button class="moreinfo">Подробнее</button></div>
            </div>
            <div style="clear:both"></div>
        </div>
    <? else: ?>
        <div class="paymentsystemleft"></div>
        <div class="paymentsystemright"></div>
        <div style="clear:both"></div>        
        <div class="paymentsystem" style="background: url(<?= CFile::getPath($arElement['PROPERTIES']['FONE']['VALUE']) ?>) no-repeat scroll 50% 50%;">
            <div class="paymentsystemin">
                <div class="paymentsystemblock">
                    <a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="paymentsystemtext"><?= $arElement["NAME"] ?></a>
                    <span class="whitetext"><?= $arElement["DETAIL_TEXT"] ?></span>
                    <div><button class="moreinfo2">Подробнее</button></div>
                </div>
                <div class="icons">
                    <img
                        border="0"
                        src="<?= $arElement["DETAIL_PICTURE"]["SRC"] ?>"
                        width="<?= $arElement["DETAIL_PICTURE"]["WIDTH"] ?>"
                        height="<?= $arElement["DETAIL_PICTURE"]["HEIGHT"] ?>"
                        alt="<?= $arElement["DETAIL_PICTURE"]["ALT"] ?>"
                        title="<?= $arElement["DETAIL_PICTURE"]["TITLE"] ?>"
                        />
                </div>
                <div style="clear:both"></div>
            </div>
        </div>
        <div style="height:45px"></div>
    <? endif ?>
    <? $i++ ?>
<? endforeach; ?>