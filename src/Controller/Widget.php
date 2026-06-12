<?php
/**
 * Этот файл является частью расширения модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Marketplace\WidgetManager\Controller;

use Ge;
use Ge\Panel\Http\Response;
use Ge\Filesystem\Filesystem;
use Ge\Mvc\Module\BaseModule;
use Ge\Panel\Controller\BaseController;
use Rg\Backend\Marketplace\WidgetManager\Widget\InformationTab;

/**
 * Контроллер удаления и демонтажа виджета.
 * 
 * Действия контроллера:
 * - unmount, удаление установленного виджета без удаления его из репозитория;
 * - uninstall, полностью удаление установленного виджета;
 * - update, обновление конфигурации установленных виджетов;
 * - delete, удаление не установленного виджета из репозитория;
 * - info, информация о виджете.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\WidgetManager\Controller
 * @since 1.0
 */
class Widget extends BaseController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Marketplace\WidgetManager\Extension
     */
    public BaseModule $module;

    /**
     * Действие "unmount" выполняет удаление установленного виджета без удаления его 
     * из репозитория.
     * 
     * @return Response
     */
    public function unmountAction(): Response
    {
        /** @var \Ge\WidgetManager\WidgetManager */
        $widgets = Ge::$app->widgets;
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Ge\Http\Request $request */
        $request = Ge::$app->request;

        // идентификатор виджета в базе данных
        $widgetId = $request->getPost('id', null, 'int');
        if (empty($widgetId)) {
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', [$widgetId]));
            return $response;
        }

        /** @var null|array Конфигурация установленного виджета */
        $widgetConfig = $widgets->getRegistry()->getInfo($widgetId, true);
        if ($widgetConfig === null) {
            $response
                ->meta->error($this->module->t('Widget with specified id "{0}" not found', [$widgetId]));
            return $response;
        }

        // локализация виджета
        $localization = $widgets->selectName($widgetConfig['rowId']);
        if ($localization) {
            $name = $localization['name'] ?? SYMBOL_NONAME;
        } else {
            $name = $moduleConfig['name'] ?? SYMBOL_NONAME;
        }

        // если виджет не имеет установщика "Installer\Installer.php"
        if (!$widgets->installerExists($widgetConfig['path'])) {
            $response
                ->meta->error(
                    $this->module->t('The widget installer at the specified path "{0}" does not exist', [$widgetConfig['path']])
                );
            return $response;
        }

        // каждый виджет обязан иметь установщик, управление установщиком передаётся текущему модулю
        /** @var \Ge\WidgetManager\WidgetInstaller $installer Установщик виджета */
        $installer = $widgets->getInstaller([
            'module'    => $this->module,
            'namespace' => $widgetConfig['namespace'],
            'path'      => $widgetConfig['path'],
            'widgetId'  => $widgetId
        ]);

        // если не получилось создать установщик
        if ($installer === null) {
            $response
                ->meta->error($this->t('Unable to create widget installer'));
            return $response;
        }

        // демонтируем виджет
        if ($installer->unmount()) {
            $response
                ->meta
                    ->cmdPopupMsg(
                        $this->module->t('Unmounting of widget "{0}" completed successfully', [$name]), 
                        $this->t('Unmounting'), 
                        'accept'
                    )
                    ->cmdReloadGrid($this->module->viewId('grid'));
        } else {
            $response
                ->meta->error($installer->getError());
        }
        return $response;
    }

    /**
     * Действие "uninstall" выполняет полностью удаление установленного виджета.
     * 
     * @return Response
     */
    public function uninstallAction():Response
    {
        /** @var \Ge\WidgetManager\WidgetManager */
        $widgets = Ge::$app->widgets;
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Ge\Http\Request $request */
        $request = Ge::$app->request;

        // идентификатор виджета в базе данных
        $widgetId = $request->getPost('id', null, 'int');
        if (empty($widgetId)) {
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', ['id']));
            return $response;
        }

        /** @var null|array Конфигурация установленного виджета */
        $widgetConfig = $widgets->getRegistry()->getInfo($widgetId, true);
        if ($widgetConfig === null) {
            $response
                ->meta->error($this->module->t('Widget with specified id "{0}" not found', [$widgetId]));
            return $response;
        }

        // локализация виджета
        $localization = $widgets->selectName($widgetConfig['rowId']);
        if ($localization) {
            $name = $localization['name'] ?? SYMBOL_NONAME;
        } else {
            $name = $widgetConfig['name'] ?? SYMBOL_NONAME;
        }

        // если виджет не имеет установщика "Installer\Installer.php"
        if (!$widgets->installerExists($widgetConfig['path'])) {
            $response
                ->meta->error(
                    $this->module->t('The widget installer at the specified path "{0}" does not exist', [$widgetConfig['path']])
                );
            return $response;
        }

        // каждый виджет обязано иметь установщик, управление установщиком передаётся текущему модулю
        /** @var \Ge\WidgetManager\WidgetInstaller $installer Установщик виджета */
        $installer = $widgets->getInstaller([
            'module'    => $this->module,
            'namespace' => $widgetConfig['namespace'],
            'path'      => $widgetConfig['path'],
            'widgetId'  => $widgetId
        ]);

        // если не получилось создать установщик
        if ($installer === null) {
            $response
                ->meta->error($this->t('Unable to create widget installer'));
            return $response;
        }

        // удаление виджета
        if ($installer->uninstall()) {
            $response
                ->meta
                    ->cmdPopupMsg(
                        $this->module->t('Uninstalling of widget "{0}" completed successfully', [$name]), 
                        $this->t('Uninstalling'), 
                        'accept'
                    )
                    ->cmdReloadGrid($this->module->viewId('grid'));
        } else {
            $response
                ->meta->error($installer->getError());
        }
        return $response;
    }

    /**
     * Действие "update" обновляет конфигурацию установленных виджетов.
     * 
     * @return Response
     */
    public function updateAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        // обновляет конфигурацию установленных виджетов
        Ge::$app->widgets->update();
        $response
            ->meta->success(
                $this->t('Widgets configuration files are updated'), 
                $this->t('Updating widgets'), 
                'custom', 
                $this->module->getAssetsUrl() . '/images/icon-update-config.svg'
            );
        return $response;
    }

    /**
     * Действие "delete" выполняет удаление не установленного виджета из репозитория.
     * 
     * @return Response
     */
    public function deleteAction(): Response
    {
        /** @var \Ge\WidgetManager\WidgetManager */
        $widgets = Ge::$app->widgets;
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|string $installId Идентификатор установки виджета */
        $installId = Ge::$app->request->post('installId');

        /** @var string|array $decrypt Расшифровка идентификатора установки виджета */
        $decrypt = $widgets->decryptInstallId($installId);
        if (is_string($decrypt)) {
            $response
                ->meta->error($decrypt);
            return $response;
        }

        /** @var null|array $installConfig Параметры конфигурации установки виджета */
        $installConfig = $widgets->getConfigInstall($decrypt['path']);
        if (empty($installConfig)) {
            $response
                ->meta->error(
                    $this->module->t('Widget installation configuration file is missing')
                );
            return $response;
        }

        // если виджет установлен
        if ($widgets->getRegistry()->has($installConfig['id'])) {
            $response
                ->meta->error(
                    $this->module->t('It is not possible to remove the widget from the repository because it\'s installed')
                );
            return $response;
        }

        // попытка удаления всех файлов виджета
        if (Filesystem::deleteDirectory(Ge::$app->modulePath . $decrypt['path'])) {
            $response
                ->meta
                    ->cmdPopupMsg(
                        $this->t('Deleting of widget completed successfully'), 
                        $this->t('Deleting'), 
                        'accept'
                    )
                    ->cmdReloadGrid($this->module->viewId('grid'));
        } else {
            $response
                ->meta->error(
                    Ge::t('app', 'Could not perform directory deletion "{0}"', [Ge::$app->modulePath . $decrypt['path']])
                );
        }
        return $response;
    }

    /**
     * Действие "info" возвращает информацию о виджете.
     * 
     * @return Response
     */
    public function infoAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|string $widgetId Идентификатор виджета */
        $widgetId = Ge::$app->request->get('id');
        if (empty($widgetId)) {
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', ['id']));
            return $response;
        }

        /** @var InformationTab $tab */
        $tab = new InformationTab();
        /** @var null|array $widgetInfo*/
        $widgetInfo = $tab->getWidgetInfo($widgetId);

        // если виджет не найден
        if ($widgetInfo === null) {
            $response
                ->meta->error($this->module->t('There is no widget with the specified id "{0}"', [$widgetId]));
            return $response;
        }

        // панель (Ext.panel Sencha ExtJS)
        $tab->panel->html = $this->getViewManager()->renderPartial('widget-info', $widgetInfo);
        // панель вкладки компонента (Ge.view.tab.Components GeJS)
        $tab->title = $this->module->t('{info.title}', [$widgetInfo['name']]);
        $tab->icon  = Ge::$app->moduleUrl . $widgetInfo['path'] . '/assets/images/icon_small.svg';
        $tab->tooltip = [
            'icon'  => Ge::$app->moduleUrl . $widgetInfo['path'] . '/assets/images/icon.svg',
            'title' => $tab->title,
            'text'  => $widgetInfo['description']
        ];

        $response
            ->setContent($tab->run())
            ->meta
                ->addWidget($tab);
        return $response;
    }
}
