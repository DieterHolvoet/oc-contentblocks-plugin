<?php

namespace DieterHolvoet\ContentBlocks\FormWidgets;

use Backend\FormWidgets\Repeater;
use DieterHolvoet\ContentBlocks\Classes\ContentBlockDefinitionManager;

/**
 * A form widget extending the native repeater widget. The big difference is
 * that this widget operates directly on the models instead of on array values.
 */
class ContentBlockRepeater extends Repeater
{
    /** @var ContentBlockDefinitionManager */
    protected $contentBlockDefinitions;

    public function init()
    {
        $this->contentBlockDefinitions = app('dieterholvoet.contentBlocks.contentBlockDefinitionManager');

        parent::init();
    }

    public function guessViewPath($suffix = '', $isPublic = false)
    {
        $class = parent::class;
        return $this->guessViewPathFrom($class, $suffix, $isPublic);
    }

    protected function makeItemFormWidget($index = 0, $groupCode = null)
    {
        $data = $this->getValueFromIndex($index);

        if (empty($groupCode) && is_object($data)) {
            $groupCode = $this->contentBlockDefinitions->getShortName(get_class($data));
        }

        return parent::makeItemFormWidget($index, $groupCode);
    }
}
