<?php

namespace DieterHolvoet\ContentBlocks\Validators;

use DieterHolvoet\ContentBlocks\Classes\ContainerDefinitionManager;
use October\Rain\Filesystem\Filesystem;

class ContentBlockPluginValidator
{
    /** @var Filesystem */
    protected $filesystem;
    /** @var ContainerDefinitionManager */
    protected $containerDefinitions;

    public function __construct(
        Filesystem $filesystem,
        ContainerDefinitionManager $containerDefinitions
    ) {
        $this->filesystem = $filesystem;
        $this->containerDefinitions = $containerDefinitions;
    }

    public function hasContainerDefinition($attribute, $value, $parameters)
    {
        return $this->filesystem->exists($this->containerDefinitions->getPath($value));
    }
}
