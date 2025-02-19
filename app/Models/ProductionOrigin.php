<?php

namespace App\Models;

use A17\Twill\Models\Model;
use App\Models\Traits\Mappable;

class ProductionOrigin extends Model
{
    use Mappable;

    protected $fillable =  [
        'name',
        'mapping_key'
    ];

    protected $touches = ['products'];
    public $timestamps = false;

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * To store in Elasticsearch.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}