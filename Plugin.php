<?php

namespace DieterHolvoet\ContentBlock;

use DieterHolvoet\ContentBlock\Classes\ContainerDefinitionManager;
use DieterHolvoet\ContentBlock\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlock\Classes\ContentBlockManager;
use DieterHolvoet\ContentBlock\Classes\HostDefinitionManager;
use DieterHolvoet\ContentBlock\Classes\HostManager;
use DieterHolvoet\ContentBlock\EventListeners\BackendFormEventListener;
use DieterHolvoet\ContentBlock\EventListeners\PageSaveEventListener;
use DieterHolvoet\ContentBlock\Extenders\PageExtender;
use DieterHolvoet\ContentBlock\Extenders\ContentBlockExtender;
use DieterHolvoet\ContentBlock\Models\Settings;
use DieterHolvoet\ContentBlock\Validators\ContentBlockPluginValidator;
use Event;
use Illuminate\Support\Facades\Validator;
use Lang;
use October\Rain\Extension\Extendable;
use Psr\Container\ContainerInterface;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use System\Classes\SettingsManager;

class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'dieterholvoet.contentblock::plugin.name',
            'description' => 'dieterholvoet.contentblock::plugin.description',
            'author'      => 'Dieter Holvoet',
            'icon'        => 'icon-th-large'
        ];
    }

    /**
     * Registers any back-end configuration links used by this plugin.
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'location' => [
                'label'       => 'dieterholvoet.contentblock::menu.settings.label',
                'description' => 'dieterholvoet.contentblock::menu.settings.description',
                'category'    => SettingsManager::CATEGORY_CMS,
                'icon'        => 'icon-th-large',
                'class'       => Settings::class,
                'order'       => 500,
                'keywords'    => 'content blocks contentblocks paragraphs models'
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        /**
         * Managers
         */

        $this->app->bind('dieterholvoet.contentBlock.containerDefinitionManager', function () {
            return new ContainerDefinitionManager(
                Settings::instance()
            );
        });

        $this->app->bind('dieterholvoet.contentBlock.contentBlockDefinitionManager', function (ContainerInterface $container) {
            return new ContentBlockDefinitionManager(
                $container->get('files'),
                PluginManager::instance(),
                $container->get('dieterholvoet.contentBlock.containerDefinitionManager'),
                Settings::instance()
            );
        });

        $this->app->bind('dieterholvoet.contentBlock.contentBlockManager', function (ContainerInterface $container) {
            return new ContentBlockManager(
                $container->get('dieterholvoet.contentBlock.hostDefinitionManager'),
                $container->get('dieterholvoet.contentBlock.contentBlockDefinitionManager')
            );
        });

        $this->app->bind('dieterholvoet.contentBlock.hostDefinitionManager', function (ContainerInterface $container) {
            return new HostDefinitionManager(
                config('cms.activeTheme')
            );
        });

        $this->app->bind('dieterholvoet.contentBlock.hostManager', function (ContainerInterface $container) {
            return new HostManager(
                $container->get('dieterholvoet.contentBlock.hostDefinitionManager')
            );
        });

        /**
         * Event listeners
         */

        $this->app->bind('dieterholvoet.contentBlock.backendFormListener', function (ContainerInterface $container) {
            return new BackendFormEventListener(
                PluginManager::instance(),
                $container->get('dieterholvoet.contentBlock.contentBlockDefinitionManager'),
                $container->get('dieterholvoet.contentBlock.containerDefinitionManager'),
                Settings::instance()
            );
        });

        $this->app->bind('dieterholvoet.contentBlock.pageSaveListener', function (ContainerInterface $container) {
            return new PageSaveEventListener(
                $container->get('db'),
                $container->get('dieterholvoet.contentBlock.contentBlockDefinitionManager'),
                $container->get('dieterholvoet.contentBlock.hostDefinitionManager')
            );
        });

        /**
         * Extenders
         */

        $this->app->bind('dieterholvoet.contentBlock.contentBlockExtender', function (ContainerInterface $container) {
            return new ContentBlockExtender(
                $container->get('dieterholvoet.contentBlock.contentBlockDefinitionManager'),
                $container->get('dieterholvoet.contentBlock.hostManager')
            );
        });

        $this->app->bind('dieterholvoet.contentBlock.pageExtender', function (ContainerInterface $container) {
            return new PageExtender(
                $container->get('dieterholvoet.contentBlock.contentBlockDefinitionManager'),
                $container->get('dieterholvoet.contentBlock.contentBlockManager')
            );
        });

        /**
         * Validators
         */

        $this->app->bind('dieterholvoet.contentBlock.validator.contentBlockPlugin', function (ContainerInterface $container) {
            return new ContentBlockPluginValidator(
                $container->get('files'),
                $container->get('dieterholvoet.contentBlock.containerDefinitionManager')
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        /**
         * Event listeners
         */

        Event::listen('backend.form.extendFields', 'dieterholvoet.contentBlock.backendFormListener@onExtendFields');
        Event::listen('cms.template.save', 'dieterholvoet.contentBlock.pageSaveListener@onCmsPageSave');
        Event::listen('pages.object.save', 'dieterholvoet.contentBlock.pageSaveListener@onStaticPageSave');

        /**
         * Extenders
         */

        /** @var Extendable[] $hostModels */
        $hostModels = [\Cms\Classes\Page::class, \RainLab\Pages\Classes\Page::class];

        foreach ($hostModels as $model) {
            $model::extend(function ($model) {
                $this->app->get('dieterholvoet.contentBlock.pageExtender')->extend($model);
            });
        }

        /** @var Extendable[] $contentBlockModels */
        $contentBlockModels = app('dieterholvoet.contentBlock.contentBlockDefinitionManager')->getModels();

        foreach ($contentBlockModels as $model) {
            $model::extend(function ($model) {
                $this->app->get('dieterholvoet.contentBlock.contentBlockExtender')->extend($model);
            });
        }

        /**
         * Validators
         */

        Validator::resolver(function($translator, $data, $rules, $messages, $customAttributes) {
            $messages = array_merge($messages, Lang::get('dieterholvoet.contentblock::validation'));
            return new \Illuminate\Validation\Validator($translator, $data, $rules, $messages, $customAttributes);
        });

        Validator::extend('content_block_has_containers', 'dieterholvoet.contentBlock.validator.contentBlockPlugin@hasContainerDefinition');
    }
}
