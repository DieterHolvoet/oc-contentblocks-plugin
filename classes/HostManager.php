<?php

namespace DieterHolvoet\ContentBlocks\Classes;

use October\Rain\Database\Model;

class HostManager
{
    /** @var HostDefinitionManager */
    protected $hostDefinitions;

    public function __construct(
        HostDefinitionManager $hostDefinitions
    ) {
        $this->hostDefinitions = $hostDefinitions;
    }

    public function getHost(Model $model)
    {
        if (
            empty($model->attributes['contentblock_host_type'])
            || empty($model->attributes['contentblock_host_id'])
        ) {
            return null;
        }

        $className = $this->hostDefinitions->getClassName($model->contentblock_host_type);
        list($theme, $filename) = explode('.', $model->contentblock_host_id, 2);

        return $className::load($theme, $filename);
    }
}
