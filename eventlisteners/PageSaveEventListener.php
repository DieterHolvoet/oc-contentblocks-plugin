<?php

namespace DieterHolvoet\ContentBlocks\EventListeners;

use Backend\Classes\AuthManager;
use Backend\Models\User;
use Cms\Controllers\Index;
use DieterHolvoet\ContentBlocks\Classes\ContainerManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockManager;
use DieterHolvoet\ContentBlocks\Classes\HostDefinitionManager;
use DieterHolvoet\ContentBlocks\Models\Settings;
use Illuminate\Database\DatabaseManager;

class PageSaveEventListener
{
    /** @var DatabaseManager */
    protected $database;
    /** @var User */
    protected $user;
    /** @var ContentBlockManager */
    protected $contentBlocks;
    /** @var HostDefinitionManager */
    protected $hostDefinitions;
    /** @var ContainerManager */
    protected $containerManager;
    /** @var Settings */
    protected $settings;

    public function __construct(
        DatabaseManager $database,
        AuthManager $authManager,
        ContentBlockManager $contentBlocks,
        HostDefinitionManager $hostDefinitions,
        ContainerManager $containerManager,
        Settings $settings
    ) {
        $this->database = $database;
        $this->user = $authManager->getUser();
        $this->contentBlocks = $contentBlocks;
        $this->hostDefinitions = $hostDefinitions;
        $this->containerManager = $containerManager;
        $this->settings = $settings;
    }

    public function onCmsPageSave(Index $template, $instance)
    {
        $this->saveContentBlocks($instance);
    }

    public function onStaticPageSave($controller, $instance, $type)
    {
        $this->saveContentBlocks($instance);
    }

    protected function saveContentBlocks($instance)
    {
        if (!$this->settings->getModelsPlugin()) {
            return;
        }

        $hostType = $this->hostDefinitions->getType($instance);
        $hostId = $this->hostDefinitions->getId($instance);

        if (!$hostType) {
            return;
        }

        $this->database->transaction(function () use ($instance, $hostType, $hostId)
        {
            // Update container
            $containerId = post('contentBlockContainer');

            if ($containerId && $this->user->hasPermission('dieterholvoet.contentblocks.manage_container')) {
                $this->containerManager->setContainer($hostType, $hostId, $containerId);
            }


            // Recreate content blocks
            if ($translationData = post('RLTranslate', [])) {
                foreach ($translationData as $langcode => $data) {
                    if (!isset($data['contentBlocks']) || !$data = json_decode($data['contentBlocks'], true)) {
                        continue;
                    }

                    $instance->translateContext($langcode);
                    $this->contentBlocks->deleteBlocks($instance);
                    $this->contentBlocks->addBlocksFromRepeaterData($instance, $data);
                }

                return;
            }

            if ($data = post('contentBlocks', [])) {
                $this->contentBlocks->deleteBlocks($instance);
                $this->contentBlocks->addBlocksFromRepeaterData($instance, $data);
            }
        });
    }
}
