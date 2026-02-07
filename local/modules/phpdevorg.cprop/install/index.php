<?php

use \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class phpdevorg_cprop extends CModule
{
    var $MODULE_ID  = 'phpdevorg.cprop';

    function __construct()
    {
        $arModuleVersion = array();
        include __DIR__ . '/version.php';

        $this->MODULE_ID = 'phpdevorg.cprop';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('IEX_CPROP_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('IEX_CPROP_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('IEX_CPROP_PARTNER_NAME');
        $this->PARTNER_URI = 'https://phpdev.org';

        $this->FILE_PREFIX = 'cprop';
        $this->MODULE_FOLDER = str_replace('.', '_', $this->MODULE_ID);
        $this->FOLDER = 'bitrix';

        $this->INSTALL_PATH_FROM = '/' . $this->FOLDER . '/modules/' . $this->MODULE_ID;
    }

    function isVersionD7()
    {
        return true;
    }

    function DoInstall()
    {
        global $APPLICATION;
        if($this->isVersionD7())
        {
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();

            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage('IEX_CPROP_INSTALL_ERROR_VERSION'));
        }
    }

    function DoUninstall()
    {
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallDB();
    }


    function InstallDB()
    {
        return true;
    }

    function UnInstallDB()
    {
        return true;
    }

    function installFiles()
    {
        return true;
    }

    function uninstallFiles()
    {
        return true;
    }

    function getEvents()
    {
        return [
            ['FROM_MODULE' => 'main', 'EVENT' => 'OnUserTypeBuildList', 'TO_METHOD' => 'GetUserTypeDescription'],
        ];
    }
    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $arEvents = $this->getEvents();

        foreach($arEvents as $arEvent){
            // Определяем класс обработчика в зависимости от события
            if ($arEvent['EVENT'] == 'OnIBlockPropertyBuildList') {
                $classHandler = 'CIBlockPropertyCprop'; // Класс для свойств ИБ
            } else {
                $classHandler = 'CUserTypeCprop';       // Класс для UF (создадим его позже)
            }

            $eventManager->registerEventHandler(
                $arEvent['FROM_MODULE'],
                $arEvent['EVENT'],
                $this->MODULE_ID,
                $classHandler,
                $arEvent['TO_METHOD']
            );
        }
        return true;
    }

    function UnInstallEvents()
    {
        // Удаляем жесткую привязку к классу
        // $classHandler = 'CIBlockPropertyCprop'; // Эту строку убираем
        
        $eventManager = EventManager::getInstance();
        $arEvents = $this->getEvents();

        foreach($arEvents as $arEvent){
            
            // Определяем тот же класс, что и при установке
            if ($arEvent['EVENT'] == 'OnIBlockPropertyBuildList') {
                $classHandler = 'CIBlockPropertyCprop'; // Класс для свойств ИБ
            } else {
                $classHandler = 'CUserTypeCprop';       // Класс для UF (пользовательских полей)
            }

            $eventManager->unRegisterEventHandler(
                $arEvent['FROM_MODULE'],
                $arEvent['EVENT'],
                $this->MODULE_ID,
                $classHandler,
                $arEvent['TO_METHOD']
            );
        }

        return true;
    }

}