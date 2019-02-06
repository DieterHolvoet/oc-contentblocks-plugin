<?php

namespace DieterHolvoet\ContentBlocks\EventListeners;

use Backend\Classes\AuthManager;
use Backend\Models\User;
use Backend\Widgets\Form;
use DieterHolvoet\ContentBlocks\Classes\ContainerDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\ContainerManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\HostDefinitionManager;
use DieterHolvoet\ContentBlocks\Models\Settings;

class BackendFormEventListener
{
    /** @var User */
    protected $user;
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;
    /** @var ContainerDefinitionManager */
    protected $containerDefinitions;
    /** @var ContainerManager */
    protected $containers;
    /** @var HostDefinitionManager */
    protected $hostDefinitions;
    /** @var Settings */
    protected $settings;

    public function __construct(
        AuthManager $authManager,
        ContentBlockDefinitionManager $contentBlockDefinitions,
        ContainerDefinitionManager $containerDefinitions,
        ContainerManager $containers,
        HostDefinitionManager $hostDefinitions,
        Settings $settings
    ) {
        $this->user = $authManager->getUser();
        $this->contentBlockDefinitions = $contentBlockDefinitions;
        $this->containerDefinitions = $containerDefinitions;
        $this->containers = $containers;
        $this->hostDefinitions = $hostDefinitions;
        $this->settings = $settings;
    }

    public function onExtendFields(Form $widget)
    {
        if (!$this->settings->getModelsPlugin() || $widget->isNested) {
            return;
        }

        $hostType = $this->hostDefinitions->getType($widget->model);
        $hostId = $this->hostDefinitions->getId($widget->model);

        if (!$hostType) {
            return;
        }

        $container = $this->containers->getContainer($hostType, $hostId);
        $containers = $this->containerDefinitions->getDefinitions();
        $groups = $this->contentBlockDefinitions->getFieldGroupsByContainer($container);

        if ($this->user->hasPermission('dieterholvoet.contentblocks.manage_container')) {
            $widget->addTabFields([
                'contentBlockContainer' => [
                    'tab' => 'dieterholvoet.contentblocks::plugin.name',
                    'title' => 'dieterholvoet.contentblocks::field.container',
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
                    'tab' => 'dieterholvoet.contentblocks::plugin.name',
                    'type' => 'contentblockrepeater',
                    'prompt' => 'dieterholvoet.contentblocks::lang.add_content_block',
                    'groups' => $groups,
                ],
            ]);
        }
    }
}
