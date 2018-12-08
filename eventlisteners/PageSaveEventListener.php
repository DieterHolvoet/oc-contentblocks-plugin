<?php

namespace DieterHolvoet\ContentBlocks\EventListeners;

use Backend\Traits\FormModelSaver;
use Cms\Controllers\Index;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\HostDefinitionManager;
use Illuminate\Database\DatabaseManager;
use October\Rain\Database\Model;

class PageSaveEventListener
{
    use FormModelSaver;

    /** @var DatabaseManager */
    protected $database;
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;
    /** @var HostDefinitionManager */
    protected $hostDefinitions;

    public function __construct(
        DatabaseManager $database,
        ContentBlockDefinitionManager $contentBlockDefinitions,
        HostDefinitionManager $hostDefinitions
    ) {
        $this->database = $database;
        $this->contentBlockDefinitions = $contentBlockDefinitions;
        $this->hostDefinitions = $hostDefinitions;
    }

    public function onCmsPageSave(Index $template, $instance)
    {
        if (!$instance instanceof \Cms\Classes\Page) {
            return;
        }

        $this->saveContentBlocks('page', $instance);
    }

    public function onStaticPageSave($controller, $instance, $type)
    {
        if (!$instance instanceof \RainLab\Pages\Classes\Page) {
            return;
        }

        $this->saveContentBlocks('static-page', $instance);
    }

    protected function saveContentBlocks(string $hostType, $instance)
    {
        $this->database->transaction(function () use ($hostType, $instance) {
            $hostId = $this->hostDefinitions->getId($instance);

            // Delete existing content blocks
            foreach ($this->contentBlockDefinitions->getModels() as $className) {
                $className::where([
                    'contentblock_host_id' => $hostId,
                    'contentblock_host_type' => $hostType,
                ])->delete();
            }

            // Recreate content blocks
            foreach (array_values(post('contentBlockFields', [])) as $i => $data) {
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
            }
        });
    }
}
