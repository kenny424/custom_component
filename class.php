<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Компонент выводит список элементов информационного блока
 * Этот компонент создавался в замену из коробки catalog.section, т.к данный компоненты тянет все свойства элементов
 */
use Bitrix\Main\Loader,
    Bitrix\Main\Entity,
    Bitrix\Sale,
    Bitrix\Sale\Order,
    Bitrix\Catalog,
    Bitrix\Main\Type\Collection,
    Bitrix\Main\Context,
    Bitrix\Iblock;
/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CUser $USER
 */

if (!Loader::includeModule('iblock'))
{
    ShowError(GetMessage('DE_BL_IBLOCK_MODULE_ERROR'));
    return;
}
if (!Loader::includeModule('catalog'))
{
    ShowError(GetMessage('DE_BL_CATALOG_MODULE_ERROR'));
    return;
}

class BlockElementList extends CBitrixComponent
{
    /**
     * @var array результат компонента
     */
    public $arNavigation;

    /**
     * @var array результат компонента
     */
    public $arNavParams;

    /**
     * @var array результат компонента
     */
    public $arResult;

    /**
     * @var array параметры компонента
     */
    public $arParams;

    /**
     * @var array параметры из смарт фильтра
     */
    public $arSmartFilter;

    /**
     * @var array параметры выборки
     */
    public $filter;

    /**
     * Инициализация свойств по параметрам компонента
     *
     * @param array $arParams массив параметров компонента
     *
     * @return array параметры компонента
     */
    public function onPrepareComponentParams($arParams)
    {
        if (!isset($arParams["CACHE_TIME"]))
        {
            $arParams["CACHE_TIME"] = 36000000;
        }

        $arParams["TAG_ELEMENT_ID"] = trim($arParams["TAG_ELEMENT_ID"]);
        $arParams["SORT_BY1"] = trim($arParams["SORT_BY1"]);

        if ($arParams["SORT_BY1"] == '')
        {
            $arParams["SORT_BY1"] = "ACTIVE_FROM";
        }

        if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER1"]))
        {
            $arParams["SORT_ORDER1"] = "DESC";
        }

        if ($arParams["SORT_BY2"] == '')
        {
            if (mb_strtoupper($arParams["SORT_BY1"]) == 'SORT')
            {
                $arParams["SORT_BY2"] = "ID";
                $arParams["SORT_ORDER2"] = "DESC";
            }
            else
            {
                $arParams["SORT_BY2"] = "SORT";
            }
        }

        if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER2"]))
        {
            $arParams["SORT_ORDER2"] = "ASC";
        }

        if (!is_array($arParams["FIELD_CODE"]))
        {
            $arParams["FIELD_CODE"] = array();
        }

        if (empty($arParams["PROPERTY_CODE"]) || !is_array($arParams["PROPERTY_CODE"]))
        {
            $arParams["PROPERTY_CODE"] = array();
        }

        foreach ($arParams["PROPERTY_CODE"] as $key => $value)
        {
            if ($value === "")
            {
                unset($arParams["PROPERTY_CODE"][$key]);
            }
        }

        if (!is_array($arParams["FIELD_CODE"]))
        {
            $arParams["FIELD_CODE"] = array();
        }

        $arParams['SECTIONS_CHAIN_START_FROM'] = isset($arParams['SECTIONS_CHAIN_START_FROM']) ? (int)$arParams['SECTIONS_CHAIN_START_FROM'] : 0;

        foreach ($arParams["FIELD_CODE"] as $key => $value)
        {
            if (!$value)
            {
                unset($arParams["FIELD_CODE"][$key]);
            }
        }

        return $arParams;
    }

    /**
     * Инициализация параметров пагинации
     *
     * @return array параметры пагинации
     */

    public function prepareNavParams(): array
    {
        CPageOption::SetOptionString("main", "nav_page_in_session", "N");
        if ($this->arParams["DISPLAY_TOP_PAGER"] || $this->arParams["DISPLAY_BOTTOM_PAGER"])
        {
            $this->arNavParams = [
                "nPageSize" => $this->arParams["ELEMENT_COUNT"],
            ];
            $this->arNavigation = CDBResult::GetNavParams($this->arNavParams);
            if ($this->arNavigation["PAGEN"] == 0 && $this->arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] > 0)
            {
                $this->arParams["CACHE_TIME"] = $this->arParams["PAGER_DESC_NUMBERING_CACHE_TIME"];
            }
        }
        else
        {
            $this->arNavParams = [
                "nTopCount" => $this->arParams["ELEMENT_COUNT"],
                "bDescPageNumbering" => $this->arParams["PAGER_DESC_NUMBERING"],
            ];
            $this->arNavigation = false;
        }
        return $this->arNavParams;
    }

    /**
     * Запуск компонента
     *
     */
    public function executeComponent()
    {
        if (empty($this->arParams['IBLOCK_ID']))
        {
            ShowError(GetMessage("DE_BL_IBLOCK_ID_ERROR"));
            return;
        }

        $this->prepareNavParams();
        $this->setBreadcrumb();
        $this->getCorrectArrayFilter();
        if ($this->startResultCache(false, array($this->filter, $this->arSmartFilter, $this->arNavigation)))
        {
            $this->getElementList();
            $this->set404status();
            $this->includeComponentTemplate();
        }
        $this->setMetaData();
    }

    /**
     * Подготовка массива для сортировки
     *
     * @return array параметры для сортировки
     */
    private function getCorrectArraySort(): array
    {
        $sort = [
            $this->arParams["SORT_BY1"] => $this->arParams["SORT_ORDER1"],
            $this->arParams["SORT_BY2"] => $this->arParams["SORT_ORDER2"],
        ];
        if (!array_key_exists("ID", $sort))
        {
            $sort["ID"] = "DESC";
        }
        return $sort;
    }

    /**
     * Подготовка массива для фильтрации
     *
     * @return void параметры для фильтрации
     */
    private function getCorrectArrayFilter(): void
    {
        $this->filter = [
            "IBLOCK_ID" => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
            'INCLUDE_SUBSECTIONS' => 'Y'
        ];

        if (!empty($this->arParams['SECTION_ID']))
        {
            $this->filter['SECTION_ID'] = $this->arParams["SECTION_ID"];
        }

        if ($this->arParams["FILTER_NAME"] == '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $this->arParams["FILTER_NAME"]))
        {
            $this->arSmartFilter = array();
        }
        else
        {
            $this->arSmartFilter = $GLOBALS[$this->arParams["FILTER_NAME"]];
            if (!is_array($this->arSmartFilter))
            {
                $this->arSmartFilter = array();
            }
        }
    }

    /**
     * Подготовка массива для выборки
     *
     * @return array параметры для выборки
     */
    private function getCorrectArraySelect(): array
    {
        return array_merge($this->arParams["FIELD_CODE"], [
            "ID",
            "IBLOCK_ID",
            "IBLOCK_SECTION_ID",
            "NAME",
            "ACTIVE_FROM",
            "TIMESTAMP_X",
            "DETAIL_PAGE_URL",
            "IPROPERTY_VALUES",
            "LIST_PAGE_URL",
            "DETAIL_TEXT",
            "DETAIL_TEXT_TYPE",
            "PREVIEW_TEXT",
            "PREVIEW_TEXT_TYPE",
            "PREVIEW_PICTURE",
            "DETAIL_PICTURE",
        ]);
    }

    /**
     * Получение списка элементов
     *
     */
    private function getElementList()
    {
        $sort = $this->getCorrectArraySort();
        $select = $this->getCorrectArraySelect();

        $rs = CIBlockElement::getList(
            $sort,
            array_merge($this->filter, $this->arSmartFilter),
            false,
            $this->arNavParams,
            $select
        );
        while ($result = $rs->GetNext())
        {
            $result['PREVIEW_PICTURE'] = CFile::GetPath($result['PREVIEW_PICTURE']);
            $result['DETAIL_PICTURE'] = CFile::GetPath($result['DETAIL_PICTURE']);

            $arButtons = CIBlock::GetPanelButtons(
                $result["IBLOCK_ID"],
                $result["ID"],
                0,
                array("SECTION_BUTTONS" => false, "SESSID" => false)
            );

            $result["PROPERTIES"] = array();

            $result["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
            $result["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];

            $this->arResult["ITEMS"][$result['ID']] = $result;
        }
        $this->arResult["NAV_STRING"] = $rs->GetPageNavStringEx(
            $navComponentObject,
            "",
            $this->arParams['PAGER_TEMPLATE'],
            false,
            $this
        );

        $this->arResult['NAV_RESULT'] = $rs;
        $this->arResult["NAV_CACHED_DATA"] = null;

        if (!empty($this->arParams['PROPERTY_CODE']) && !empty($this->arResult["ITEMS"]))
        {
            CIBlockElement::GetPropertyValuesArray(
                $this->arResult["ITEMS"],
                $this->arParams["IBLOCK_ID"],
                $this->filter,
                ['CODE' => $this->arParams['PROPERTY_CODE']]
            );
        }
        $this->getOptimalPriceElement();
        $this->setResultCacheKeys(
            [
                'ITEMS',
                $this->arParams['META_KEYWORDS'],
                $this->arParams['META_DESCRIPTION'],
                $this->arParams['BROWSER_TITLE'],
                $this->arParams['BACKGROUND_IMAGE'],
                'NAME',
                'PATH',
                'SECTIONS',
                'IBLOCK_SECTION_ID',
                'IPROPERTY_VALUES',
                'ITEMS_TIMESTAMP_X',
                'BACKGROUND_IMAGE',
                'USE_CATALOG_BUTTONS'
            ]
        );
    }

    /**
     * Получение оптимальной цены
     *
     * @return void оптимальная цена элементов
     */
    private function getOptimalPriceElement(): void
    {
        foreach ($this->arResult['ITEMS'] as $key => $items)
        {
            $this->arResult['ITEMS'][$key]['PRICE'] = CCatalogProduct::GetOptimalPrice($items['ID']);
        }
    }

    /**
     * Установка хлебных крошек
     *
     * @return void хлебные крошки
     */
    private function setBreadcrumb()
    {
        global $APPLICATION;
        if ($this->arParams['SECTION_ID'] > 0 && $this->arParams['ADD_SECTIONS_CHAIN'])
        {
            $this->arResult['PATH'] = array();
            $pathIterator = CIBlockSection::GetNavChain(
                $this->arResult['IBLOCK_ID'],
                $this->arParams['SECTION_ID'],
                [
                    'ID', 'CODE', 'XML_ID', 'EXTERNAL_ID', 'IBLOCK_ID',
                    'IBLOCK_SECTION_ID', 'SORT', 'NAME', 'ACTIVE',
                    'DEPTH_LEVEL', 'SECTION_PAGE_URL'
                ]
            );
            $pathIterator->SetUrlTemplates('', $this->arParams['SECTION_URL']);
            while ($path = $pathIterator->GetNext())
            {
                $ipropValues = new Iblock\InheritedProperty\SectionValues($this->arParams['IBLOCK_ID'], $path['ID']);
                $path['IPROPERTY_VALUES'] = $ipropValues->getValues();
                $this->arResult['PATH'][] = $path;
            }

            if ($this->arParams['SECTIONS_CHAIN_START_FROM'] > 0)
            {
                $this->arResult['PATH'] = array_slice($this->arResult['PATH'], $this->arParams['SECTIONS_CHAIN_START_FROM']);
            }

            if ($this->arParams['ADD_SECTIONS_CHAIN'] && is_array($this->arResult['PATH']))
            {
                foreach ($this->arResult['PATH'] as $path)
                {
                    if ($path['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'] != '')
                    {
                        $APPLICATION->AddChainItem($path['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'], $path['~SECTION_PAGE_URL']);
                    }
                    else
                    {
                        $APPLICATION->AddChainItem($path['NAME'], $path['~SECTION_PAGE_URL']);
                    }
                }
            }
        }
    }
    /**
     * Установка метаданных, last modified
     *
     * @return void метаданные, last modified
     */
    public function setMetaData()
    {
        global $APPLICATION;
        $arParams =& $this->arParams;

        $this->arResult['IPROPERTY_VALUES'] = array();
        if ($arParams['SECTION_ID'] > 0)
        {
            $ipropValues = new Iblock\InheritedProperty\SectionValues($arParams['IBLOCK_ID'], $arParams['SECTION_ID']);
            $this->arResult['IPROPERTY_VALUES'] = $ipropValues->getValues();
        }

        if ($this->arParams['SET_TITLE'])
        {
            if ($this->arResult['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'] != '')
            {
                $APPLICATION->SetTitle($this->arResult['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'], $this->storage['TITLE_OPTIONS']);
            }
            elseif (isset($this->arResult['NAME']))
            {
                $APPLICATION->SetTitle($this->arResult['NAME'], $this->storage['TITLE_OPTIONS']);
            }
        }
        if ($this->arParams['SET_BROWSER_TITLE'] === 'Y')
        {
            $browserTitle = \Bitrix\Main\Type\Collection::firstNotEmpty(
                $this->arResult, $this->arParams['BROWSER_TITLE'],
                $this->arResult['IPROPERTY_VALUES'], 'SECTION_META_TITLE'
            );
            if (is_array($browserTitle))
            {
                $APPLICATION->SetPageProperty('title', implode(' ', $browserTitle), $this->storage['TITLE_OPTIONS']);
            }
            elseif ($browserTitle != '')
            {
                $APPLICATION->SetPageProperty('title', $browserTitle, $this->storage['TITLE_OPTIONS']);
            }
        }
        if ($this->arParams['SET_META_KEYWORDS'] === 'Y')
        {
            $metaKeywords = \Bitrix\Main\Type\Collection::firstNotEmpty(
                $this->arResult, $this->arParams['META_KEYWORDS'],
                $this->arResult['IPROPERTY_VALUES'], 'SECTION_META_KEYWORDS'
            );
            if (is_array($metaKeywords))
            {
                $APPLICATION->SetPageProperty('keywords', implode(' ', $metaKeywords), $this->storage['TITLE_OPTIONS']);
            }
            elseif ($metaKeywords != '')
            {
                $APPLICATION->SetPageProperty('keywords', $metaKeywords, $this->storage['TITLE_OPTIONS']);
            }
        }

        if ($this->arParams['SET_META_DESCRIPTION'] === 'Y')
        {
            $metaDescription = \Bitrix\Main\Type\Collection::firstNotEmpty(
                $this->arResult, $this->arParams['META_DESCRIPTION'],
                $this->arResult['IPROPERTY_VALUES'], 'SECTION_META_DESCRIPTION'
            );
            if (is_array($metaDescription))
            {
                $APPLICATION->SetPageProperty('description', implode(' ', $metaDescription), $this->storage['TITLE_OPTIONS']);
            }
            elseif ($metaDescription != '')
            {
                $APPLICATION->SetPageProperty('description', $metaDescription, $this->storage['TITLE_OPTIONS']);
            }
        }
        if ($this->arParams['SET_LAST_MODIFIED'])
        {
            $time = \Bitrix\Main\Type\DateTime::createFromUserTime($this->arResult['SECTIONS'][$this->arParams['SECTION_ID']]['TIMESTAMP_X']);
            Bitrix\Main\Context::getCurrent()->getResponse()->setLastModified($time);
        }

    }
    /**
     * Установка 404 статуса
     *
     * @return void 404 статус
     */
    private function set404status()
    {
        if (empty($this->arResult['ITEMS']))
        {
            $this->abortResultCache();
            Iblock\Component\Tools::process404(
                trim($this->arParams["MESSAGE_404"]) ?: GetMessage("T_NEWS_NEWS_NA")
                ,true
                ,$this->arParams["SET_STATUS_404"] === "Y"
                ,$this->arParams["SHOW_404"] === "Y"
                ,$this->arParams["FILE_404"]
            );
        }
    }
}