<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Sultanov\Efusion\Entity\CustomGridTable;

class CustomDealTabGrid extends CBitrixComponent
{
    public const  GRID_ID            = 'custom_deal_grid';
    private const DEFAULT_PAGE_SIZE  = 20;
    private const DEFAULT_SORT_ORDER = ['ID' => 'DESC'];

    private FilterOptions  $filterOptions;
    private Options        $gridOptions;
    private PageNavigation $pageNavigation;
    private int            $dealId;

    public function __construct($component = null)
    {
        parent::__construct($component);

        $this->filterOptions = new FilterOptions(self::GRID_ID);
        $this->gridOptions = new Options(self::GRID_ID);
        $this->pageNavigation = new PageNavigation('nav_' . self::GRID_ID);

        Loader::includeModule('sultanov.efusion');
    }

    public function executeComponent(): void
    {
        $this->dealId = (int) ($this->arParams['DEAL_ID'] ?? 0);
        try
        {
            $this->checkRequiredParams();
            $this->arResult['FILTER'] = $this->getFilterFields();
            $this->arResult['COLUMNS'] = $this->getGridColumns();

            $filterData = $this->filterOptions->getFilter();
            $filter = $this->buildFilter($filterData);

            $this->initPagination($filter);
            $this->arResult['ROWS'] = $this->getRows($filter);
            $this->arResult['NAV'] = $this->pageNavigation;

            $this->includeComponentTemplate();
        }
        catch (Exception $e)
        {
            ShowError($e->getMessage());
        }
    }

    /**
     * @throws SystemException
     */
    private function checkRequiredParams(): void
    {
        if ($this->dealId <= 0)
        {
            throw new SystemException('Invalid deal ID');
        }
    }

    private function getFilterFields(): array
    {
        return [
            [
                'id'      => 'TITLE',
                'name'    => 'Название',
                'type'    => 'string',
                'default' => true,
            ],
            [
                'id'   => 'DESCRIPTION',
                'name' => 'Описание',
                'type' => 'string',
            ],
            [
                'id'   => 'CREATED_AT',
                'name' => 'Дата создания',
                'type' => 'date',
            ],
            [
                'id'   => 'UPDATED_AT',
                'name' => 'Дата обновления',
                'type' => 'date',
            ],
        ];
    }

    private function getGridColumns(): array
    {
        return [
            [
                'id'      => 'ID',
                'name'    => 'ID',
                'default' => true,
                'sort'    => 'ID',
            ],
            [
                'id'      => 'TITLE',
                'name'    => 'Название',
                'default' => true,
                'sort'    => 'TITLE',
            ],
            [
                'id'      => 'DESCRIPTION',
                'name'    => 'Описание',
                'default' => true,
                'sort'    => 'DESCRIPTION',
            ],
            [
                'id'      => 'CREATED_AT',
                'name'    => 'Дата создания',
                'default' => true,
                'sort'    => 'CREATED_AT',
            ],
            [
                'id'      => 'UPDATED_AT',
                'name'    => 'Дата обновления',
                'default' => true,
                'sort'    => 'UPDATED_AT',
            ],
        ];
    }

    private function buildFilter(array $filterData = []): array
    {
        // Базовый фильтр по ID сделки
        $filter = ['=DEAL_ID' => $this->dealId];

        // Добавляем фильтры из UI
        foreach ($this->arResult['FILTER'] as $field)
        {
            $fieldId = $field['id'];

            if (!empty($filterData[$fieldId]))
            {
                $value = $filterData[$fieldId];

                // Специальная обработка для дат
                if (in_array($fieldId, ['CREATED_AT', 'UPDATED_AT']))
                {
                    if (is_array($value))
                    {
                        if (!empty($value['from']))
                        {
                            $filter['>=' . $fieldId] = $value['from'];
                        }
                        if (!empty($value['to']))
                        {
                            $filter['<=' . $fieldId] = $value['to'];
                        }
                    }
                    else
                    {
                        $filter[$fieldId] = $value;
                    }
                }
                // Фильтр по подстроке для текстовых полей
                elseif (in_array($fieldId, ['TITLE', 'DESCRIPTION']))
                {
                    $filter['%' . $fieldId] = $value;
                }
                // Точное совпадение для остальных
                else
                {
                    $filter[$fieldId] = $value;
                }
            }
        }

        // Фильтр по быстрому поиску
        if (!empty($filterData['FIND']))
        {
            $searchTerm = $filterData['FIND'];
            $filter[] = [
                'LOGIC'        => 'OR',
                '%TITLE'       => $searchTerm,
                '%DESCRIPTION' => $searchTerm,
            ];
        }

        return $filter;
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    private function initPagination(array $filter): void
    {
        $this->pageNavigation->initFromUri();

        // Получаем количество записей
        $totalCount = CustomGridTable::getCount($filter);
        $this->pageNavigation->setRecordCount($totalCount);

        // Устанавливаем размер страницы
        $pageSize = $this->gridOptions->getCurrentOptions()['page_size'] ?? self::DEFAULT_PAGE_SIZE;
        $this->pageNavigation->setPageSize((int) $pageSize);
    }

    /**
     * @throws ArgumentException
     * @throws ObjectException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getRows(array $filter): array
    {
        $rows = [];

        $dbResult = CustomGridTable::getList([
            'select'      => [
                'ID',
                'TITLE',
                'DESCRIPTION',
                'CREATED_AT',
                'UPDATED_AT',
            ],
            'filter'      => $filter,
            'order'       => $this->getOrder(),
            'offset'      => $this->pageNavigation->getOffset(),
            'limit'       => $this->pageNavigation->getLimit(),
            'count_total' => true,
        ]);

        while ($item = $dbResult->fetch())
        {
            $rows[] = [
                'data'    => [
                    'ID'          => $item['ID'],
                    'TITLE'       => $item['TITLE'],
                    'DESCRIPTION' => $item['DESCRIPTION'],
                    'CREATED_AT'  => $item['CREATED_AT']->toString(),
                    'UPDATED_AT'  => $item['UPDATED_AT'] ? $item['UPDATED_AT']->toString() : '',
                ],
                'actions' => $this->getRowActions($item['ID']),
            ];
        }

        return $rows;
    }

    private function getOrder(): array
    {
        return $this->gridOptions->getSorting(self::DEFAULT_SORT_ORDER)['sort'];
    }

    private function getRowActions(int $id): array
    {
        return [
            [
                'TEXT'    => 'Редактировать',
                'ONCLICK' => "editRecord({$id})",
                'DEFAULT' => true,
            ],
            [
                'TEXT'    => 'Удалить',
                'ONCLICK' => "deleteRecord({$id})",
            ],
        ];
    }
}