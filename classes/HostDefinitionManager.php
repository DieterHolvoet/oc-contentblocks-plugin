<?php

namespace DieterHolvoet\ContentBlock\Classes;

use Cms\Classes\CmsObject;

class HostDefinitionManager
{
    /** @var string */
    protected $activeTheme;

    public function __construct(
        string $activeTheme
    ) {
        $this->activeTheme = $activeTheme;
    }

    public function getClassName(string $type)
    {
        return $this->getDefinitions()[$type] ?? null;
    }

    public function getType($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        return array_flip($this->getDefinitions())[$className] ?? null;
    }

    /** @return CmsObject[] */
    public function getDefinitions(): array
    {
        return [
            'cms-page' => \Cms\Classes\Page::class,
            'static-page' => \RainLab\Pages\Classes\Page::class,
        ];
    }

    public function getId(CmsObject $instance)
    {
        foreach ($this->getDefinitions() as $class) {
            if ($instance instanceof $class) {
                return sprintf(
                    '%s.%s',
                    $this->activeTheme,
                    $instance->getFileName()
                );
            }
        }

        return null;
    }
}
