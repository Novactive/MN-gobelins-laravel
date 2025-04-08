<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Models\Model;
use App\Models\Presenters\Admin\ProductPresenter as AdminProductPresenter;
use App\Models\Presenters\ProductPresenter;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable, HasMedias;

    protected $hidden = ['pivot'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public $presenterAdmin = AdminProductPresenter::class;
    public $presenter = ProductPresenter::class;

    // Twill requirement
    public $slugAttributes = [
        'inventory_id',
    ];

    // Twill images
    public $mediasParams = [
        'cover' => [ // role name
            'default' => [ // crop name
                [
                    'name' => 'default',
                    'ratio' => 16 / 9,
                ],
            ],
        ],
    ];

    // Eloquent relationships

    public function authorships()
    {
        return $this->hasMany(Authorship::class);
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'authorships')->using(Authorship::class);
    }

    public function getAuthorIdsAttribute()
    {
        return $this->authors->pluck('id')->all();
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function entryMode()
    {
        return $this->belongsTo(EntryMode::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function style()
    {
        return $this->belongsTo(Style::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class);
    }

    public function productionOrigin()
    {
        return $this->belongsTo(ProductionOrigin::class);
    }

    public function selections()
    {
        return $this->belongsToMany(Selection::class);
    }

    // Accessors

    public function getMaterialsWithAncestorsAttribute()
    {
        return $this->materials->map(function ($m) {
            return \App\Models\Material::ancestorsAndSelf($m->id)->all();
        })->flatten()->unique('id');
    }

    /**
     * Start dirty hacks to get Twill to properly list
     * browser items in a block, upon page reload.
     * For some reason,
     * A17\Twill\Repositories\Behaviors\HandleBrowsers::getFormFieldsForRelatedBrowser()
     * does not use  ProductController::$browserColumns.
     * Refs: https://github.com/area17/twill/issues/75
     *
     */
    public function getTitleInBrowserAttribute()
    {
        return $this->inventory_id;
    }

    public function defaultCmsImage($params)
    {
        return $this->presentAdmin()->listing_thumbnail();
    }

    public function getAdminEditUrlAttribute()
    {
        return route('admin.collection.products.edit', ['product' => $this]);
    }
    /* End dirty hacks */

    /**
     * Get all the related Materials in a flat array,
     * with the directly related items marked as leaves.
     *
     * @return array
     */
    public function getSearchableMaterialsAttribute()
    {
        return $this->materials->map(function ($mat) {
            return $mat->toSearchableAncestorsAndSelf();
        })->flatten(1)->unique('id')->all();
    }

    public function getSearchableProductTypesAttribute()
    {
        return $this->productType ? $this->productType->toSearchableAncestorsAndSelf() : [];
    }

    public function getSearchableAuthorsAttribute()
    {
        return $this->authors->map(function ($author) {
            return $author->toSearchableArray();
        })->all();
    }

    public function getSearchableImagesAttribute()
    {
        return $this->images()
            ->where('is_published', true)
            ->orderBy('is_poster', 'DESC')
            ->orderBy('is_prime_quality', 'DESC')
            ->get()
            ->map(function ($image) {
                return $image->toSearchableArray();
            })->all();
    }

    /**
     * The "poster" image of a product should be marked
     * as such from our datasource. If not, just take the
     * highest quality one.
     *
     * @return \App\Models\Image|null
     */
    public function getPosterImageAttribute()
    {
        return $this->images()
            ->where('is_published', true)
            ->orderBy('is_poster', 'DESC')
            ->orderBy('is_prime_quality', 'DESC')
            ->first();
    }

    public function getSearchableStyleAttribute()
    {
        return $this->style ? $this->style->toSearchableArray() : [];
    }

    public function getSearchableProductionOriginAttribute()
    {
        return $this->productionOrigin ? $this->productionOrigin->toSearchableArray() : [];
    }

    public function getSearchableEntryModeAttribute()
    {
        return $this->entryMode ? $this->entryMode->toSearchableArray() : [];
    }

    // Fillables

    protected $fillable = [
        'inventory_id',
        'inventory_root',
        'inventory_number',
        'inventory_suffix',
        'legacy_inventory_number',
        'height_or_thickness',
        'length_or_diameter',
        'depth_or_width',
        'conception_year',
        'acquisition_origin',
        'acquisition_date',
        'listed_as_historic_monument',
        'listed_on',
        'category',
        'denomination',
        'title_or_designation',
        'description',
        'bibliography',
        'style_id',
        'is_published',
        'publication_code',
        'entry_mode_id',
        'legacy_updated_on',
        'historic',
        'dim_order'
    ];

    // Eloquent scopes

    public function scopeByInventory($query, $inventory)
    {
        return $query->where('inventory_id', '=', $inventory);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    // Temporary addition for demo purposes.
    // Todo: get score from source data (SCOM), or
    // fine tune the image quality criteria.
    public function getImageQualityScoreAttribute()
    {
        $images = $this->images()->published()->get();
        if ($images && sizeof($images) > 0) {
            if ($images[0]->is_prime_quality) {
                if (strstr($images[0]->path, 'BIDEAU')) {
                    return 5;
                } else {
                    return 3;
                }
            } else {
                return 2;
            }
        } else {
            return 0;
        }
    }

    public function getSeoTitleAttribute()
    {
        return implode(' – ', array_filter([$this->denomination, $this->title_or_designation]));
    }
    public function getSeoDescriptionAttribute()
    {
        return str_replace("\r", "", str_replace("\n", " ", $this->description));
    }
    public function getSeoImagesAttribute()
    {
        $images = $this->images()->published()->get();
        $urls = [];
        if ($images && sizeof($images) > 0) {
            $urls = $images->map(function ($i) {
                return url(\FolkloreImage::url('/media/xl/' . $i->path, 600));
            })->all();
        }
        return $urls;
    }

    /**
     * Get the indexable data array for the model.
     * This data will be stored in Elasticsearch.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title_or_designation' => $this->title_or_designation,
            'denomination' => $this->denomination,
            'description' => $this->description,
            'historic' => $this->historic,
            'about_author' => $this->authors->pluck('biography')->filter()->implode("\n"),
            'bibliography' => $this->bibliography,
            'acquisition_origin' => $this->acquisition_origin,
            'acquisition_date' => $this->acquisition_date,
            'acquisition_mode' => $this->searchableEntryMode,
            'inventory_id' => $this->formatInventoryId($this->inventory_id),
            'inventory_id_as_keyword' => strtoupper($this->inventory_id),
            'product_types' => $this->searchableProductTypes,
            'authors' => $this->searchableAuthors,
            'period_name' => $this->period ? $this->period->name : null,
            'period_start_year' => $this->period ? $this->period->start_year : null,
            'period_end_year' => $this->period ? $this->period->end_year : null,
            'conception_year' => $this->conception_year,
            'conception_year_as_text' => $this->conception_year ? (string) $this->conception_year : null,
            'images' => $this->searchableImages,
            'image_quality_score' => $this->imageQualityScore,
            'style' => $this->searchableStyle,
            'materials' => $this->searchableMaterials,
            'production_origin' => $this->searchableProductionOrigin,
            'length_or_diameter' => $this->length_or_diameter,
            'depth_or_width' => $this->depth_or_width,
            'height_or_thickness' => $this->height_or_thickness,
            'legacy_inventory_number' => $this->legacy_inventory_number,
            'formatted_dimensions' => $this->getFormattedDimensions($this->dim_order)
        ];
    }

    /***
     * Determine if a model should be indexed in Elasticsearch, or not.
     */
    public function shouldBeSearchable()
    {
        return $this->is_published;
    }

    private function formatInventoryId($inventoryId) {
        $inventoryId = preg_replace('/^([A-Z]+)-(\d+)/', '$1 $2', $inventoryId);
        $inventoryId = preg_replace_callback('/-(\d{3})$/', function ($matches) {
            return ($matches[1] === "000") ? '' : "/{$matches[1]}";
        }, $inventoryId);
        $inventoryId = preg_replace('/-(\d{3})/', '/$1', $inventoryId);

        return $inventoryId;
    }

    private function getFormattedDimensions($TypeDimRef) {
        if (!$this->height_or_thickness && !$this->length_or_diameter && !$this->depth_or_width ) {
            return null;
        }

        $dimensionsMap = [
            'Height' => (float) $this->height_or_thickness,
            'Width' => (float) $this->length_or_diameter,
            'Depth' => (float) $this->depth_or_width,
        ];

        $isSmall = count(array_filter($dimensionsMap, fn($d) => $d > 0 && $d < 1)) > 0;

        if ($isSmall) {
            $dimensionsMap = array_map(fn($d) => round($d * 100, 2), $dimensionsMap);
        }

        $unit = $isSmall ? 'cm' : 'm';
        $dimensionsOrder = explode(' x ', $TypeDimRef);
        $orderedDimensions = array_map(fn($dim) => $dimensionsMap[$dim] ?? null, $dimensionsOrder);
        $formattedDimensions = implode(' x ', $orderedDimensions) . ' ' . $unit;

        $labelMap = [
            'Height' => 'h',
            'Width' => 'l',
            'Depth' => 'L',
        ];

        $orderedLabels = array_map(fn($dim) => $labelMap[$dim] ?? null, $dimensionsOrder);
        $formattedLabel = 'Dimensions (' . implode(' × ', array_filter($orderedLabels)) . ')';

        return [
            'dimensions' => $formattedDimensions,
            'label' => $formattedLabel
        ];
    }

}
