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
use Ge\Panel\Helper\ExtForm;
use Ge\Mvc\Module\BaseModule;
use Ge\Panel\Widget\SettingsWindow;
use Ge\Panel\Controller\FormController;

/**
 * Контроллер настройки виджета.
 * 
 * Действия контроллера:
 * - view, вывод интерфейса настроек виджета;
 * - data, вывод настроек виджета по указанному идентификатору;
 * - update, изменение настроек виджета по указанному идентификатору.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\WidgetManager\Controller
 * @since 1.0
 */
class WidgetSettings extends FormController
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
            // просмтор настроек
            case 'data':
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
     * {@inheritdoc}
     */
    public function createWidget(): SettingsWindow
    {
        return new SettingsWindow();
    }

    /**
     * Действие "view" выводит интерфейс настроек виджета.
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
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', ['id']));
            return $response;
        }

        /** @var null|array $widgetParams */
        $widgetParams = Ge::$app->widgets->getRegistry()->getAt($id);
        // если виджет не найден
        if ($widgetParams === null) {
            $response
                ->meta->error($this->module->t('There is no widget with the specified id "{0}"', [$id]));
            return $response;
        }

        // для доступа к пространству имён объекта
        Ge::$loader->addPsr4($widgetParams['namespace']  . NS, Ge::$app->modulePath . $widgetParams['path'] . DS . 'src');

        $settingsClass = $widgetParams['namespace'] . NS . 'Settings' . NS . 'Settings';
        if (!class_exists($settingsClass)) {
            $response
                ->meta->error($this->module->t('Unable to create widget object "{0}"', [$settingsClass]));
            return $response;
        }

        // т.к. виджет самостоятельно не может подключать свою локализацию (в данном случаи делает это модуль), 
        // то добавляем шаблон локализации виджета модулю
        $category = Ge::$app->translator->getCategory($this->module->id);
        $category->patterns['widget'] = [
            'basePath' => Ge::$app->modulePath . $widgetParams['path'] . DS . 'lang',
            'pattern'  => 'text-%s.php',
        ];
        $this->module->addTranslatePattern('widget');

        /** @var object|Ge\Panel\Widget\SettingsWindow $widget Виджет настроек */
        $widget = Ge::createObject($settingsClass);
        if ($widget instanceof Ge\Panel\Widget\SettingsWindow) {
            // панель формы (Ge.view.form.Panel GeJS)
            $widget->form->router->route = $this->module->route('/wsettings');
            $widget->form->router->id    = $id;
            $widget->form->buttons = ExtForm::buttons([
                'help' => [
                    'component' => 'widget:' . $widgetParams['id'],
                    'subject'   => 'settings'
                ], 
                'reset', 'save', 'cancel'
            ]);
            $widget->titleTpl = $this->module->t('{settings.title}');
        }

        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }

    /**
     * Действие "data" выводит настройки виджета по указанному идентификатору.
     *
     * @return Response
     */
    public function dataAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|int $id Идентификатор виджета */
        $id = $this->getIdentifier();
        if (empty($id)) {
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', ['id']));
            return $response;
        }

        /** @var null|array $widgetParams Параметры виджета */
        $widgetParams = Ge::$app->widgets->getRegistry()->getAt($id);
        // если виджет не найден
        if ($widgetParams === null) {
            $response
                ->meta->error($this->module->t('There is no widget with the specified id "{0}"', [$id]));
            return $response;
        }

        /** @var null|\Ge\Data\Model\RecordModel|\Ge\Panel\Data\Model\WidgetSettingsModel $model */
        $model = Ge::$app->widgets->getModel(
            'Settings', $widgetParams['id'], ['basePath' => Ge::$app->modulePath . $widgetParams['path'], 'module' => $this->module]
        );
        // если модель данных не определена
        if ($model === null) {
            $response
                ->meta->error(Ge::t('app', 'Could not defined data model "{0}"', ['Settings']));
            return $response;
        }

        /** @var null|\Ge\Data\Model\RecordModel|\Ge\Panel\Data\Model\WidgetSettingsModel $form */
        $form = $model->get();
        if ($form === null) {
            $response
                ->meta->error(
                    $model->hasErrors() ? $model->getError() : Ge::t(BACKEND, 'The item you selected does not exist or has been deleted')
                );
            return $response;
        }

        return $response->setContent($form->getAttributes());
    }

    /**
     * Действие "update" изменяет настройки виджета по указанному идентификатору.
     * 
     * @return Response
     */
    public function updateAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Ge\Http\Request $request */
        $request  = Ge::$app->request;

        /** @var null|int $id Идентификатор виджета */
        $id = $this->getIdentifier();
        if (empty($id)) {
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', ['id']));
            return $response;
        }

        /** @var null|array $widgetParams Параметры виджета */
        $widgetParams = Ge::$app->widgets->getRegistry()->getAt($id);
        // если виджет не найден
        if ($widgetParams === null) {
            $response
                ->meta->error($this->module->t('There is no widget with the specified id "{0}"', [$id]));
            return $response;
        }

        /** @var null|\Ge\Data\Model\RecordModel|\Ge\Panel\Data\Model\WidgetSettingsModel $model */
        $model = Ge::$app->widgets->getModel(
            'Settings', $widgetParams['id'], ['basePath' => Ge::$app->modulePath . $widgetParams['path'], 'module' => $this->module]
        );
        // если модель данных не определена
        if ($model === null) {
            $response
                ->meta->error(Ge::t('app', 'Could not defined data model "{0}"', ['Settings']));
            return $response;
        }

        /** @var null|\Ge\Data\Model\RecordModel|\Ge\Panel\Data\Model\WidgetSettingsModel $form */
        $form = $model->get();
        if ($form === null) {
            $response
                ->meta->error(
                    $model->hasErrors() ? $model->getError() : $this->t('Unable to get widget settings')
                );
            return $response;
        }

        // т.к. виджет самостоятельно не может подключать свою локализацию (в данном случаи делает это модуль), 
        // то добавляем шаблон локализации виджета модулю
        $category = Ge::$app->translator->getCategory($this->module->id);
        $category->patterns['widget'] = [
            'basePath' => Ge::$app->modulePath . $widgetParams['path'] . DS . 'lang',
            'pattern'  => 'text-%s.php',
        ];
        $this->module->addTranslatePattern('widget');

        // загрузка атрибутов в модель из запроса
        if (!$form->load($request->getPost())) {
            $response
                ->meta->error(Ge::t(BACKEND, 'No data to perform action'));
            return $response;
        }

        // валидация атрибутов модели
        if (!$form->validate()) {
            $response
                ->meta->error(Ge::t(BACKEND, 'Error filling out form fields: {0}', [$form->getError()]));
            return $response;
        }

        // сохранение атрибутов модели
        if (!$form->save()) {
            $response
                ->meta->error(
                    $form->hasErrors() ? $form->getError() : Ge::t(BACKEND, 'Could not save data')
                );
            return $response;
        } else {
            // всплывающие сообщение
            $response
                ->meta
                    ->cmdPopupMsg($this->t('Widget settings successfully changed'), $this->t('Widget settings'), 'accept');
        }
        return $response;
    }
}
