<?php
use Bitrix\Main\Localization\Loc,
    Bitrix\Main\EventManager,
    Bitrix\Main\ModuleManager,
	Bitrix\Main\Config\Option,
    Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class awz_artpay extends CModule
{
	var $MODULE_ID = "awz.artpay";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $PAYMENT_HANDLER_PATH;

    public function __construct()
	{
        $arModuleVersion = array();
        include(__DIR__.'/version.php');

        $dirs = explode('/',dirname(__DIR__ . '../'));
        $this->MODULE_ID = array_pop($dirs);
        unset($dirs);

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = Loc::getMessage("AWZ_ARTPAY_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("AWZ_ARTPAY_MODULE_DESCRIPTION");
		$this->PARTNER_NAME = Loc::getMessage("AWZ_PARTNER_NAME");
		$this->PARTNER_URI = "https://zahalski.dev/";

		$ps_dir_path = strlen(Option::get('sale', 'path2user_ps_files')) > 3 ? Option::get('sale', 'path2user_ps_files') : '/bitrix/php_interface/include/sale_payment/';
        $this->PAYMENT_HANDLER_PATH = $_SERVER["DOCUMENT_ROOT"] . $ps_dir_path;
    

		return true;
	}

    function DoInstall()
    {
        global $APPLICATION, $step;

        $this->InstallFiles();
        $this->InstallDB();
        $this->checkOldInstallTables();
        $this->InstallEvents();
        $this->createAgents();

        ModuleManager::RegisterModule($this->MODULE_ID);

        $filePath = dirname(__DIR__ . '/../../').'/options.php';

        if(file_exists($filePath)){
            LocalRedirect('/bitrix/admin/settings.php?lang='.LANG.'&mid='.$this->MODULE_ID.'&mid_menu=1');
        }

        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION, $step;
		$this->UnInstallDB();
		$this->UnInstallFiles();
		$this->UnInstallEvents();
		$this->deleteAgents();

		ModuleManager::UnRegisterModule($this->MODULE_ID);
		return true;
		
    }

    function InstallDB()
    {
        return true;
    }

    function UnInstallDB()
    {
        return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'] ."/bitrix/modules/".$this->MODULE_ID."/install/handlers", $this->PAYMENT_HANDLER_PATH, true, true);
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'] ."/bitrix/modules/".$this->MODULE_ID."/install/images/logo", $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images/sale/sale_payments/');
        return true;
    }

    function UnInstallFiles()
    {
        $ps_dir_path = strlen(Option::get('sale', 'path2user_ps_files')) > 3 ? Option::get('sale', 'path2user_ps_files') : '/bitrix/php_interface/include/sale_payment/';
        DeleteDirFilesEx($ps_dir_path . str_replace(".", "", $this->MODULE_ID));
        return true;
    }

    function createAgents() {
        return true;
    }

    function deleteAgents() {
        return true;
    }

    function checkOldInstallTables()
    {
        return true;
    }

}