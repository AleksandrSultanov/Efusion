<?php

namespace Sultanov\Efusion\Crm;

use CCrmDeal;
use CCrmOwnerType;
use CCrmPerms;

class TabController
{
	/**
	 * CRM права текущего пользователя
	 *
	 * @var CCrmPerms
	 */
	protected CCrmPerms $userPermissions;

	public function __construct()
	{
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
	}

	/**
	 * Получение актуальных вкладок
	 *
	 * @param int $elementId
	 * @param int $entityTypeID
	 * @param array $tabs
	 * @return array
	 */
	public function getActualEntityTab(int $elementId, int $entityTypeID, array $tabs = []): array
	{
		switch ($entityTypeID)
		{
			case CCrmOwnerType::Deal:
				$tabs = $this->getActualDealTabs($tabs, $elementId);
			break;
			case CCrmOwnerType::Company:
				// @TODO Реализовать получение вкладок для компаний
			break;
			case CCrmOwnerType::Contact:
				// @TODO Реализовать получение вкладок для контактов
			break;
		}

		return $tabs;
	}

	/**
	 * Получение актуальных вкладок элемента сущности "Сделка"
	 *
	 * @param array $tabs
	 * @param int $elementId
	 * @return array
	 */
	private function getActualDealTabs(array $tabs, int $elementId): array
	{
		$canUpdateDeal = CCrmDeal::CheckUpdatePermission($elementId, $this->userPermissions);

		if (!$canUpdateDeal)
		{
			return $tabs;
		}
		else
		{
			$tabs[] = [
                'id'      => 'custom_grid_table',
                'name'    => 'Новая вкладка',
                'enabled' => !empty($elementId),
                'loader'  => [
                    'serviceUrl'    =>
                        '/local/components/sultanov.efusion/custom_deal_tab.grid/lazyload.ajax.php?&site=' . SITE_ID . '&'
                        . bitrix_sessid_get() . '&deal_id=' . $elementId,
                    'componentData' => [
                        'template' => '',
                        'deal_id' => $elementId,
                    ],
                ],
			];
		}

		return $tabs;
	}
}