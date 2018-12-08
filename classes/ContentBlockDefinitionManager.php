<?php

namespace DieterHolvoet\ContentBlocks\Classes;

use DieterHolvoet\ContentBlocks\Models\Settings;
use Model;
use October\Rain\Filesystem\Filesystem;
use System\Classes\PluginManager;
use System\Traits\ConfigMaker;

class ContentBlockDefinitionManager
{
    use ConfigMaker;

    /** @var Filesystem */
    protected $filesystem;
    /** @var PluginManager */
    protected $pluginManager;
    /** @var ContainerDefinitionManager */
    protected $containerDefinitionManager;

    /** @var array */
    protected $modelMap;
    /** @var Settings */
    protected $settings;

    public function __construct(
        Filesystem $filesystem,
        PluginManager $pluginManager,
        ContainerDefinitionManager $containerDefinitionManager,
        Settings $settings
    ) {
        $this->filesystem = $filesystem;
        $this->pluginManager = $pluginManager;
        $this->containerDefinitionManager = $containerDefinitionManager;
        $this->settings = $settings;
    }

    public function getClassName(string $shortName)
    {
        return $this->getModels()[$shortName] ?? null;
    }

    public function getShortName($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        return array_flip($this->getModels())[$className] ?? null;
    }

    public function getModels()
    {
        if (!$this->settings->getModelsPlugin()) {
            return [];
        }

        if (isset($this->modelMap)) {
            return $this->modelMap;
        }

        $models = [];
        $plugin = $this->pluginManager->findByIdentifier($this->settings->getModelsPlugin());
        $pluginNamespace = explode('\\', get_class($plugin));
        $pluginNamespace = array_slice($pluginNamespace, 0, 2);

        $glob = sprintf('%s/*.php', $this->getModelsPath());

        foreach ($this->filesystem->glob($glob) as $modelPath) {
            $shortName = basename($modelPath, '.php');
            $className = implode('\\', array_merge($pluginNamespace, ['Models', $shortName]));
            $model = app($className);

            if (!$model instanceof Model) {
                continue;
            }

            $models[strtolower($shortName)] = $className;
        }

        return $this->modelMap = $models;
    }

    public function getModelsPath()
    {
        return sprintf(
            '%s/%s/models',
            plugins_path(),
            str_replace('.', '/', $this->settings->getModelsPlugin())
        );
    }

    public function getFieldGroups()
    {
        return array_reduce(
            $this->filesystem->directories($this->getModelsPath()),
            function (array $c, string $modelPath) {
                $parts = explode('/', $modelPath);
                $modelName = end($parts);

                $c[$modelName] = array_merge(
                    (array) $this->makeConfig($modelPath . '/info.yaml'),
                    (array) $this->makeConfig($modelPath . '/fields.yaml')
                );

                return $c;
            },
            []
        );
    }

    public function getFieldGroupsByContainer(string $container)
    {
        $blocks = $this->getFieldGroups();
        $containers = $this->containerDefinitionManager->getDefinitions();

        if (!isset($containers[$container])) {
            return [];
        }

        return array_map(
            function (string $block) use ($blocks) {
                return $blocks[$block];
            },
            array_combine($containers[$container]['blocks'], $containers[$container]['blocks'])
        );
    }
}
