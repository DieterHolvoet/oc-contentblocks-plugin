<?php

namespace DieterHolvoet\ContentBlocks\Classes;

class ContentBlockManager
{
    /** @var array */
    protected $modelMap;

    /** @var HostDefinitionManager */
    protected $hostDefinitions;
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;

    public function __construct(
        HostDefinitionManager $hostDefinitions,
        ContentBlockDefinitionManager $contentBlockDefinitions
    ) {
        $this->hostDefinitions = $hostDefinitions;
        $this->contentBlockDefinitions = $contentBlockDefinitions;
    }

    public function getBlocks($host)
    {
        $blocks = collect();

        $type = $this->hostDefinitions->getType($host);
        $id = $this->hostDefinitions->getId($host);

        foreach ($this->contentBlockDefinitions->getModels() as $model) {
            $newBlocks = $model::where([
                'contentblock_host_type' => $type,
                'contentblock_host_id' => $id,
            ])->get();

            $blocks = $blocks->merge($newBlocks);
        }

        return $blocks
            ->sortBy('contentblock_weight')
            ->all();
    }
}
