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
use Ge\Mvc\Module\BaseModule;
use Ge\Panel\Controller\FormController;
use Rg\Backend\Marketplace\WidgetManager\Widget\InstallWindow;

/**
 * Контроллер установки виджета.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\WidgetManager\Controller
 * @since 1.0
 */
class Install extends FormController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Marketplace\WidgetManager\Extension
     */
    public BaseModule $module;

    /**
     * {@inheritdoc}
     * 
     * @return InstallWindow
     */
    public function createWidget(): InstallWindow
    {
        return new InstallWindow();
    }

    /**
     * Действие "complete" завершает установку расширения.
     * 
     * @return Response
     */
    public function completeAction(): Response
    {
        // добавляем шаблон локализации для установки (см. ".widget.php")
        $this->module->addTranslatePattern('install');

        /** @var \Ge\WidgetManager\WidgetManager $manager Менеджер виджетов */
        $manager = Ge::$app->widgets;
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|string Идентификатор установки виджета */
        $installId = Ge::$app->request->post('installId');

        /** @var string|array Расшифровка идентификатора установки виджета */
        $decrypt = $manager->decryptInstallId($installId);
        if (is_string($decrypt)) {
            Ge::debug('Install', [
                'method'    => get_class($manager) . '::decryptInstallId()',
                'installId' => $installId
            ]);
            $response
                ->meta->error($decrypt);
            return $response;
        }

        // если виджет не имеет установщика "Installer\Installer.php"
        if (!$manager->installerExists($decrypt['path'])) {
            Ge::debug('Install', [
                'method'    => get_class($manager) . '::decryptInstallId()',
                'installId' => $installId
            ]);
            $response
                ->meta->error($this->module->t('The widget installer at the specified path "{0}" does not exist', [$decrypt['path']]));
            return $response;
        }
        
        // каждый виджет обязано иметь установщик, управление установщиком передаётся текущему модулю
        /** @var \Ge\WidgetManager\WidgetInstaller $installer Установщик виджета */
        $installer = $manager->getInstaller([
            'module'    => $this->module, 
            'namespace' => $decrypt['namespace'],
            'path'      => $decrypt['path'], 
            'installId' => $installId
        ]);

        // если установщик не создан
        if ($installer === null) {
            Ge::debug('Install', [
                'method' => get_class($manager) . '::getInstaller()',
                'error'  => $this->t('Unable to create widget installer'),
                'params' => [
                    'module'    => get_class($this->module),
                    'namespace' => $decrypt['namespace'],
                    'path'      => $decrypt['path'], 
                    'installId' => $installId
                ]
            ]);
            $response
                ->meta->error($this->t('Unable to create widget installer'));
            return $response;
        }

        // устанавливает виджет
        if ($installer->install()) {
            $response
                ->meta
                    ->cmdPopupMsg(
                        $this->module->t('Widget installation "{0}" completed successfully', [$installer->info['name']]),
                        $this->t('Installing'),
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
     * Действие "view" выводит интерфейс установщика виджета.
     * 
     * @return Response
     */
    public function viewAction(): Response
    {
        // добавляем шаблон локализации для установки (см. ".widget.php")
        $this->module->addTranslatePattern('install');

        /** @var \Ge\WidgetManager\WidgetManager */
        $widgets = Ge::$app->widgets;
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|string Идентификатор установки виджета */
        $installId = Ge::$app->request->post('installId');

        /** @var string|array Расшифровка идентификатора установки виджета */
        $decrypt = $widgets->decryptInstallId($installId);
        if (is_string($decrypt)) {
            Ge::debug('Install', [
                'method'    => get_class($widgets) . '::decryptInstallId()',
                'installId' => $installId
            ]);
            $response
                ->meta->error($decrypt);
            return $response;
        }

        // если виджет не имеет установщика "Installer\Installer.php"
        if (!$widgets->installerExists($decrypt['path'])) {
            Ge::debug('Install', [
                'method' => get_class($widgets) . '::installerExists',
                'error'  => $this->module->t('The widget installer at the specified path "{0}" does not exist', [$decrypt['path']])
            ]);
            $response
                ->meta->error($this->module->t('The widget installer at the specified path "{0}" does not exist', [$decrypt['path']]));
            return $response;
        }

        // каждый виджет обязано иметь установщик, управление установщиком передаётся текущему модулю
        /** @var \Ge\WidgetManager\WidgetInstaller|null $installer Установщик виджета */
        $installer = $widgets->getInstaller([
            'module'    => $this->module, 
            'namespace' => $decrypt['namespace'],
            'path'      => $decrypt['path'], 
            'installId' => $installId
        ]);

        // если установщик не создан
        if ($installer === null) {
            Ge::debug('Install', [
                'method' => get_class($widgets) . '::getInstaller()',
                'error'  => $this->t('Unable to create widget installer'),
                'params' => [
                    'module'    => get_class($this->module),
                    'namespace' => $decrypt['namespace'],
                    'path'      => $decrypt['path'], 
                    'installId' => $installId
                ]
            ]);
            $response
                ->meta->error($this->t('Unable to create widget installer'));
            return $response;
        }

        /** @var null|\Ge\Panel\Widget\BaseWidget|\Ge\View\Widget $widget */
        $widget = $installer->getWidget();
        // если установщик не имеет виджет
        if ($widget === null) {
            /** @var InstallWindow $widget */
            $widget = $this->getWidget();
        }
        $widget->info = $installer->getWidgetInfo();

       // проверка конфигурации устанавливаемого виджета
        if (!$installer->validateInstall()) {
            Ge::debug('Install', [
                'method' => get_class($installer) . '::validateInstall()',
                'error'  => $installer->getError()
            ]);
            $widget->notice = $installer->getError();
        }

        // если была ошибка при формировании виджета
        if ($widget === false) {
            return $response;
        }

        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }
}
