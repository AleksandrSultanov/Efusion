<?php

namespace Sultanov\Efusion\Crm;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use ReflectionClass;

class TabManager
{
	public static function setCustomTabs(Event $event): EventResult
	{
		$entityId = $event->getParameter('entityID');
		$entityTypeID = $event->getParameter('entityTypeID');
		$tabs = $event->getParameter('tabs');

		$reflection = new ReflectionClass($event);
		$property = $reflection->getProperty('parameters');

		$eventParameters = $property->getValue($event);

		$tabController = new TabController();

		$tabs = $tabController->getActualEntityTab($entityId, $entityTypeID, $tabs);

		// Добавление вкладки с помощью Reflection API
		$eventParameters['tabs'] = $tabs;
		$property->setValue($event, $eventParameters);

		return new EventResult(EventResult::SUCCESS, [
				'tabs' => $tabs,
		]);
	}
}