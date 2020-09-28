<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/home', 'HomeController@index');

// Auth::routes();


Route::get('shopify/auth/callback', 'HomeController@callback')->name('redirect_url');
Route::any('uninstall', 'HomeController@uninstall')->name('unistallation_hook');

Route::get('/set-products', 'ProductController@formulateProduct');
Route::get('up-product', 'ProductController@upProduct');
Route::get('del-all-shopify-product', "ProductController@delShopifyProduct");
Route::get('up-month-product', 'ProductController@upMonthBasedVariant');
Route::get('up-variant', "ProductController@upVariant");
Route::get('del-custom-var-record', 'ProductController@deleteCustomVariantRec');
Route::get('by-category', 'ProductController@insertCategoryOnProduct');

Route::get('set-collection', 'CollectionController@setCollection');
Route::get('up-collection', 'CollectionController@upCollection');
Route::get('collection-status-to-zero', 'CollectionController@collectionStatusZero');

Route::get('set-connect', 'CollectionController@setConnect');
Route::get('del-connect', 'CollectionController@deleteConnect');

Route::get('access-token', 'HomeController@getAccessToken');

Route::get('del-all-product', 'ProductController@deleteProduct');
Route::get('set-variant', 'ProductController@formulateVariant');

Route::get('status-to-zero', 'ProductController@setProductStatusZero');

Route::get('up-image', 'ProductController@setImage');
Route::get('up-variant-image', 'ProductController@setImageForCustomVariant');

