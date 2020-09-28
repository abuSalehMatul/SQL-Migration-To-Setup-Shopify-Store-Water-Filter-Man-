<?php

namespace App\Http\Controllers;

use App\Collection as ShopifyCollection;
use App\ShopifyConnect;
use App\shopifyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Token;
use Illuminate\Support\Facades\Storage;

use function GuzzleHttp\json_decode;

class CollectionController extends Controller
{
    public function setCollection()
    {
        return "jibonero na";
        $getColl = DB::table('ecom_category')->join('ecom_category_description', 'ecom_category.category_id', '=', 'ecom_category_description.category_id')
            ->select(
                'ecom_category.category_id',
                'ecom_category.image',
                'ecom_category.parent_id',
                'ecom_category.date_added',
                'ecom_category_description.name',
                'ecom_category_description.meta_keywords',
                'ecom_category_description.meta_description',
                'ecom_category_description.seo_key',
                'ecom_category_description.description'
            )
            ->get();

        foreach ($getColl as $category) {
            $collection = new ShopifyCollection();
            $collection->category_id = $category->category_id;
            $collection->parent_id = $category->parent_id;
            $collection->date_added = $category->date_added;
            $collection->meta_description = $category->meta_description;
            $collection->meta_key = $category->meta_keywords;
            $collection->seo_key = $category->seo_key;
            $collection->description = $category->description;
            $collection->images = $category->image;
            $collection->title = $category->name;
            $collection->handle = $category->name;
            $collection->save();
        }
        return "collection set";
    }

    public function upCollection()
    {
        return "hoise ";
        $token = Token::first();
        $collections = ShopifyCollection::where('status_upload', 0)->get();
        $server = "https://stickybar.aivalabs.com";
        $i = 0;
        foreach ($collections as $collection) {
            
            $src = $server . '/sql_migrate_pictures/category/' . $collection->images;
            $header_response = get_headers($src, 1);
            $key = $collection->parent_id == 0 ? "parent_collection_key_{$collection->parent_id}": "child_collection";
            $metaValue = $collection->parent_id == 0 ? "parent": "child_of_{$collection->parent_id}_collection";
            if (strpos($header_response[0], "404") !== false || $collection->images == null ) {
                $data = [
                    "custom_collection" => [
                        "title" => $collection->title,
                        "handle" => $collection->seo_key,
                        "body_html" => $collection->description,
                        "published" => true,
                        "published_scope" => "global",
                        "sort_order" => "manual",
                        "metafields" => [
                            [
                                "key" => $key ,
                                "value" => $metaValue,
                                "value_type" => "string",
                                "namespace" => "global"
                            ]
                        ]
                    ]
                ];
            } else {
                $data = [
                    "custom_collection" => [
                        "title" => $collection->title,
                        "handle" => $collection->seo_key,
                        "body_html" => $collection->description,
                        "published" => true,
                        "published_scope" => "global",
                        "sort_order" => "manual",
                        "metafields" => [
                            [
                                "key" => $key ,
                                "value" => $metaValue,
                                "value_type" => "string",
                                "namespace" => "global"
                            ]
                        ],
                        "image" => [
                            "src" => $server . '/sql_migrate_pictures/category/' . $collection->images,
                            "alt" => $collection->title
                        ]
                    ]
                ];
            }
            $data = json_encode($data);
            $url = "https://water-filter-men.myshopify.com/admin/api/2020-10/custom_collections.json";
            $crl = curl_init();
            curl_setopt($crl, CURLOPT_URL, $url);
            curl_setopt($crl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "X-Shopify-Access-Token: " . $token->access_token));
            curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($crl, CURLOPT_VERBOSE, 0);
            curl_setopt($crl, CURLOPT_HEADER, 1);
            curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($crl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($crl);

            $header_size = curl_getinfo($crl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            curl_close($crl);

            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln($body);



            $body = json_decode($body);
            //  print_r($body);
            //  print_r($data);
            $fileName = "database_product_" . $collection->id . "_collection_id_" . $body->custom_collection->id . ".json";
            $value = json_encode(($body), JSON_PRETTY_PRINT);
            Storage::disk('collection')->put($fileName, $value);

            $collection->status_upload = 1;
            $collection->shopify_collection_id = $body->custom_collection->id;
            $collection->save();
        }
    }

    public function collectionStatusZero()
    {
        return "hoise";
        return  ShopifyCollection::where('status_upload', 1)->update(['status_upload' => 0]);
    }

    public function setConnect()
    {
        return "noo";
        $catPros = DB::table('ecom_product_to_category')->get();
        foreach($catPros as $catPro){
            $shopifyCollection = ShopifyCollection::find($catPro->category_id);
            $shopifyProduct = shopifyProduct::where('product_code',$catPro->product_id)->first();
            if($shopifyCollection != null && $shopifyProduct != null){
                $connect = new ShopifyConnect();
                $connect->category_id = $catPro->category_id;
                $connect->product_id = $catPro->product_id;
                $connect->shopify_category_id = $shopifyCollection->shopify_collection_id;
                $connect->shopify_product_id = $shopifyProduct->shopify_id;
                $connect->save();
            }
        }
    }

    public function deleteConnect()
    {
        return "kn";
        DB::table('shopify_connects')->truncate();
    }


}
