<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Sultanov\Efusion\Crm\TabManager;
use Sultanov\Efusion\Entity\CustomGridTable;

Loc::loadMessages(__FILE__);

class sultanov_efusion extends CModule
{
	public $MODULE_ID = 'sultanov.efusion';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';
		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = Loc::getMessage('SULTANOV_EFUSION_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('SULTANOV_EFUSION_MODULE_DESC');
	}

	/**
	 * @throws LoaderException
	 */
	public function DoInstall(): void
	{
		ModuleManager::registerModule($this->MODULE_ID);
		$this->InstallDB();
		$this->InstallEvents();
		$this->InstallFiles();
	}

	/**
	 * @throws SqlQueryException
	 * @throws LoaderException
	 */
	public function DoUninstall(): void
	{
		$this->UnInstallEvents();
		$this->UnInstallDB();
        $this->UnInstallFiles();
		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	/**
	 * @throws LoaderException
	 */
	public function InstallDB(): void
	{
		if (Loader::includeModule($this->MODULE_ID))
		{
			try
			{
                $customGrid = CustomGridTable::getEntity();
                if (!Application::getConnection()->isTableExists($customGrid->getDBTableName())) {
                    $customGrid->createDbTable();
                }
			}
			catch (ArgumentException|SystemException $e)
			{
				Application::getInstance()->getExceptionHandler()->writeToLog($e);
			}
		}
	}

	/**
	 * @throws SqlQueryException
	 * @throws LoaderException
	 */
	public function UnInstallDB(): void
	{
		if (Loader::includeModule($this->MODULE_ID))
		{
			$connection = Application::getConnection();
			$connection->dropTable(CustomGridTable::getTableName());
		}
	}

	public function InstallEvents(): void
	{
		EventManager::getInstance()->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            TabManager::class,
            'setCustomTabs'
		);
	}

	public function UnInstallEvents(): void
	{
		EventManager::getInstance()->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            TabManager::class,
            'setCustomTabs'
		);
	}

	public function InstallFiles(): void
	{
		$documentRoot = Application::getDocumentRoot();

		CopyDirFiles(
            __DIR__ . '/files/components',
            $documentRoot . '/local/components/' . $this->MODULE_ID,
            true,
            true,
		);
	}

	public function UnInstallFiles(): void
	{
		DeleteDirFilesEx('/local/components/' . $this->MODULE_ID);
	}
}