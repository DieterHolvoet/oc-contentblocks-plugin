<?php

namespace DieterHolvoet\ContentBlocks;

use DieterHolvoet\ContentBlocks\Classes\ContainerDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockManager;
use DieterHolvoet\ContentBlocks\Classes\HostDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\HostManager;
use DieterHolvoet\ContentBlocks\EventListeners\BackendFormEventListener;
use DieterHolvoet\ContentBlocks\EventListeners\PageSaveEventListener;
use DieterHolvoet\ContentBlocks\Extenders\PageExtender;
use DieterHolvoet\ContentBlocks\Extenders\ContentBlockExtender;
use DieterHolvoet\ContentBlocks\Models\Settings;
use DieterHolvoet\ContentBlocks\Validators\ContentBlockPluginValidator;
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
            'name'        => 'dieterholvoet.contentblocks::plugin.name',
            'description' => 'dieterholvoet.contentblocks::plugin.description',
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
                'label'       => 'dieterholvoet.contentblocks::menu.settings.label',
                'description' => 'dieterholvoet.contentblocks::menu.settings.description',
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

        $this->app->bind('dieterholvoet.contentBlocks.containerDefinitionManager', function () {
            return new ContainerDefinitionManager(
                Settings::instance()
            );
        });

        $this->app->bind('dieterholvoet.contentBlocks.contentBlockDefinitionManager', function (ContainerInterface $container) {
            return new ContentBlockDefinitionManager(
                $container->get('files'),
                PluginManager::instance(),
                $container->get('dieterholvoet.contentBlocks.containerDefinitionManager'),
                Settings::instance()
            );
        });

        $this->app->bind('dieterholvoet.contentBlocks.contentBlockManager', function (ContainerInterface $container) {
            return new ContentBlockManager(
                $container->get('dieterholvoet.contentBlocks.hostDefinitionManager'),
                $container->get('dieterholvoet.contentBlocks.contentBlockDefinitionManager')
            );
        });

        $this->app->bind('dieterholvoet.contentBlocks.hostDefinitionManager', function (ContainerInterface $container) {
            return new HostDefinitionManager(
                config('cms.activeTheme')
            );
        });

        $this->app->bind('dieterholvoet.contentBlocks.hostManager', function (ContainerInterface $container) {
            return new HostManager(
                $container->get('dieterholvoet.contentBlocks.hostDefinitionManager')
            );
        });

        /**
         * Event listeners
         */

        $this->app->bind('dieterholvoet.contentBlocks.backendFormListener', function (ContainerInterface $container) {
            return new BackendFormEventListener(
                PluginManager::instance(),
                $container->get('dieterholvoet.contentBlocks.contentBlockDefinitionManager'),
                $container->get('dieterholvoet.contentBlocks.containerDefinitionManager'),
                Settings::instance()
            );
        });

        $this->app->bind('dieterholvoet.contentBlocks.pageSaveListener', function (ContainerInterface $container) {
            return new PageSaveEventListener(
                $container->get('db'),
                $container->get('dieterholvoet.contentBlocks.contentBlockDefinitionManager'),
                $container->get('dieterholvoet.contentBlocks.hostDefinitionManager'),
                Settings::instance()
            );
        });

        /**
         * Extenders
         */

        $this->app->bind('dieterholvoet.contentBlocks.contentBlockExtender', function (ContainerInterface $container) {
            return new ContentBlockExtender(
                $container->get('dieterholvoet.contentBlocks.contentBlockDefinitionManager'),
                $container->get('dieterholvoet.contentBlocks.hostManager')
            );
        });

        $this->app->bind('dieterholvoet.contentBlocks.pageExtender', function (ContainerInterface $container) {
            return new PageExtender(
                $container->get('dieterholvoet.contentBlocks.contentBlockDefinitionManager'),
                $container->get('dieterholvoet.contentBlocks.contentBlockManager')
            );
        });

        /**
         * Validators
         */

        $this->app->bind('dieterholvoet.contentBlocks.validator.contentBlockPlugin', function (ContainerInterface $container) {
            return new ContentBlockPluginValidator(
                $container->get('files'),
                $container->get('dieterholvoet.contentBlocks.containerDefinitionManager')
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

        Event::listen('backend.form.extendFields', 'dieterholvoet.contentBlocks.backendFormListener@onExtendFields');
        Event::listen('cms.template.save', 'dieterholvoet.contentBlocks.pageSaveListener@onCmsPageSave');
        Event::listen('pages.object.save', 'dieterholvoet.contentBlocks.pageSaveListener@onStaticPageSave');

        /**
         * Extenders
         */

        /** @var Extendable[] $hostModels */
        $hostModels = [\Cms\Classes\Page::class, \RainLab\Pages\Classes\Page::class];

        foreach ($hostModels as $model) {
            $model::extend(function ($model) {
                $this->app->get('dieterholvoet.contentBlocks.pageExtender')->extend($model);
            });
        }

        /** @var Extendable[] $contentBlockModels */
        $contentBlockModels = app('dieterholvoet.contentBlocks.contentBlockDefinitionManager')->getModels();

        foreach ($contentBlockModels as $model) {
            $model::extend(function ($model) {
                $this->app->get('dieterholvoet.contentBlocks.contentBlockExtender')->extend($model);
            });
        }

        /**
         * Validators
         */

        Validator::resolver(function($translator, $data, $rules, $messages, $customAttributes) {
            $messages = array_merge($messages, Lang::get('dieterholvoet.contentblocks::validation'));
            return new \Illuminate\Validation\Validator($translator, $data, $rules, $messages, $customAttributes);
        });

        Validator::extend('content_block_has_containers', 'dieterholvoet.contentBlocks.validator.contentBlockPlugin@hasContainerDefinition');
    }
}
