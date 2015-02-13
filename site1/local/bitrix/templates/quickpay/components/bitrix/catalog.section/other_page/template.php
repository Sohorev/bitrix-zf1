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
<div class="downmenu">
    <nav>
        <? foreach ($arResult["ITEMS"] as $cell => $arElement): ?>
            <?
            $this->AddEditAction($arElement['ID'], $arElement['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arElement['ID'], $arElement['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCT_ELEMENT_DELETE_CONFIRM')));
            ?>
            <div class="downmenuitem">
                <div><a href="<?= $arElement["DETAIL_PAGE_URL"] ?>" class="blackbighref"><?= $arElement["NAME"] ?></a></div>
                <div><img
                        border="0"
                        src="<?= $arElement["PREVIEW_PICTURE"]["SRC"] ?>"
                        width="<?= $arElement["PREVIEW_PICTURE"]["WIDTH"] ?>"
                        height="<?= $arElement["PREVIEW_PICTURE"]["HEIGHT"] ?>"
                        alt="<?= $arElement["PREVIEW_PICTURE"]["ALT"] ?>"
                        title="<?= $arElement["PREVIEW_PICTURE"]["TITLE"] ?>"
                        />
                </div>
            </div>
        <? endforeach; ?>
    </nav>
</div>
<div style="clear:both"></div>