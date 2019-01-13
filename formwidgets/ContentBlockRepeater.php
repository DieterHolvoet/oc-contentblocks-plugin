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
        // Below line changed
        $class = parent::class;
        return $this->guessViewPathFrom($class, $suffix, $isPublic);
    }

    protected function makeItemFormWidget($index = 0, $groupCode = null)
    {
        $className = $this->contentBlockDefinitions->getClassName($groupCode);
        $configDefinition = $this->useGroups
            ? $this->getGroupFormFieldConfig($groupCode)
            : $this->form;

        $config = $this->makeConfig($configDefinition);
        $data = $this->getLoadValueFromIndex($index);

        // Below line changed
        $config->model = $config->data = empty($data) ? new $className : $data;
        $config->alias = $this->alias . 'Form'.$index;
        $config->arrayName = $this->getFieldName().'['.$index.']';
        $config->isNested = true;

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();

        $this->indexMeta[$index] = [
            'groupCode' => $groupCode
        ];

        return $this->formWidgets[$index] = $widget;
    }

    protected function processExistingItems()
    {
        $loadedIndexes = $loadedGroups = [];
        $loadValue = $this->getLoadValue();

        // Ensure that the minimum number of items are preinitialized
        // ONLY DONE WHEN NOT IN GROUP MODE
        if (!$this->useGroups && $this->minItems > 0) {
            if (!is_array($loadValue)) {
                $loadValue = [];
                for ($i = 0; $i < $this->minItems; $i++) {
                    $loadValue[$i] = [];
                }
            } elseif (count($loadValue) < $this->minItems) {
                for ($i = 0; $i < ($this->minItems - count($loadValue)); $i++) {
                    $loadValue[] = [];
                }
            }
        }

        if (is_array($loadValue)) {
            foreach ($loadValue as $index => $loadedValue) {
                $loadedIndexes[] = $index;
                // Below line changed
                $loadedGroups[] = $this->contentBlockDefinitions->getShortName($loadedValue);
            }
        }

        $itemIndexes = post($this->indexInputName, $loadedIndexes);
        $itemGroups = post($this->groupInputName, $loadedGroups);

        if (!count($itemIndexes)) {
            return;
        }

        $items = array_combine(
            (array) $itemIndexes,
            (array) ($this->useGroups ? $itemGroups : $itemIndexes)
        );

        foreach ($items as $itemIndex => $groupCode) {
            $this->makeItemFormWidget($itemIndex, $groupCode);
            $this->indexCount = max((int) $itemIndex, $this->indexCount);
        }
    }
}
