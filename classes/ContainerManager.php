<?php

namespace DieterHolvoet\ContentBlocks\Classes;

use Dieterholvoet\Contentblocks\Models\Container;
use DieterHolvoet\ContentBlocks\Models\Settings;

class ContainerManager
{
    /** @var Settings */
    protected $settings;

    public function __construct(
        Settings $settings
    ) {
        $this->settings = $settings;
    }

    public function getContainer(string $hostType, string $hostId): ?string
    {
        $containerId = Container::where([
            'host_id' => $hostId,
            'host_type' => $hostType,
        ])->pluck('container_id')->first();

        if (empty($containerId)) {
            return $this->settings->getDefaultContainer();
        }

        return $containerId;
    }

    public function setContainer(string $hostType, string $hostId, string $containerId)
    {
        $where = [
            'host_id' => $hostId,
            'host_type' => $hostType,
        ];

        $values = [
            'container_id' => $containerId,
        ];

        Container::updateOrCreate($where, $values);
    }
}
