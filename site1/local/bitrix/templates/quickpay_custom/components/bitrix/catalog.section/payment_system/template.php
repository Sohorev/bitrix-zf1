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
    <? if ($i % 2 == 0): ?>
        <div class="slidepicttext">
            <div class="slidepict">
                <img
                    border="0"
                    src="<?= $arElement["DETAIL_PICTURE"]["SRC"] ?>"
                    width="<?= $arElement["DETAIL_PICTURE"]["WIDTH"] ?>"
                    height="<?= $arElement["DETAIL_PICTURE"]["HEIGHT"] ?>"
                    alt="<?= $arElement["DETAIL_PICTURE"]["ALT"] ?>"
                    title="<?= $arElement["DETAIL_PICTURE"]["TITLE"] ?>"
                    />
            </div>
            <div class="slidetext"><?= $arElement["NAME"] ?><br/>
                  <span><?= $arElement["DETAIL_TEXT"] ?></span>
            </div>
        </div>
    <? else: ?>
<div class="slidetextpict">
    <div class="slidepicttext">
        <div class="slidetext"><?= $arElement["NAME"] ?><br/>
            <span><?= $arElement["DETAIL_TEXT"] ?></span>
        </div>
        <div class="slidepict">
            <img
                border="0"
                src="<?= $arElement["DETAIL_PICTURE"]["SRC"] ?>"
                width="<?= $arElement["DETAIL_PICTURE"]["WIDTH"] ?>"
                height="<?= $arElement["DETAIL_PICTURE"]["HEIGHT"] ?>"
                alt="<?= $arElement["DETAIL_PICTURE"]["ALT"] ?>"
                title="<?= $arElement["DETAIL_PICTURE"]["TITLE"] ?>"
                />
        </div>
    </div>
</div>
    <? endif ?>
    <div style="clear:both;height:30px"></div>
    <? $i++ ?>
<? endforeach; ?>