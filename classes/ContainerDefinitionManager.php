<?php

namespace DieterHolvoet\ContentBlock\Classes;

use DieterHolvoet\ContentBlock\Models\Settings;
use System\Traits\ConfigMaker;

class ContainerDefinitionManager
{
    use ConfigMaker;

    /** @var string */
    protected $modelPlugin;

    public function __construct(
        Settings $settings
    ) {
        $this->modelPlugin = $settings->getModelsPlugin();
    }

    public function getDefinitions()
    {
        if (!$this->modelPlugin) {
            return [];
        }

        $containersPath = $this->getPath($this->modelPlugin);
        return (array) $this->makeConfig($containersPath);
    }

    public function getPath(string $plugin)
    {
        return sprintf(
            '%s/%s/containers.yaml',
            plugins_path(),
            str_replace('.', '/', strtolower($plugin))
        );
    }
}
