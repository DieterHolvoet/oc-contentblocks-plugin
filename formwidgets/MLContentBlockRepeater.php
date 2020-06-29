<?php

namespace DieterHolvoet\ContentBlocks\FormWidgets;

use RainLab\Translate\FormWidgets\MLRepeater;

/**
 * A form widget extending the translatable repeater widget. The big difference is
 * that this widget operates directly on the models instead of on array values.
 */
class MLContentBlockRepeater extends MLRepeater
{
    use ContentBlockRepeaterTrait;
}
