<?php

namespace DieterHolvoet\ContentBlocks\Extenders;

use Cms\Classes\CmsObject;
use DieterHolvoet\ContentBlocks\Classes\ContainerManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockDefinitionManager;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockManager;
use DieterHolvoet\ContentBlocks\Classes\HostDefinitionManager;
use October\Rain\Database\Model;

class PageExtender
{
    /** @var ContainerManager */
    protected $containers;
    /** @var HostDefinitionManager */
    protected $hostDefinitions;
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;
    /** @var ContentBlockManager */
    protected $contentBlocks;

    public function __construct(
        ContainerManager $containers,
        HostDefinitionManager $hostDefinitions,
        ContentBlockDefinitionManager $contentBlockDefinitions,
        ContentBlockManager $contentBlocks
    ) {
        $this->containers = $containers;
        $this->hostDefinitions = $hostDefinitions;
        $this->contentBlockDefinitions = $contentBlockDefinitions;
        $this->contentBlocks = $contentBlocks;
    }

    public function extend(CmsObject $model)
    {
        $model->addDynamicMethod('getContentBlocksAttribute', function() use ($model) {
            if ($locale = post('_repeater_locale')) {
                $model->translateContext($locale);
            }

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

        $model->bindEvent('model.afterFetch', function () use ($model) {
            // Add content blocks as actual attributes to fix some translation issues
            $existingLocale = $model->translateContext();

            foreach (['nl', 'fr', 'en'] as $locale) {
                $model->translateContext($locale);
                $model->setAttributeTranslated('contentBlocks', array_map(
                    function (Model $block) {
                        $data = $block->attributesToArray();
                        $data['_group'] = $this->contentBlockDefinitions->getShortName(get_class($block));

                        return $data;
                    },
                    $this->contentBlocks->getBlocks($model)
                ));
            }

            $model->translateContext($existingLocale);
        });

        $model->bindEvent('model.beforeSave', static function () use ($model) {
            // Content blocks are saved separately, remove them from the page model
            if (isset($model->attributes['contentBlocks'])) {
                unset($model->attributes['contentBlocks']);
            }
        });
    }
}
