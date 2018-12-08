<?php

namespace DieterHolvoet\ContentBlock\Extenders;

use Cms\Classes\CmsObject;
use DieterHolvoet\ContentBlock\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlock\Classes\ContentBlockManager;
use October\Rain\Database\Model;

class PageExtender
{
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;
    /** @var ContentBlockManager */
    protected $contentBlocks;

    public function __construct(
        ContentBlockDefinitionManager $contentBlockDefinitions,
        ContentBlockManager $contentBlocks
    ) {
        $this->contentBlockDefinitions = $contentBlockDefinitions;
        $this->contentBlocks = $contentBlocks;
    }

    public function extend(CmsObject $model)
    {
        $model->addDynamicMethod('getContentBlocksAttribute', function() use ($model) {
            return $this->contentBlocks->getBlocks($model);
        });

        $model->addDynamicMethod('getContentBlockFieldsAttribute', function() use ($model) {
            return array_map(
                function (Model $model) {
                    return array_merge(
                        ['_group' => $this->contentBlockDefinitions->getShortName($model)],
                        $model->toArray()
                    );
                },
                $this->contentBlocks->getBlocks($model)
            );
        });
    }
}
