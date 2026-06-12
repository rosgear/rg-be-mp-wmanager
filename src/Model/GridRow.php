<?php
/**
 * Этот файл является частью расширения модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Marketplace\WidgetManager\Model;

use Ge;
use Ge\Panel\Data\Model\FormModel;

/**
 * Модель данных профиля записи установленного виджета.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\WidgetManager\Model
 * @since 1.0
 */
class GridRow extends FormModel
{
    /**
     * Идентификатор выбранного виджета.
     * 
     * @see GridRow::afterValidate()
     * 
     * @var string
     */
    protected ?string $widgetId;

    /**
     * Имя выбранного виджета.
     * 
     * @see GridRow::afterValidate()
     * 
     * @var string
     */
    public ?string $widgetName;

    /**
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'tableName'  => '{{widget}}',
            'primaryKey' => 'id',
            'fields'     => [
                ['id'],
                ['name'], 
                ['enabled', 'label' => 'Enabled']
            ],
            'useAudit' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_AFTER_SAVE, function ($isInsert, $columns, $result, $message) {
                if ($message['success']) {
                    if (isset($columns['enabled'])) {
                        $enabled = (int) $columns['enabled'];
                        $message['message'] = $this->module->t('Widget {0} - ' . ($enabled > 0 ? 'enabled' : 'disabled'), [$this->widgetName]);
                        $message['title']   = $this->module->t($enabled > 0 ? 'Enabled' : 'Disabled');
                    }
                }
                // всплывающие сообщение
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
            });
    }

    /**
     * {@inheritDoc}
     */
    public function afterValidate(bool $isValid): bool
    {
        if ($isValid) {
            /** @var \Ge\Http\Request $request */
            $request  = Ge::$app->request;
            // имя виджета
            $this->widgetName = $request->post('name');
            if (empty($this->widgetName)) {
                $this->setError(Ge::t('app', 'Parameter passed incorrectly "{0}"', ['Name']));
                return false;
            }
            // идентификатор виджета
            $this->widgetId = $request->post('widgetId');
            if (empty($this->widgetId)) {
                $this->setError(Ge::t('app', 'Parameter passed incorrectly "{0}"', ['Widget Id']));
                return false;
            }
            if (!Ge::$app->widgets->getRegistry()->has($this->widgetId)) {
                $this->setError($this->module->t('There is no widget with the specified id "{0}"', [$this->widgetId]));
                return false;
            }
        }
        return $isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeUpdate(array &$columns): void
    {
        /** @var \Ge\WidgetManager\WidgetRegistry $installed */
        $installed = Ge::$app->widgets->getRegistry();
        /** @var \Ge\Http\Request $request */
        $request = Ge::$app->request;
        // доступность виджета
        $enabled = $request->getPost('enabled', 0, 'int');
        $installed->set($this->widgetId, ['enabled' => (bool) $enabled], true);
    }
}
