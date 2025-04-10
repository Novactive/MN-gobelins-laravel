<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'path',
        'width',
        'height',
        'is_published',
        'is_poster',
        'is_prime_quality',
        'is_documentation_quality',
        'has_privacy_issue',
        'has_marking',
        'is_reviewed',
        'photographer',
        'license',
        'update_date'
    ];

    protected $touches = ['product'];

    protected $hidden = [
        'pivot', // Hide from toArray()
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Data stored in Elasticsearch
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'path' => $this->path,
            'width' => $this->width,
            'height' => $this->height,
            'photographer' => $this->photographer,
            'is_poster' => $this->is_poster,
            'is_prime_quality' => $this->is_prime_quality,
            'is_documentation_quality' => $this->is_documentation_quality,
            'has_marking' => $this->has_marking,
            'license' => $this->license,
            'update_date' => $this->update_date
        ];
    }
}
