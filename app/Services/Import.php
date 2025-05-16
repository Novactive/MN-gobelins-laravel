<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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

        $productsToBeIndexed = Redis::smembers('products_to_be_indexed');

        if ($item['id'] === 0) {
            if (empty($productsToBeIndexed)) {
                echo "Pas de produits à indexer...\n";
                return;
            }
            $products = Product::whereIn('inventory_id', $productsToBeIndexed)->get();
            $products->searchable();
            echo "Réindexation des produits en cours...\n";
            Redis::del('products_to_be_indexed');
            return;
        }

        try {
            // First save the Product object, without posting to ES.
            // We only post to ES once we have all the products saved (see above).
            $inventoryId = $item['inventory_root'] . '-' . ((!isset($item['inventory_number']) || $item['inventory_number'] == 0) ? "000" : $item['inventory_number']) . '-' .
                ((!isset($item['inventory_suffix']) || $item['inventory_suffix'] == 0) ? "000" : $item['inventory_suffix']) .($item['inventory_suffix2'] ? "-" . $item['inventory_suffix2'] : '');

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
                        'legacy_inventory_number' => trim($item['legacy_inventory_number']) !== '' ? trim($item['legacy_inventory_number']) : null,
                        'height_or_thickness' => (float)$item['height_or_thickness'],
                        'length_or_diameter' => (float)$item['length_or_diameter'],
                        'depth_or_width' => (float)$item['depth_or_width'],
                        'conception_year' => (string)$item['conception_year'],
                        'acquisition_origin' => (string)$item['acquisition_origin'],
                        'acquisition_date' => $item['acquisition_date'],
                        'listed_as_historic_monument' => (bool)$item['listed_as_historic_monument'],
                        'category' => (string)$item['category'],
                        'denomination' => (string)$item['denomination'],
                        'title_or_designation' => (string)$item['title_or_designation'],
                        'description' => (string)$item['description'],
                        'bibliography' => $this->getBibliography($item['obj_literature_ref'] ?? [], $item['pages_ref_txt'] ?? [], $item['obj_literature_clb'] ?? ''),
                        'is_published' => (bool)$item['is_publishable'],
                        'publication_code' => (string)$item['publication_code'],
                        'dim_order' => (string)$item['dim_order'],
                        'historic' => (string)$item['history'],
                    ]
                );
            });

            //Drop all authorships
            $product->authorships->map(function ($as) {
                $as->delete();
            });

            // Create authors
            $authors = $this->importAuthors($item['authors'], (int)$item['id']);

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

            $isPublicDomain = $this->isPublicDomain($authors);

            // Images
            $product->images->map(function ($img) {
                $img->delete();
            });
            $this->importImages($product, $item['images'], $isPublicDomain);

            //Product Type
            if ($item['product_type']) {
                $productType = \App\Models\ProductType::where('name', $item['product_type'])->first();
                if ($productType) {
                    $product->productType()->associate($productType);
                }
            }

            // Period
            if ($item['period_legacy_id']) {
                $period = \App\Models\Period::updateOrCreate(
                    ['legacy_id' => $item['period_legacy_id']],
                    [
                        'legacy_id' => $item['period_legacy_id'],
                        'name' => $item['period_name'],
                        'start_year' => $item['period_start_year'] ?? null,
                        'end_year' => $item['period_end_year'] ?? null,
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
                $foreignStyles = [
                    'Chinois', 'Oriental', 'Hollandais', 'Anglais',
                    'Autrichien', 'Italien', 'Japonais', 'Espagnol', 'Flamand',
                ];

                $style = \App\Models\Style::where('legacy_id', $item['style_legacy_id'])->first();

                if ($style) {
                    $product->style()->associate($style);
                } elseif (in_array($item['style_name'], ['Directoire', 'Consulat'])) {
                    $product->style()->associate(\App\Models\Style::where('name', 'Directoire - Consulat')->first());
                } elseif ($foreignStyles) {
                    // We consolidate the English, Chinese, Japanese, etc, styles into one "Foreign" one.
                    $product->style()->associate(\App\Models\Style::where('name', 'Étranger')->first());
                }
            } elseif ($item['period_legacy_id'] &&
                $style = \App\Models\Style::mappedFrom('numepo', (string) $item['period_legacy_id'])->first()) {
                // associate the product to the style related to the period
                $product->style()->associate($style);
            } else {
                // Fallback to conception year.
                if ($item['conception_year']) {
                    $style = \App\Models\Style::where([
                        ['start_year', '<=', (int)$item['conception_year']],
                        ['end_year', '>=', (int)$item['conception_year']]
                    ])->first();
                    if ($style) {
                        $product->style()->associate($style);
                    }
                }
            }

            // Materials
            $conservation = !empty($item['conservation']) ? array_map(function ($id) {
                $moduleXml = $this->zetcomService->getSingleModule('Conservation', (int)$id);
                return $this->dataProcessor->processConservationData($moduleXml);
            }, $item['conservation']) : [];

            $materials = $item['mat_tech'];
            $upholstery = array_filter(array_merge($conservation, [$item['obj_garn']], [$item['obj_new_trim_dpl']]), function ($value) {
                return $value !== null && $value !== "";
            });

            $material_ids = collect($materials)
                ->map(function ($legacy_mat) {
                    return \App\Models\Material::mappedFrom('mat', $legacy_mat)->get()->all();
                })
                ->flatten()
                ->pluck('id')
                ->all();

            $upholstery_ids = collect($upholstery)
                ->map(function ($legacy_mat) {
                    return \App\Models\Material::mappedFrom('gar', $legacy_mat)->get()->all();
                })
                ->flatten()
                ->pluck('id')
                ->all();

            $product->materials()->sync(array_merge($material_ids, $upholstery_ids));

            //Production Origin
            if ($item['production_origin']) {
                $productionOrigin = \App\Models\ProductionOrigin::firstOrCreate(
                    ['name' => $item['production_origin']],
                    ['mapping_key' => str_replace(" ", "_", $item['production_origin'])]
                );
                if ($productionOrigin) {
                    $product->productionOrigin()->associate($productionOrigin);
                }
            }

            $product->save();

            if ($item['is_publishable']) {
                Redis::sadd('products_to_be_indexed', $inventoryId);
            }

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
    private function importImages(&$product, array $images, bool $isPublicDomain = false) {

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
            ->map(function ($img) use ($isPublicDomain) {
                $imageDetails = $this->dataProcessor->processMultimediaData($img['moduleXml']);
                return [
                    'path' => $this->zetcomService->getImage($img['imgId']),
                    'is_published' => true,
                    'photographer' => $imageDetails['photographer'] ?? '',
                    'is_poster' => $imageDetails['is_poster'],
                    'is_prime_quality' => $imageDetails['is_prime_quality'],
                    'license' => $isPublicDomain && strpos($imageDetails['photographer'], 'Bideau') !== false ? 'pub' : 'perso',
                    'update_date' => $imageDetails['update_date']
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
    private function importAuthors(array $authorIds, int $productId) {
        return collect($authorIds)->map(function ($auId) use ($productId) {
                $authorXml = $this->zetcomService->getSingleModule('Person', $auId);
                $author = $this->dataProcessor->processPersonData($authorXml, $productId);
                $legacyId = $author['legacy_id'] ? (int)$author['legacy_id'] : (int)$author['id'];
                $author = \App\Models\Author::updateOrCreate(
                    ['legacy_id' => $legacyId],
                    [
                        'legacy_id' => $legacyId,
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

    /**
     * @param array $objLiteratureRef
     * @param array $pagesRefTxt
     * @param string $objLiteratureClb
     * @return string
     * @throws \Exception
     */
    private function getBibliography(array $objLiteratureRef, array $pagesRefTxt, string $objLiteratureClb) {

        $bibliography = "";

        foreach ($objLiteratureRef as $key => $objLiteratureId) {
            $literatureXml = $this->zetcomService->getSingleModule('Literature', (int)$objLiteratureId);
            $litCitationClb = $this->dataProcessor->getLiteratureItem($literatureXml);

            if ($litCitationClb === ""){
                continue;
            }

            $bibliography .= $litCitationClb . (isset($pagesRefTxt[$key]) ? ", p.$pagesRefTxt[$key] " : "") . "\n";
        }

        $bibliography .= $objLiteratureClb;

        return $bibliography;

    }

    public function isPublicDomain($authors)
    {
        foreach ($authors as $author) {
            if (strpos($author['right_type'], 'domaine public') === false) {
                return false;
            }
        }
        return true;
    }

}