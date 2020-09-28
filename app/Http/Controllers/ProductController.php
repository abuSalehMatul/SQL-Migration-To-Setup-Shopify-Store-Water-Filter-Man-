<?php

namespace App\Http\Controllers;

use App\CustomVariant;
use App\ShopifyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \PHPShopify\AuthHelper;
use \PHPShopify\ShopifySDK;
use App\shopifyProduct;
use PHPShopify\Product;
use Illuminate\Support\Facades\Storage;
use App\Token;
use App\Variant;
use Exception;

use function GuzzleHttp\json_encode;

class ProductController extends Controller
{

    public function insertCategoryOnProduct()
    {
        return "korbo na";
        $byCatPros = DB::table('ecom_category_description')->join('ecom_product_to_category', 'ecom_category_description.category_id', '=', 'ecom_product_to_category.category_id')
            ->select(
                'ecom_product_to_category.product_id',
                'ecom_product_to_category.category_id',
                'ecom_category_description.name',
                'ecom_category_description.description',
                'ecom_category_description.meta_keywords'
            )
            ->get();

        foreach ($byCatPros as $catPro) {
            $product = shopifyProduct::where('product_code', $catPro->product_id)->first();
            //  return $product;
            if ($product) {
                $product->tags = $catPro->name;
                $product->save();
            }


            $cat = new ShopifyCategory();
            $cat->category_id = $catPro->category_id;
            $cat->name = $catPro->name;
            $cat->description = $catPro->description;
            $cat->meta_keyword = $catPro->meta_keywords;
            $cat->save();
        }
        return $byCatPros;
    }
    public function formulateProduct()
    {
        return "hi";
        $products = DB::table('ecom_product')->join('ecom_product_description', 'ecom_product.product_id', '=', 'ecom_product_description.product_id')
            ->select(
                'ecom_product.product_id',
                'ecom_product.quantity',
                'ecom_product.image',
                'ecom_product.manufacturer_id',
                'ecom_product.price',
                'ecom_product.weight',
                'ecom_product.length',
                'ecom_product.width',
                'ecom_product.height',
                'ecom_product.out_of_stock',
                'ecom_product.rrp',
                'ecom_product.month6_price',
                'ecom_product.month12_rrp',
                'ecom_product.month12_price',
                'ecom_product_description.name',
                'ecom_product_description.description',
                'ecom_product_description.short_desc',
                'ecom_product_description.meta_description'
            )
            ->get();

        foreach ($products as $product) {
            $p = new shopifyProduct();
            $p->meta_description = $product->meta_description;
            $p->short_desc = $product->short_desc;
            $p->description = $product->description;
            $p->title = $product->name;
            $p->out_of_stock = $product->out_of_stock;
            $p->height = $product->height;
            $p->width = $product->width;
            $p->rrp = $product->rrp;
            $p->length = $product->length;
            $p->weight = $product->weight;
            $p->price = $product->price;
            $p->manufacturer_id = $product->manufacturer_id;
            $p->images = $product->image;
            $p->month12_price = $product->month12_price;
            $p->month12_rrp = $product->month12_rrp;
            $p->month6_price = $product->month6_price;
            $p->quantity = $product->quantity;
            $p->product_code = $product->product_id;
            $p->save();
        }

        return "hi";
    }

    public function upProduct()
    {
        return 'hi';
        $token = Token::first();
        $config = array(
            'ShopUrl' => 'water-filter-men.myshopify.com',
            'AccessToken' => $token->access_token,
        );

        ShopifySDK::config($config);
        $shopify = new ShopifySDK($config);

        $products = shopifyProduct::where('status', 0)
        ->where('month12_price', 0)
        ->where('month12_rrp', 0)
        ->where('month6_price', 0)
        ->where('rrp', '>', 0)->get();

        $c = shopifyProduct::where('status', 0)
        ->where('month12_price', 0)
        ->where('month12_rrp', 0)
        ->where('month6_price', 0)
        ->where('rrp', '>', 0)->count();
      

        for ($i = 0; $i < $c; $i++) {

            $variantArr = [];
            $images = [];
            $attr = DB::table('ecom_product_attributes')->where('product_id', $products[$i]->product_code)->first();
            if($attr){
            
                continue;
            }
            // return $i;
            $variantArr[] = [
                "price" =>  $products[$i]->price,
                "compare_at_price" => $products[$i]->rrp,
                "weight" => $products[$i]->weight,
                "weight_unit" => "kg",
                "inventory_management" => "shopify",
                "inventory_quantity" => $products[$i]->quantity,
                "sku" => "#".$products[$i]->title."#_" . rand(0, 234),
                "taxable" => true
            ];

            $product = array(
                "title" => $products[$i]->title,
                "body_html" => $products[$i]->description,
                "handle" => $products[$i]->title,
                "published_scope" => "global",
                "published" => true,
                "variants" => $variantArr,
                "product_type" => $products[$i]->tags,
                "tags" => [$products[$i]->tags],

                "metafields" => [
                    [
                        "key" => "meta_field",
                        "value" => $products[$i]->meta_description,
                        "value_type" => "string",
                        "namespace" => "global"
                    ]
                ]
            );
            $res = $shopify->Product->post($product);

            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln(json_encode($res));

            $specificProduct = shopifyProduct::find($products[$i]->id);
            $specificProduct->status = 1;
            $specificProduct->shopify_id = $res['id'];
            $specificProduct->variant_id = $res['variants'][0]['id'];
            $specificProduct->inventory_item_id = $res['variants'][0]['inventory_item_id'];
            $specificProduct->save();

            $fileName = "database_product_" . $products[$i]->id . "_shopify_id_" . $res['id'] . ".json";
            $value = json_encode(($res), JSON_PRETTY_PRINT);
            Storage::disk('public')->put($fileName, $value);

            
            $variantArr = [];
            $images = [];
        }
    }

    public function setImageForCustomVariant()
    {
        $token = Token::first();
        $customVariants = DB::table('custom_variants')->get()->groupBy('product_id');
        return $customVariants;


    }

    public function setImage()
    {
        return "no";
        $token = Token::first();
        $products = shopifyProduct::where('status', 1)->where('image_upload', 0)->where('image_upload', "!=", 10)->get();
        return $products;
        $server = "https://stickybar.aivalabs.com";
        $arr = [];
        $i = 0;
        foreach ($products as $product) {
            $i++;
            if($i == 50){
                return "50 up";
            }
            $src = $server . "/sql_migrate_pictures/products/" . $product->images;
            $header_response = get_headers($src, 1);
            if (strpos($header_response[0], "404") !== false || $product->images == null) {
                $specificProduct = shopifyProduct::find($product->id);
                $specificProduct->image_upload = 10;
                $specificProduct->save();
            } else {
                // $arr[]=[
                //     $product->images => "yes". "<br>".$server."/sql_migrate_pictures/products/".$product->images
                // ];
                $data = [
                    'image' => [
                        'src' => $src
                    ]
                ];
                $data = json_encode($data);
                $url =  "https://water-filter-men.myshopify.com/admin/api/2020-07/products/{$product->shopify_id}/images.json";
                $crl = curl_init();
                curl_setopt($crl, CURLOPT_URL, $url);
                curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token->access_token));
                curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($crl, CURLOPT_VERBOSE, 0);
                curl_setopt($crl, CURLOPT_HEADER, 1);
                curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($crl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($crl);
                curl_close($crl);
               
                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln(json_encode($response));

                $specificProduct = shopifyProduct::find($product->id);
                $specificProduct->image_upload = 1;
                $specificProduct->save();

                $fileName = "database_product_" . $product->id . "_shopify_id_" . $product->shopify_id . ".txt";
                $value = json_encode(($response), JSON_PRETTY_PRINT);
                Storage::disk('image')->put($fileName, $response);

               // return $response;
               
            }
        }
        // return $arr;


    }

    public function deleteProduct()
    {
        // $products = shopifyProduct::get();
        return "hove na";
        DB::table('shopify_products')->truncate();
    }

    public function deleteCustomVariantRec()
    {
        return "hove na";
        // $products = shopifyProduct::get();
        DB::table('custom_variants')->truncate();
    }

    public function setProductStatusZero()
    {
        return "no no no";
        return  shopifyProduct::where('status', 1)->update(['status' => 0, 'image_upload' => 0]);
    }

    public function formulateVariant()
    {
        return "no";
        $productByAttr = DB::table('ecom_product_attributes')->get();
        foreach ($productByAttr as $pro) {
            $attr = DB::table('ecom_attributes')->where('id', $pro->attribute)->first();
            $value = DB::table('ecom_attributes_values')->where('id', $pro->attribute_value)->first();
            if ($attr && $value) {
                $variant = new Variant();
                $variant->product_id = $pro->product_id;
                $variant->attribute_name = $attr->name;
                $variant->attribute_value = $value->attribute_value;
                $variant->image = $value->image;
                $variant->rrp = $pro->rrp_price;
                $variant->price = $pro->price;
                $variant->ecom_product_attributes = $pro->id;
                $variant->save();
            }
        }
        return 'hi';
    }


    public function upMonthBasedVariant()
    {
        return "hi";
        $token = Token::first();
        $config = array(
            'ShopUrl' => 'water-filter-men.myshopify.com',
            'AccessToken' => $token->access_token,
        );

        ShopifySDK::config($config);
        $shopify = new ShopifySDK($config);

        $products = shopifyProduct::where('status', 0)
        ->where('month12_price', '>', 0)
        ->where('month12_rrp', '>', 0)
        ->where('month6_price', '>', 0)
        ->where('rrp', '>', 0)->get();

        $c = shopifyProduct::where('status', 0)
        ->where('month12_price', '>', 0)
        ->where('month12_rrp', '>', 0)
        ->where('month6_price', '>', 0)
        ->where('rrp', '>', 0)->count();
        for ($i = 0; $i < $c; $i++) {
            echo $products[$i]->price;
            $variantArr = [];
            $variantArr[] = [
                "option1" => "6 months lifespan",
                "price" =>  $products[$i]->price,
                "compare_at_price" => $products[$i]->rrp,
                "weight" => $products[$i]->weight,
                "weight_unit" => "kg",
                "inventory_management" => "shopify",
                "inventory_quantity" => $products[$i]->quantity,
                "sku" => "#".$products[$i]->title."#_" . rand(0, 234),
                "taxable" => true
            ];
            $variantArr[] = [
                "option1" => "12 months lifespan",
                "price" =>  $products[$i]->month12_price,
                "compare_at_price" => $products[$i]->month12_rrp,
                "weight" => $products[$i]->weight,
                "weight_unit" => "kg",
                "inventory_management" => "shopify",
                "inventory_quantity" => $products[$i]->quantity,
                "sku" => "#".$products[$i]->title."#_" . rand(0, 234),
                "taxable" => true
            ];

            $product = array(
                "title" => $products[$i]->title,
                "body_html" => $products[$i]->description,
                "handle" => $products[$i]->title,
                "published_scope" => "global",
                "published" => true,
                "variants" => $variantArr,
                "product_type" => $products[$i]->tags,
                "tags" => [$products[$i]->tags],

                "metafields" => [
                    [
                        "key" => "meta_field",
                        "value" => $products[$i]->meta_description,
                        "value_type" => "string",
                        "namespace" => "global"
                    ]
                ]
            );
            $res = $shopify->Product->post($product);

            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln(json_encode($res));

            $specificProduct = shopifyProduct::find($products[$i]->id);
            $specificProduct->status = 1;
            $specificProduct->shopify_id = $res['id'];
            $specificProduct->variant_id = json_encode([$res['variants'][0]['id'],$res['variants'][1]['id'] ]);
            $specificProduct->inventory_item_id = json_encode([$res['variants'][0]['inventory_item_id'], $res['variants'][1]['inventory_item_id']]);
            $specificProduct->save();

            $fileName = "database_product_" . $products[$i]->id . "_shopify_id_" . $res['id'] . ".json";
            $value = json_encode(($res), JSON_PRETTY_PRINT);
            Storage::disk('monthBasedProduct')->put($fileName, $value);

            
            $variantArr = [];
            $images = [];
        }

    }

    public function upVariant(){
        return "hoise";
        $token = Token::first();
        $config = array(
            'ShopUrl' => 'water-filter-men.myshopify.com',
            'AccessToken' => $token->access_token,
        );

        ShopifySDK::config($config);
        $shopify = new ShopifySDK($config);

        $attrByProductId =DB::table('ecom_product_attributes')->get()->groupBy('product_id');
        $matul =0;
        foreach($attrByProductId as $productId => $attrs){
            $matul++;
            if($matul == 180){
                return "150done";
            }
            $ex = "go";
            $product = shopifyProduct::where('product_code', $productId)->first();
            if($product->status == 1){

            }else{
                $variantArr = [];
                $imageArr =[];
                if($product == null){
                    $ex = "no";
                }
                else{
                    foreach($attrs as $attr){
                        $attribute = DB::table('ecom_attributes')->where('id', $attr->attribute)->first();
                        $attributeValue = DB::table('ecom_attributes_values')->where('id', $attr->attribute_value)->first();
                       
                        $rrp ="";
                        if($attr->rrp_price == 0){
                            if($product->rrp > 0){
                                $rrp = $product->rrp;
                            }
                        }else{
                            $rrp = $attr->rrp_price;
                        }
        
                        $price ="";
                        if($attr->price == 0){
                            if($product->price > 0){
                                $price = $product->rrp;
                            }
                        }else{
                            $price = $attr->price;
                        }
                       // return $rrp;
                        if($rrp == "" || $attributeValue == ""){
    
                        }else{
                            $variantArr[] = [
                                "option1" => $attributeValue->attribute_value,
                                "price" =>  $price,
                                "compare_at_price" => $rrp,
                                "weight" => $product->weight,
                                "weight_unit" => "kg",
                                "inventory_management" => "shopify",
                                "inventory_quantity" => $product->quantity,
                                "sku" => "#".$product->title."#_" . rand(0, 234),
                                "taxable" => true
                            ];
    
                            if($attr->images){
                                array_push($imageArr, $attr->images);
                            }else{
                                array_push($imageArr, "no");
                            }
                        }
                       
                    }
                }
             
               if($ex != "go"){
    
               }else{
                $product = array(
                    "title" => $product->title,
                    "body_html" => $product->description,
                    "handle" => $product->title,
                    "published_scope" => "global",
                    "published" => true,
                    "variants" => $variantArr,
                    "product_type" => $product->tags,
                    "tags" => [$product->tags],
    
                    "metafields" => [
                        [
                            "key" => "meta_field",
                            "value" => $product->meta_description,
                            "value_type" => "string",
                            "namespace" => "global"
                        ]
                    ]
                );
                $res = $shopify->Product->post($product);
    
                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln(json_encode($res));
                
                $specificProduct = shopifyProduct::where('product_code',$productId)->first();
                $specificProduct->status = 1;
                $specificProduct->shopify_id = $res['id'];
                $shpVariants = $res['variants'];
                $shpVariantsId = [];
                $inventoryItemId = [];
                foreach($shpVariants as $var){
                    array_push($shpVariantsId, $var['id']);
                    array_push($inventoryItemId, $var['inventory_item_id']);
                    $customVar = new CustomVariant();
                    $customVar->variant_id = $var['id'];
                    $customVar->product_id = $productId;
                    $customVar->image = json_encode($imageArr);
                    $customVar->save();
                }
                $specificProduct->variant_id = json_encode($shpVariantsId);
                $specificProduct->inventory_item_id = json_encode($inventoryItemId);
                $specificProduct->save();
    
                $fileName = "database_product_" . $productId . "_shopify_id_" . $res['id'] . ".json";
                $value = json_encode(($res), JSON_PRETTY_PRINT);
                Storage::disk('customVariant')->put($fileName, $value);
               }
                $ex = "go";
                
               
            }
           
            $variantArr = [];
            $imageArr = [];
        }
        return "done";
        //return $attrByProductId;
    }

    public function delShopifyProduct()
    {
        return "dur";
        $token = Token::first();
        $products = shopifyProduct::get();
        foreach($products as $product){
            $url =  "https://water-filter-men.myshopify.com/admin/api/2020-07/products/{$product->shopify_id}.json";
            $crl = curl_init();
            curl_setopt($crl, CURLOPT_URL, $url);
            curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token->access_token));
            curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($crl, CURLOPT_VERBOSE, 0);
            curl_setopt($crl, CURLOPT_HEADER, 1);
            curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "DELETE");
            // curl_setopt($crl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($crl);
            curl_close($crl);
        }
       
    }
}

