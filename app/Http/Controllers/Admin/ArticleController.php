<?php

namespace App\Http\Controllers\Admin;

use A17\Twill\Http\Controllers\Admin\ModuleController;

class ArticleController extends ModuleController
{
    protected $moduleName = 'articles';

    protected $permalinkBase = 'savoir-faire';

    /*
     * Options of the index view
     */
    protected $indexOptions = [
        'create' => true,
        'edit' => true,
        'publish' => true,
        'bulkPublish' => true,
        'feature' => true,
        'bulkFeature' => true,
        'restore' => true,
        'bulkRestore' => true,
        'delete' => true,
        'bulkDelete' => true,
        'reorder' => true,
        'permalink' => true,
        'bulkEdit' => true,
        'editInModal' => false,
        'forceDelete' => true,
        'bulkForceDelete' => true,
    ];

    /*
     * Key of the index column to use as title/name/anythingelse column
     * This will be the first column in the listing and will have a link to the form
     */
    protected $titleColumnKey = 'title';

    /*
     * Available columns of the index view
     */
    protected $indexColumns = [
        'title' => [
            'title' => 'Titre',
            'field' => 'title',
        ],
        'byline' => [
            'title' => 'Par',
            'field' => 'byline',
        ],
        'lead' => [
            'title' => 'Introduction',
            'field' => 'lead',
        ],
        // 'presenterMethodField' => [ // presenter column
        //     'title' => 'Field title',
        //     'field' => 'presenterMethod',
        //     'present' => true,
        // ]
    ];

    protected $filters = [
    ];

    /*
     * Add anything you would like to have available in your module's index view
     */
    protected function indexData($request)
    {
        return [
        ];
    }

    protected function formData($request)
    {
        return [
        ];
    }
}
