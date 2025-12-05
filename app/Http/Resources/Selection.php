<?php

namespace App\Http\Resources;

use Elasticsearch\ClientBuilder;
use Illuminate\Http\Resources\Json\JsonResource;

class Selection extends JsonResource
{
    private $client;

    public function __construct($r)
    {
        parent::__construct($r);
        $this->client = ClientBuilder::create()->setHosts(config("es.host"))->build();
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $canUpdateThis = $request->user('api') && $request->user('api')->can('update', $this->resource);

        $product_ids = $this->resource->products()->where('is_published', true)->select('id')->get()->pluck('id')->toArray();

        if (sizeof($product_ids)) {
            // Use search query instead of mget to get products by database IDs
            $es = $this->client->search([
                "index" => "gobelins_search_1",
                "type" => "products",
                "body" => [
                    "query" => [
                        "terms" => [
                            "id" => $product_ids
                        ]
                    ],
                    "size" => count($product_ids)
                ]
            ]);
            
            $products = collect($es['hits']['hits'])->map(function ($hit) {
                $hit['_source']['_id'] = $hit['_id'];
                return $hit['_source'];
            })->all();
        } else {
            $products = [];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'public' => $this->public,
            'users' => $this->users->map(function ($u) use ($request, $canUpdateThis) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    $this->mergeWhen($canUpdateThis, [
                        'email' => $u->email,
                    ]),
                ];
            })->filter()->all(),
            $this->mergeWhen($canUpdateThis, [
                'invitations' => $this->invitations,
            ]),
            // 'products' => $this->products->map(function ($p) {
            //     // return $p->toSearchableArray();
            //     return [
            //         'title_or_designation' => $p->title_or_designation,
            //         'denomination' => $p->denomination,
            //         'inventory_id' => $p->inventory_id,
            //         'product_types' => $p->searchableProductTypes,
            //         'authors' => $p->searchableAuthors,
            //         'images' => $p->searchableImages,
            //         'partiallyLoaded' => true,
            //     ];
            // })->all()
            'products' => $products,
        ];
    }
}
