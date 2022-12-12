<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

$arComponentDescription = array(
    "NAME" => Loc::getMessage("DE_BL_COMPONENT_NAME"),
    "DESCRIPTION" => Loc::getMessage("DE_BL_COMPONENT_DESC"),
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "d_element",
        "NAME" => Loc::getMessage("DE_COMPONENTS_NAMESPACE_NAME"),
    ),
);
?>