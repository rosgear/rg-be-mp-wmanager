<?php
/**
 * Этот файл является частью расширения модуля веб-приложения RosGear.
 * 
 * Файл конфигурации установки расширения.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

return [
    'priority'    => 1,
    'id'          => 'rg.be.mp.wmanager',
    'moduleId'    => 'rg.be.mp',
    'name'        => 'Widget Manager',
    'description' => 'Website Widget Manager',
    'namespace'   => 'Rg\Backend\Marketplace\WidgetManager',
    'path'        => '/rg/rg.be.mp.wmanager',
    'route'       => 'wmanager',
    'locales'     => ['ru_RU', 'en_GB'],
    'permissions' => ['any', 'view', 'read', 'install', 'uninstall', 'info'],
    'events'      => [],
    'required'    => [
        ['php', 'version' => '8.2'],
        ['app', 'code' => 'RG Workspace'],
        ['app', 'code' => 'RG CMS'],
        ['app', 'code' => 'RG CRM'],
        ['module', 'id' => 'rg.be.mp']
    ]
];
