<?php

namespace DieterHolvoet\ContentBlocks\FormWidgets;

use Backend\FormWidgets\Repeater;

/**
 * A form widget extending the native repeater widget. The big difference is
 * that this widget operates directly on the models instead of on array values.
 */
class ContentBlockRepeater extends Repeater
{
    use ContentBlockRepeaterTrait;
}
