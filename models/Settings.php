<?php

namespace DieterHolvoet\ContentBlock\Models;

use Lang;
use Model;
use October\Rain\Database\Traits\Validation;
use System\Classes\PluginBase;
use System\Classes\PluginManager;

/**
 * Settings Model
 */
class Settings extends Model
{
    use Validation;

    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    /**
     * @var string A unique code
     */
    public $settingsCode = 'dieterholvoet_contentblock_settings';

    /**
     * @var string Reference to field configuration
     */
    public $settingsFields = 'fields.yaml';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'dieterholvoet_contentblock_settings';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Validation rules
     */
    protected $rules = [
        'models_plugin' => 'content_block_has_containers',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function getDefaultContainerOptions()
    {
        return array_map(
            function (array $definition) { return $definition['label']; },
            app('dieterholvoet.contentBlock.containerDefinitionManager')->getDefinitions()
        );
    }

    public function getModelsPluginOptions()
    {
        return array_map(
            function (PluginBase $plugin) {
                $details = $plugin->pluginDetails();
                return sprintf(
                    '%s (%s)',
                    Lang::get($details['name']),
                    $details['author']
                );
            },
            PluginManager::instance()->getPlugins()
        );
    }

    public function getModelsPlugin()
    {
        return $this->models_plugin;
    }

    public function getDefaultContainer()
    {
        return $this->default_container;
    }
}
