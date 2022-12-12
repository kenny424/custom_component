<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

/** @var array $arResult */
/** @var array $arParams */
$this->setFrameMode(true);

if (empty($arResult['ITEMS'])) {
    return;
}
consoleDump($arResult['ITEMS']);
?>
<ul>
    <?php foreach ($arResult['ITEMS'] as $item) { ?>
        <li>
            <a href="<?= $item['DETAIL_PAGE_URL']; ?>"><?= $item['NAME']; ?></a>
            <img src="<?= $item['PREVIEW_PICTURE']; ?>">
        </li>
    <?php } ?>
</ul>
<?php
    if (!empty($arResult['NAV_STRING']))
    {
        echo $arResult['NAV_STRING'];
    }
?>
