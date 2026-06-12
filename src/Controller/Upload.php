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
use Ge\Panel\Controller\FormController;
use Rg\Backend\Marketplace\WidgetManager\Widget\UploadWindow;

/**
 * Контроллер загрузки файла пакета виджета.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\WidgetManager\Controller
 * @since 1.0
 */
class Upload extends FormController
{
    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'UploadForm';

    /**
     * {@inheritdoc}
     */
    public function createWidget(): UploadWindow
    {
        return new UploadWindow();
    }

    /**
     * Действие "perfom" выполняет загрузку файла или подтверждает запрос.
     * 
     * @return Response
     */
    public function perfomAction(): Response
    {
        /** @var \Ge\Panel\Http\Response $response */
        $response = $this->getResponse();
        /** @var \Ge\Http\Request $request */
        $request  = Ge::$app->request;

        /** @var \Rg\Backend\Marketplace\WidgetManager\Model\UploadForm $form */
        $form = $this->getModel($this->defaultModel);
        if ($form === null) {
            $response
                ->meta->error(Ge::t('app', 'Could not defined data model "{0}"', [$this->defaultModel]));
            return $response;
        }

        if ($this->useAppEvents) {
            Ge::$app->doEvent($this->module->id . ':onFormAction', [$this->module, $form, 'upload']);
        }

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

        // загрузка файла
        if (!$form->upload()) {
            $response
                ->meta->error(
                    $form->hasErrors() ? $form->getError() : $this->module->t('File uploading error')
                );
            return $response;
        }

        if ($this->useAppEvents) {
            Ge::$app->doEvent($this->module->id . ':onAfterFormAction', [$this->module, $form, 'upload']);
        }
        return $response;
    }
}
