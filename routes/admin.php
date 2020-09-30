<?php

// Register Twill routes here (eg. Route::module('posts'))

// Route::module('pages');
Route::group(['prefix' => 'savoir-faire'], function () {
    Route::module('articles');
    Route::module('sections');
});
Route::group(['prefix' => 'collection'], function () {
    Route::module('authors');
    Route::module('products');
});
