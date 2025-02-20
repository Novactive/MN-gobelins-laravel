<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class Import
{

    private ZetcomService $zetcomService;
    private XmlDataProcessor $dataProcessor;

    /**
     * @param ZetcomService $zetcomService
     * @param XmlDataProcessor $dataProcessor
     */
    public function __construct(ZetcomService $zetcomService, XmlDataProcessor $dataProcessor)
    {
        $this->zetcomService = $zetcomService;
        $this->dataProcessor = $dataProcessor;
    }

    /**
     * @param $item
     * @return void
     */
    public function execute($item)
    {
        try {
            // First save the Product object, without posting to ES.
            // We only post to ES once we have all the relationships saved (see below).
            $inventoryId = $item['inventory_root'] . '-' . ($item['inventory_number'] ?? "000") . '-' .
                ($item['inventory_suffix'] ?? "000") .($item['inventory_suffix2'] ? "-" . $item['inventory_suffix2'] : '');
            $product = null;
            \App\Models\Product::withoutSyncingToSearch(function () use (&$product, $item, $inventoryId) {
                $product = \App\Models\Product::updateOrCreate(
                    //with auth "GMT-11047-001"
                    ['inventory_id' => $inventoryId],
                    [
                        'inventory_id' => $inventoryId,
                        'inventory_root' => (string)$item['inventory_root'],
                        'inventory_number' => (int)$item['inventory_number'] ?? 0,
                        'inventory_suffix' => (int)$item['inventory_suffix'] ?? 0,
                        'inventory_suffix2' => (int)$item['inventory_suffix2'] ?? 0,
                        'legacy_inventory_number' => (string)$item['legacy_inventory_number'],
                        'height_or_thickness' => (float)$item['height_or_thickness'],
                        'length_or_diameter' => (float)$item['length_or_diameter'],
                        'depth_or_width' => (float)$item['depth_or_width'],
                        'conception_year' => (int)$item['conception_year'],
                        'acquisition_origin' => (string)$item['acquisition_origin'],
                        'acquisition_date' => $item['acquisition_date'],
                        'listed_as_historic_monument' => (bool)$item['listed_as_historic_monument'],
                        'listed_on' => $item['listed_on'],
                        'category' => (string)$item['category'],
                        'denomination' => (string)$item['denomination'],
                        'title_or_designation' => (string)$item['title_or_designation'],
                        'description' => (string)$item['description'],
                        'bibliography' => (string)$item['bibliography'],
                        'is_published' => (bool)$item['is_publishable'],
                        'publication_code' => (string)$item['publication_code'],
                        'legacy_updated_on' => $item['legacy_updated_on'],
                    ]
                );
            });

            // Images
            $product->images->map(function ($img) {
                $img->delete();
            });
            $this->importImages($product, $item['images']);

            //Product Type
            if ($item['product_type']) {
                $productType = \App\Models\ProductType::where('name', $item['product_type'])->first();
                if ($productType) {
                    $product->productType()->associate($productType);
                }
            }

            //Drop all authorships
            $product->authorships->map(function ($as) {
                $as->delete();
            });

            // Create authors
            $authors = $this->importAuthors($item['authors']);

            // Create Authorships
            \App\Models\Authorship::unguard();
            $product->authorships()->createMany(
                $authors->map(function ($author) {
                    return [
                        'author_id' => \App\Models\Author::where('legacy_id', $author['legacy_id'])->first()->id,
                    ];
                })->toArray()
            );
            \App\Models\Authorship::reguard();

            // Period
            if ($item['period_legacy_id']) {
                $period = \App\Models\Period::updateOrCreate(
                    ['legacy_id' => $item['period_legacy_id']],
                    [
                        'legacy_id' => $item['period_legacy_id'],
                        'name' => $item['period_name'],
                        'start_year' => $item['period_start_year'] ?? 5,
                        'end_year' => $item['period_end_year'] ?? 2,
                    ]
                );

                if ($period) {
                    $product->period()->associate($period);
                }
            }

            // Entry mode
            if ($item['entry_mode_legacy_id']) {
                $entryMode = \App\Models\EntryMode::updateOrCreate(
                    ['legacy_id' => $item['entry_mode_legacy_id']],
                    [
                        'legacy_id' => $item['entry_mode_legacy_id'],
                        'name' => $item['entry_mode_name'],
                    ]
                );

                if ($entryMode) {
                    $product->entryMode()->associate($entryMode);
                }
            }

            //Style
            if ($item['style_legacy_id']) {
                $style = \App\Models\EntryMode::updateOrCreate(
                    ['legacy_id' => $item['style_legacy_id']],
                    [
                        'legacy_id' => $item['style_legacy_id'],
                        'name' => $item['style_name'],
                    ]
                );

                if ($style) {
                    $product->style()->associate($style);
                }
            }

            //Production Origin
            if ($item['production_origin']) {
                $productionOrigin = \App\Models\ProductionOrigin::firstOrCreate(
                    ['name' => "ARC2"],
                    ['mapping_key' => 'Test']
                );
                if ($productionOrigin) {
                    $product->productionOrigin()->associate($productionOrigin);
                }
            }

            $product->save();

            echo "Le produit $inventoryId a été mis à jour/ajouté" . (!$item['is_publishable'] ? ", mais il est non publiable" : "") . "\n";
            Log::error("Le produit $inventoryId a été mis à jour/ajouté" . (!$item['is_publishable'] ? ", mais il est non publiable" : "") . "\n");

        } catch (\Exception $exception) {
            echo "[IMPORT ERROR (Product(" . $item['id'] . ")]" . $exception->getMessage() . "\n";
            Log::error("[IMPORT ERROR (Product(" . $item['id'] . ")]" . $exception->getMessage());
        }


    }

    /**
     * @param $product
     * @param array $images
     * @return void
     */
    private function importImages(&$product, array $images) {

        $images = collect($images)
            ->map(function ($imgId) {
                $moduleXml = $this->zetcomService->getSingleModule('Multimedia', $imgId);
                return [
                    'imgId' => $imgId,
                    'moduleXml' => $moduleXml,
                ];
            })
            ->filter(function ($img) {
                return $this->dataProcessor->isImagePublishable($img['moduleXml']);
            })
            ->map(function ($img) {
                return [
                    'path' => $this->zetcomService->getImage($img['imgId']),
                    'photographer' => $this->dataProcessor->getPhotographer($img['moduleXml']),
                    'is_published' => true
                ];
            })
            ->map(function ($img) use ($product) {
                $imagePath = public_path("media/xl/{$img['path']}");

                if (file_exists($imagePath) && ($size = @getimagesize($imagePath))) {
                    list($img['width'], $img['height']) = $size;
                } else {
                    Log::error("Fichier image ( " . $img['path'] . ") invalide ou corrompu pour le produit :" . $product['inventory_id']);
                    $img['width'] = null;
                    $img['height'] = null;
                }

                return $img;
            })
            ->filter(fn($img) => !empty($img['path']) && $img['width'] && $img['height'])
            ->values()
            ->toArray();

        if (is_array($images) && sizeof($images) > 0) {
            $product->images()->createMany($images);
        }
    }


    /**
     * @param array $authorIds
     * @return \Illuminate\Support\Collection
     */
    private function importAuthors(array $authorIds) {

        return collect($authorIds)->map(function ($auId) {
                $authorXml = $this->zetcomService->getSingleModule('Person', $auId);
                $author = $this->dataProcessor->processPersonData($authorXml);
                \App\Models\Author::updateOrCreate(
                    ['legacy_id' => (int)$author['legacy_id']],
                    [
                        'legacy_id' => (int) $author['legacy_id'],
                        'name' => (string) $author['name'],
                        'first_name' => (string) $author['first_name'],
                        'last_name' => (string) $author['last_name'],
                        'date_of_birth' => $author['date_of_birth'],
                        'year_of_birth' => isset($author['year_of_birth']) ? (new \DateTime($author['year_of_birth']))->format('Y') : null,
                        'date_of_death' => $author['date_of_death'],
                        'year_of_death' => isset($author['year_of_death']) ? (new \DateTime($author['year_of_death']))->format('Y') : null,
                        'occupation' => (string)$author['occupation'],
                        'birthplace' => (string)$author['birthplace'],
                        'deathplace' => (string)$author['deathplace'],
                        'isni_uri' => (string)$author['isni_uri'],
                        'biography' => (string)$author['biography'],
                    ]
                );

                echo "L'auteur " . $author['id'] . " a été mis à jour/ajouté\n";
                Log::info("L'auteur " . $author['id'] . " a été mis à jour/ajouté");

                return $author;
            });
    }

}