<?php
/**
 * Этот файл является частью расширения модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Marketplace\WidgetManager\Widget;

use Ge;
use Ge\Panel\Widget\Widget;
use Ge\Panel\Widget\TabWidget;

/**
 * Виджет для формирования вкладки c информацией о виджете.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\WidgetManager\Widget
 * @since 1.0
 */
class InformationTab extends TabWidget
{
    /**
     * Панель вкладки (Ext.panel.Panel Sencha ExtJS).
     * 
     * @var Widget
     */
    public Widget $panel;

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        // панель вкладки (Ext.panel.Panel Sencha ExtJS)
        $this->panel = new Widget([
            'bodyCls'    => 'g-widget-info__body',
            'scrollable' => true
        ], $this);

        $this->bodyPadding = 0;
        $this->id    = 'tab-info';
        $this->cls   = 'g-module-info g-panel_background';
        $this->items = [$this->panel];
    }

    /**
     * Возвращает информацию о виджете.
     * 
     * @param string $widgetId Идентификатор виджета.
     * 
     * @return array|null
     */
    public function getWidgetInfo(string $widgetId): ?array
    {
        /** @var \Ge\WidgetManager\WidgetManager $widgets Менеджер виджетов */
        $widgets = Ge::$app->widgets;
        /** @var \Ge\WidgetManager\WidgetRegistry $registry Установленные виджеты */
        $registry = $widgets->getRegistry();

        /** @var array|null $info Информация о виджете */
        $info = $registry->getInfo($widgetId, true);
        if ($info === null) {
            return null;
        }

        /* Локализация виджета для определения имени и описания */
        $name = $widgets->selectName($info['rowId']);
        // если есть перевод
        if ($name) {
            $info['name'] = $name['name'];
            $info['description'] = $name['description'];
        }

        /* Раздел "Модуль установлен" */
        // дата установки модуля
        $info['createdDate'] = null;
        // пользователь устанавливавший модуль
        $info['createdUser'] = null;
        // модуль из базы данных
        $widget = $widgets->selectOne($widgetId, true);
        if ($widget) {
            if ($widget['createdDate']) {
                $info['createdDate'] = Ge::$app->formatter->toDateTime($widget['createdDate']);
            }
            if ($widget['createdUser']) {
                $userId = (int) $widget['createdUser'];
                /** @var \Ge\Panel\User\UserIdentity $user */
                $user = Ge::userIdentity();
                /** @var \Ge\Panel\User\UserProfile $profile */
                $profile = Ge::userIdentity()->getProfile();
                // переопределяем
                $info['createdUser'] = [
                    'user'    => $user->findOne(['id' => $userId ]),
                    'profile' => $profile->findOne(['user_id' => $userId])
                ];
            }
        }
        return $info;
    }
}
