<?php

namespace DieterHolvoet\ContentBlocks\Extenders;

use DieterHolvoet\ContentBlocks\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\HostManager;
use October\Rain\Database\Model;

class ContentBlockExtender
{
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;
    /** @var HostManager */
    protected $hosts;

    public function __construct(
        ContentBlockDefinitionManager $contentBlockDefinitions,
        HostManager $hosts
    ) {
        $this->contentBlockDefinitions = $contentBlockDefinitions;
        $this->hosts = $hosts;
    }

    public function extend(Model $model)
    {
        $model->addDynamicMethod('getShortNameAttribute', function() use ($model) {
            return $this->contentBlockDefinitions->getShortName($model);
        });

        $model->addDynamicMethod('getHostAttribute', function() use ($model) {
            return $this->hosts->getHost($model);
        });
    }
}
