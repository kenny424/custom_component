<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

/** @var array $arCurrentValues */

if(!CModule::IncludeModule("iblock"))
	return;

$arTypes = CIBlockParameters::GetIBlockTypes();

$arIBlocks=array();

$db_iblock = CIBlock::GetList(
    array("SORT"=>"ASC"),
    array("SITE_ID"=>$_REQUEST["site"], "TYPE" => ($arCurrentValues["IBLOCK_TYPE"]!="-"?$arCurrentValues["IBLOCK_TYPE"]:""))
);

while($arRes = $db_iblock->Fetch()) {
    $arIBlocks[$arRes["ID"]] = "[" . $arRes["ID"] . "] " . $arRes["NAME"];
}

$arProperty_LNS = array();
$rsProp = CIBlockProperty::GetList(array("sort"=>"asc", "name"=>"asc"), array("ACTIVE"=>"Y", "IBLOCK_ID"=>(isset($arCurrentValues["IBLOCK_ID"])?$arCurrentValues["IBLOCK_ID"]:$arCurrentValues["ID"])));
while ($arr=$rsProp->Fetch())
{
    $arProperty[$arr["CODE"]] = "[".$arr["CODE"]."] ".$arr["NAME"];
    if (in_array($arr["PROPERTY_TYPE"], array("L", "N", "S")))
    {
        $arProperty_LNS[$arr["CODE"]] = "[".$arr["CODE"]."] ".$arr["NAME"];
    }
}
$arSorts = array("ASC"=>GetMessage("DE_EL_IBLOCK_DESC_ASC"), "DESC"=>GetMessage("DE_EL_IBLOCK_DESC_DESC"));

$arSortFields = array(
    "ID"=>GetMessage("DE_EL_IBLOCK_DESC_FID"),
    "NAME"=>GetMessage("DE_EL_IBLOCK_DESC_FNAME"),
    "ACTIVE_FROM"=>GetMessage("DE_EL_IBLOCK_DESC_FACT"),
    "SORT"=>GetMessage("DE_EL_IBLOCK_DESC_FSORT"),
    "TIMESTAMP_X"=>GetMessage("DE_EL_IBLOCK_DESC_FTSAMP")
);

$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => Loc::getMessage("DE_EL_IBLOCK_TYPE_NAME"),
			"TYPE" => "LIST",
			"VALUES" => $arTypes,
			"DEFAULT" => "news",
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => Loc::getMessage("DE_EL_IBLOCK_ID_NAME"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlocks,
			"ADDITIONAL_VALUES" => "Y",
			"REFRESH" => "Y",
		),
        "FIELD_CODE" => CIBlockParameters::GetFieldCode(GetMessage("DE_EL_IBLOCK_FIELD_NAME"), "DATA_SOURCE"),
        "SECTION_ID" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("IBLOCK_SECTION_ID"),
            "TYPE" => "STRING",
            "DEFAULT" => '',
        ),
        "PROPERTY_CODE" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("DE_EL_PROPERTY_CODE_NAME"),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arProperty_LNS,
            "ADDITIONAL_VALUES" => "Y",
        ),
        "ELEMENT_COUNT" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => Loc::getMessage("DE_EL_ELEMENTS_COUNT_NAME"),
            "TYPE" => "STRING",
            "DEFAULT" => "25",
        ),
        "SORT_BY1" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("DE_EL_IBLOCK_DESC_IBORD1"),
            "TYPE" => "LIST",
            "DEFAULT" => "ACTIVE_FROM",
            "VALUES" => $arSortFields,
            "ADDITIONAL_VALUES" => "Y",
        ),
        "SORT_ORDER1" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("DE_EL_IBLOCK_DESC_IBBY1"),
            "TYPE" => "LIST",
            "DEFAULT" => "DESC",
            "VALUES" => $arSorts,
            "ADDITIONAL_VALUES" => "Y",
        ),
        "SORT_BY2" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("DE_EL_IBLOCK_DESC_IBORD2"),
            "TYPE" => "LIST",
            "DEFAULT" => "SORT",
            "VALUES" => $arSortFields,
            "ADDITIONAL_VALUES" => "Y",
        ),
        "SORT_ORDER2" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("DE_EL_IBLOCK_DESC_IBBY2"),
            "TYPE" => "LIST",
            "DEFAULT" => "ASC",
            "VALUES" => $arSorts,
            "ADDITIONAL_VALUES" => "Y",
        ),
        "SET_BROWSER_TITLE" => array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("DE_EL_SET_BROWSER_TITLE"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SET_META_KEYWORDS" => array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("DE_EL_SET_META_KEYWORDS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SET_META_DESCRIPTION" => array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("DE_EL_SET_META_DESCRIPTION"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SET_LAST_MODIFIED" => array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("DE_EL_SET_LAST_MODIFIED"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "FILTER_NAME" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("DE_EL_IBLOCK_FILTER"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        "ADD_SECTIONS_CHAIN" => array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("DE_EL_IBLOCK_DESC_ADD_SECTIONS_CHAIN"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
		"CACHE_TIME" => array("DEFAULT"=>36000000),
	),
);
CIBlockParameters::AddPagerSettings(
    $arComponentParameters,
    GetMessage("DE_EL_IBLOCK_DESC_PAGER"),
    true, //$bDescNumbering
    true, //$bShowAllParam
    true, //$bBaseLink
    $arCurrentValues["PAGER_BASE_LINK_ENABLE"]==="Y"
);

CIBlockParameters::Add404Settings($arComponentParameters, $arCurrentValues);