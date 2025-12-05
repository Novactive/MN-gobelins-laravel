<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\View\Composers\FiltersComposer;

class FiltersController extends Controller
{
    /**
     * Retrieve collection filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $composer = new FiltersComposer();
            return response()->json([
                'success' => true,
                'data' => $composer->filters,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while loading filters',
                'data' => [
                    'productTypes' => [],
                    'styles' => [],
                    'authors' => [],
                    'authors_offsets' => [],
                    'periods' => [],
                    'materials' => [],
                    'productionOrigins' => [],
                    'dimensions' => [
                        'max_height_or_thickness' => 0,
                        'max_depth_or_width' => 0,
                        'max_length_or_diameter' => 0,
                    ]
                ],
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
} 