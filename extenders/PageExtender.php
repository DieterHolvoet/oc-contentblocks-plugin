<?php

namespace DieterHolvoet\ContentBlocks\Extenders;

use Cms\Classes\CmsObject;
use DieterHolvoet\ContentBlocks\Classes\ContainerManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockManager;
use DieterHolvoet\ContentBlocks\Classes\HostDefinitionManager;

class PageExtender
{
    /** @var ContainerManager */
    protected $containers;
    /** @var HostDefinitionManager */
    protected $hostDefinitions;
    /** @var ContentBlockManager */
    protected $contentBlocks;

    public function __construct(
        ContainerManager $containers,
        HostDefinitionManager $hostDefinitions,
        ContentBlockManager $contentBlocks
    ) {
        $this->containers = $containers;
        $this->hostDefinitions = $hostDefinitions;
        $this->contentBlocks = $contentBlocks;
    }

    public function extend(CmsObject $model)
    {
        $model->addDynamicMethod('getContentBlocksAttribute', function() use ($model) {
            return $this->contentBlocks->getBlocks($model);
        });

        $model->addDynamicMethod('getContentBlockContainerAttribute', function() use ($model) {
            $hostType = $this->hostDefinitions->getType($model);
            $hostId = $this->hostDefinitions->getId($model);

            if (!$hostType) {
                return null;
            }

            return $this->containers->getContainer($hostType, $hostId);
        });
    }
}
