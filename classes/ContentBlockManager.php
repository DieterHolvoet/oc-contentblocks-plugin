<?php

namespace DieterHolvoet\ContentBlocks\Classes;

use Backend\Traits\FormModelSaver;
use October\Rain\Database\Model;
use October\Rain\Extension\Extendable;

class ContentBlockManager
{
    use FormModelSaver;

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

        $conditions = [
            'contentblock_host_type' => $this->hostDefinitions->getType($host),
            'contentblock_host_id' => $this->hostDefinitions->getId($host),
            'contentblock_host_locale' => $this->getLocale($host),
        ];

        foreach ($this->contentBlockDefinitions->getModels() as $model) {
            $newBlocks = $model::where($conditions)->get();
            $blocks = $blocks->merge($newBlocks);
        }

        return $blocks
            ->sortBy('contentblock_weight')
            ->all();
    }

    public function addBlocksFromRepeaterData($host, array $data): void
    {
        $hostType = $this->hostDefinitions->getType($host);
        $hostId = $this->hostDefinitions->getId($host);
        $hostLocale = $this->getLocale($host);

        foreach ($data as $key => $values) {
            $className = $this->contentBlockDefinitions->getClassName($values['_group']);
            $contentBlock = new $className;
            $index = array_search($key, array_keys($data), true);

            $values['contentblock_host_id'] = $hostId;
            $values['contentblock_host_type'] = $hostType;
            $values['contentblock_host_locale'] = $hostLocale;
            $values['contentblock_weight'] = $index;

            unset($values['_group']);

            /** @var Model $modelToSave */
            foreach ($this->prepareModelsToSave($contentBlock, $values) as $modelToSave) {
                $modelToSave->save();
            }
        }
    }

    public function deleteBlocks($host): void
    {
        $conditions = [
            'contentblock_host_type' => $this->hostDefinitions->getType($host),
            'contentblock_host_id' => $this->hostDefinitions->getId($host),
            'contentblock_host_locale' => $this->getLocale($host),
        ];

        foreach ($this->contentBlockDefinitions->getModels() as $model) {
            $model::where($conditions)->delete();
        }
    }

    protected function getLocale($host): string
    {
        return $this->isTranslatable($host)
            ? $host->translateContext()
            : config('app.locale');
    }

    protected function isTranslatable($host): bool
    {
        return $host instanceof Extendable
            && (
                $host->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel')
                || $host->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatablePage')
                || $host->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableCmsObject')
            );
    }
}
