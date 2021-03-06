<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Tracking\TrackingTable;
use Tracking\LogTable;

Loc::loadMessages(__FILE__);

class Tracking extends CModule
{
    public function __construct()
    {
        $arModuleVersion = array();

        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_ID = 'tracking';
        $this->MODULE_NAME = Loc::getMessage('TRACKING_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('TRACKING_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->InstallFiles();
    }

    public function doUninstall()
    {
        $this->uninstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        Option::delete($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            TrackingTable::getEntity()->createDbTable();
            LogTable::getEntity()->createDbTable();
        }
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(TrackingTable::getTableName());
            $connection->dropTable(LogTable::getTableName());
        }
    }

    public function UnInstallEvents()
    {
        $handbookOptions = explode(',', Option::get($this->MODULE_ID, 'handbooks'));
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach ($handbookOptions as $handbooksName) {
            $eventManager->UnRegisterEventHandler("", $handbooksName.'OnAfterUpdate', $this->MODULE_ID, "Tracking\Event", "updateHandbook");
            $eventManager->UnRegisterEventHandler("", $handbooksName.'OnAfterDelete', $this->MODULE_ID, "Tracking\Event", "deleteHandbook");
            $eventManager->UnRegisterEventHandler("", $handbooksName.'OnAfterAdd', $this->MODULE_ID, "Tracking\Event", "addHandbook");
        }
        return true;
    }

    public function InstallFiles()
    {
        $re = \CopyDirFiles($_SERVER["DOCUMENT_ROOT"].getLocalPath('modules/'.$this->MODULE_ID.'/install/admin'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true, true);

        return true;
    }

    public function UnInstallFiles()
    {
        \DeleteDirFiles($_SERVER["DOCUMENT_ROOT"].getLocalPath('modules/'.$this->MODULE_ID.'/install/admin'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');

        return true;
    }
}