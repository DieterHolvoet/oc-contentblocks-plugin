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

    /**
     * The applied modifications will:
     * - resolve the group code from the model name if missing
     * - replace a new array value with a content block model
     * - replace the widget data with the content block model, this is allowed
     *  because array access on models is possible
     * - replace the widget model with the content block model, so methods like
     * filterFields are triggered on the content block model instead of the page model
     */
    protected function makeItemFormWidget($index = 0, $groupCode = null)
    {
        $value = $this->getValueFromIndex($index);

        if (is_object($value)) {
            $block = $value;

            if (empty($groupCode)) {
                $groupCode = $this->contentBlockDefinitions->getShortName(get_class($value));
            }
        }

        if (is_array($value)) {
            $className = $this->contentBlockDefinitions->getClassName($groupCode);
            $block = new $className;
            $block->unguard();
            $block->fill($value);
        }

        $configDefinition = $this->useGroups
            ? $this->getGroupFormFieldConfig($groupCode)
            : $this->form;

        $config = $this->makeConfig($configDefinition);
        $config->model = $config->data = $block;
        $config->alias = $this->alias . 'Form' . $index;
        $config->arrayName = $this->getFieldName().'['.$index.']';
        $config->isNested = true;
        if (self::$onAddItemCalled || $this->minItems > 0) {
            $config->enableDefaults = true;
        }

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->previewMode = $this->previewMode;
        $widget->bindToController();

        $this->indexMeta[$index] = [
            'groupCode' => $groupCode
        ];

        return $this->formWidgets[$index] = $widget;
    }
}
