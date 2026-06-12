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

/**
 * Контроллер настройки шорткода виджета.
 * 
 * Действия контроллера:
 * - view, вывод интерфейса настроек шорткода виджета.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\WidgetManager\Controller
 * @since 1.0
 */
class ShortcodeSettings extends FormController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Marketplace\WidgetManager\Extension
     */
    public BaseModule $module;

    /**
     * {@inheritdoc}
     */
    public function translateAction(mixed $params, ?string $default = null): ?string
    {
        switch ($this->actionName) {
            // вывод интерфейса
            case 'view':
                return Ge::t(BACKEND, "{{$this->actionName} settings action}");

            default:
                return parent::translateAction(
                    $params,
                    $default ?: Ge::t(BACKEND, "{{$this->actionName} settings action}")
                );
        }
    }

    /**
     * Возвращает идентификатор выбранного виджета.
     *
     * @return int
     */
    public function getIdentifier(): int
    {
        return (int) Ge::$app->router->get('id');
    }

    /**
     * Действие "view" выводит интерфейс настроек шорткода виджета.
     * 
     * @return Response
     */
    public function viewAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|int $id Идентификатор виджета */
        $id = $this->getIdentifier();
        if (empty($id)) {
            return $this->errorResponse(
                GE_MODE_DEV ?
                    Ge::t('app', 'Parameter "{0}" not specified', ['id']) :
                    $this->module->t('Unable to show widget shortcode settings')
            );
        }

        /** @var null|string $tagName Имя тега */
        $tagName = Ge::$app->request->getQuery('name');
        if (empty($tagName)) {
            return $this->errorResponse(
                GE_MODE_DEV ?
                    Ge::t('app', 'Parameter "{0}" not specified', ['name']) :
                    $this->module->t('Unable to show widget shortcode settings')
            );
        }

        /** @var null|array $widgetParams Параметры виджета */
        $widgetParams = Ge::$app->widgets->getRegistry()->getAt($id);
        if ($widgetParams === null) {
            return $this->errorResponse(
                GE_MODE_DEV ?
                    Ge::t('app', 'There is no widget with the specified id "{0}"', ['$id']) :
                    $this->module->t('Unable to show widget shortcode settings')
            );
        }

        /** @var null|array $install Параметры установки виджета */
        $install = Ge::$app->widgets->getRegistry()->getConfigInstall($id);
        // если нет параметров установки
        if ($install === null) {
            return $this->errorResponse(
                GE_MODE_DEV ?
                    Ge::t('app', 'There is no widget with the specified id "{0}"', ['$id']) :
                    $this->module->t('Unable to show widget shortcode settings')
            );
        }

        /** @var array|null $shortcode Параметры указанного шорткода виджета */
        $shortcode = $install['editor']['shortcodes'][$tagName] ?? null;
        if (empty($shortcode)) {
            return $this->errorResponse(
                GE_MODE_DEV ?
                    Ge::t('app', 'Parameter passed incorrectly "{0}"', ['shortcodes[' . $tagName . ']']) :
                    $this->module->t('Unable to show widget shortcode settings')
            );
        }

        // если нет настроек шорткода
        if (empty($shortcode['settings'])) {
            return $this->errorResponse(
                GE_MODE_DEV ?
                    Ge::t('app', 'The value for parameter "{0}" is missing', ['shortcodes[settings]']) :
                    $this->module->t('Unable to show widget shortcode settings')
            );
        }

        // для доступа к пространству имён объекта
        Ge::$loader->addPsr4($widgetParams['namespace']  . NS, Ge::$app->modulePath . $widgetParams['path'] . DS . 'src');

        $settingsClass = $widgetParams['namespace'] . NS . $shortcode['settings'];
        if (!class_exists($settingsClass)) {
            return $this->errorResponse(
                $this->module->t('Unable to create widget object "{0}"', [$settingsClass])
            );
        }

        // т.к. виджет самостоятельно не может подкличать свою локализацию, то делает это менеджер виджетов (Extension), 
        // подключая локализации виджета себе
        $category = Ge::$app->translator->getCategory($this->module->id);
        $category->patterns['widget'] = [
            'basePath' => Ge::$app->modulePath . $widgetParams['path'] . DS . 'lang',
            'pattern'  => 'text-%s.php',
        ];
        $this->module->addTranslatePattern('widget');

        /** @var object|Ge\Panel\Widget\ShortcodeSettingsWindow $widget Виджет настроек шорткода */
        $widget = Ge::createObject($settingsClass);
        if ($widget instanceof Ge\Panel\Widget\ShortcodeSettingsWindow) {
            $widget->form->controller = 'rg-mp-wmanager-shortcodesettings';
            $widget
                ->setNamespaceJS('Rg.be.mp.wmanager')
                ->addRequire('Rg.be.mp.wmanager.ShortcodeSettingsController' . (GE_DEBUG ? '-debug' : ''));
        }

        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }
}
