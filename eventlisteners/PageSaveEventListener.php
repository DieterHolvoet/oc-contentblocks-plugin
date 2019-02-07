<?php

namespace DieterHolvoet\ContentBlocks\EventListeners;

use Backend\Classes\AuthManager;
use Backend\Models\User;
use Backend\Traits\FormModelSaver;
use Cms\Controllers\Index;
use DieterHolvoet\ContentBlocks\Classes\ContainerManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\HostDefinitionManager;
use Dieterholvoet\Contentblocks\Models\Container;
use DieterHolvoet\ContentBlocks\Models\Settings;
use Illuminate\Database\DatabaseManager;
use October\Rain\Database\Model;

class PageSaveEventListener
{
    use FormModelSaver;

    /** @var DatabaseManager */
    protected $database;
    /** @var User */
    protected $user;
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;
    /** @var HostDefinitionManager */
    protected $hostDefinitions;
    /** @var ContainerManager */
    protected $containerManager;
    /** @var Settings */
    protected $settings;

    public function __construct(
        DatabaseManager $database,
        AuthManager $authManager,
        ContentBlockDefinitionManager $contentBlockDefinitions,
        HostDefinitionManager $hostDefinitions,
        ContainerManager $containerManager,
        Settings $settings
    ) {
        $this->database = $database;
        $this->user = $authManager->getUser();
        $this->contentBlockDefinitions = $contentBlockDefinitions;
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

        $this->database->transaction(function () use ($hostType, $hostId) {

            // Update container
            $containerId = post('contentBlockContainer');

            if ($containerId && $this->user->hasPermission('dieterholvoet.contentblocks.manage_container')) {
                $this->containerManager->setContainer($hostType, $hostId, $containerId);
            }

            // Delete existing content blocks
            foreach ($this->contentBlockDefinitions->getModels() as $className) {
                $className::where([
                    'contentblock_host_id' => $hostId,
                    'contentblock_host_type' => $hostType,
                ])->delete();
            }

            // Recreate content blocks
            foreach (array_values(post('contentBlocks', [])) as $i => $data) {
                $className = $this->contentBlockDefinitions->getClassName($data['_group']);
                $contentBlock = new $className;

                $data['contentblock_host_id'] = $hostId;
                $data['contentblock_host_type'] = $hostType;
                $data['contentblock_weight'] = $i;
                unset($data['_group']);

                /** @var Model $modelToSave */
                foreach ($this->prepareModelsToSave($contentBlock, $data) as $modelToSave) {
                    $modelToSave->save();
                }

                /** @var Model $contentBlock */
                if (!$contentBlock->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel')) {
                    continue;
                }

                foreach (array_keys(post('RLTranslate', [])) as $langcode) {
                    $translationData = post("RLTranslate.{$langcode}.contentBlocks", []);
                    $translationData = array_values($translationData)[$i] ?? null;

                    if (!$translationData) {
                        continue;
                    }

                    $contentBlock->translateContext($langcode);

                    /** @var Model $modelToSave */
                    foreach ($this->prepareModelsToSave($contentBlock, $translationData) as $modelToSave) {
                        $modelToSave->save();
                    }
                }
            }
        });
    }
}
