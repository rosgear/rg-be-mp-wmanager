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
use Ge\Panel\Controller\BaseController;
use Rg\Backend\Marketplace\WidgetManager\Widget\InformationTab;

/**
 * Контроллер информации о виджете.
 * 
 * Действия контроллера:
 * - index, информация о виджете;
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\WidgetManager\Controller
 * @since 1.0
 */
class WidgetInfo extends BaseController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Marketplace\WidgetManager\Extension
     */
    public BaseModule $module;

    /**
     * Действие "info" возвращает информацию о виджете.
     * 
     * @return Response
     */
    public function indexAction(): Response
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
