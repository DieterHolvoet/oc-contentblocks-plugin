<?php

namespace DieterHolvoet\ContentBlocks\EventListeners;

use Backend\Classes\AuthManager;
use Backend\Models\User;
use Backend\Widgets\Form;
use DieterHolvoet\ContentBlocks\Classes\ContainerDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlocks\Models\Settings;
use System\Classes\PluginManager;

class BackendFormEventListener
{
    /** @var User */
    protected $user;
    /** @var PluginManager */
    protected $pluginManager;
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;
    /** @var ContainerDefinitionManager */
    protected $containerDefinitions;
    /** @var Settings */
    protected $settings;

    public function __construct(
        AuthManager $authManager,
        PluginManager $pluginManager,
        ContentBlockDefinitionManager $contentBlockDefinitions,
        ContainerDefinitionManager $containerDefinitions,
        Settings $settings
    ) {
        $this->user = $authManager->getUser();
        $this->pluginManager = $pluginManager;
        $this->contentBlockDefinitions = $contentBlockDefinitions;
        $this->containerDefinitions = $containerDefinitions;
        $this->settings = $settings;
    }

    public function onExtendFields(Form $widget)
    {
        if (!$this->settings->getModelsPlugin()) {
            return;
        }

        if (
            $this->pluginManager->hasPlugin('RainLab.Pages')
            && $widget->model instanceof \RainLab\Pages\Classes\Page
            && !$widget->isNested
        ) {
            $this->handleStaticPages($widget);
        }

        if ($widget->model instanceof \Cms\Classes\Page && !$widget->isNested) {
            $this->handleCmsPages($widget);
        }
    }

    protected function handleCmsPages(Form $widget)
    {
        $settings = $widget->model->getAttribute('settings');
        $container = $settings['contentBlockContainer'] ?? $this->settings->getDefaultContainer();
        $containers = $this->containerDefinitions->getDefinitions();
        $groups = $this->contentBlockDefinitions->getFieldGroupsByContainer($container);

        if ($this->user->hasPermission('dieterholvoet.contentblocks.manage_container')) {
            $widget->addTabFields([
                'settings[contentBlockContainer]' => [
                    'tab' => 'Content blocks',
                    'title' => 'Content block container',
                    'type' => 'dropdown',
                    'options' => array_map(
                        function (array $definition) { return $definition['label']; },
                        $containers
                    ),
                ],
            ]);
        }

        if ($this->user->hasPermission('dieterholvoet.contentblocks.manage_content_blocks')) {
            $widget->addTabFields([
                'contentBlockFields' => [
                    'tab' => 'Content blocks',
                    'type' => 'contentblockrepeater',
                    'prompt' => 'Add another content block',
                    'groups' => $groups,
                ],
            ]);
        }
    }

    protected function handleStaticPages(Form $widget)
    {
        $viewBag = $widget->model->getAttribute('viewBag');
        $container = $viewBag['contentBlockContainer'] ?? $this->settings->getDefaultContainer();
        $containers = $this->containerDefinitions->getDefinitions();
        $groups = $this->contentBlockDefinitions->getFieldGroupsByContainer($container);

        if ($this->user->hasPermission('dieterholvoet.contentblocks.manage_container')) {
            $widget->addTabFields([
                'viewBag[contentBlockContainer]' => [
                    'tab' => 'Content blocks',
                    'title' => 'Content block container',
                    'type' => 'dropdown',
                    'options' => array_map(
                        function (array $definition) { return $definition['label']; },
                        $containers
                    ),
                ],
            ]);
        }

        if ($this->user->hasPermission('dieterholvoet.contentblocks.manage_content_blocks')) {
            $widget->addTabFields([
                'contentBlocks' => [
                    'tab' => 'Content blocks',
                    'type' => 'contentblockrepeater',
                    'prompt' => 'Add another content block',
                    'groups' => $groups,
                ],
            ]);
        }
    }
}
