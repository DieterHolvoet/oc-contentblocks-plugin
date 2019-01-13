<?php

namespace DieterHolvoet\ContentBlocks\Extenders;

use Cms\Classes\CmsObject;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockManager;

class PageExtender
{
    /** @var ContentBlockManager */
    protected $contentBlocks;

    public function __construct(
        ContentBlockManager $contentBlocks
    ) {
        $this->contentBlocks = $contentBlocks;
    }

    public function extend(CmsObject $model)
    {
        $model->addDynamicMethod('getContentBlocksAttribute', function() use ($model) {
            return $this->contentBlocks->getBlocks($model);
        });
    }
}
