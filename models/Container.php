<?php namespace Dieterholvoet\Contentblocks\Models;

use Model;

/**
 * container Model
 */
class Container extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'dieterholvoet_contentblocks_containers';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'host_type',
        'host_id',
        'container_id',
    ];

    /**
     * @var bool Indicates if the model should be timestamped.
     */
    public $timestamps = false;

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
}
