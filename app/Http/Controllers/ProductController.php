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

use function GuzzleHttp\json_decode;
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
       //return DB::table('ecom_product')->count();
        $products = DB::table('ecom_product')->LeftJoin('ecom_product_description', 'ecom_product.product_id', '=', 'ecom_product_description.product_id')
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
       // return 'hi';
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
            ->where('price', ">", 0)
            ->get();

        $c = shopifyProduct::where('status', 0)
            ->where('month12_price', 0)
            ->where('month12_rrp', 0)
            ->where('price', ">", 0)
            ->where('month6_price', 0)
            ->count();


        for ($i = 0; $i < $c; $i++) {

            $variantArr = [];
            $images = [];
            $attr = DB::table('ecom_product_attributes')->where('product_id', $products[$i]->product_code)->first();
            if ($attr) {

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
                "sku" => "#" . $products[$i]->title . "#_" . rand(0, 234),
                "taxable" => true
            ];

            if($products[$i]->rrp == 0 || $products[$i]->rrp == ""){
                unset($variantArr[0]["compare_at_price"]);
            }

            $product = array(
                "title" => $this->removeSpecial($products[$i]->title),
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

           // $specificProduct = shopifyProduct::find($products[$i]->id);
            $products[$i]->status = 1;
            $products[$i]->shopify_id = $res['id'];
            $products[$i]->variant_id = $res['variants'][0]['id'];
            $products[$i]->inventory_item_id = $res['variants'][0]['inventory_item_id'];
            $products[$i]->save();

            $fileName = "database_product_" . $products[$i]->id . "_shopify_id_" . $res['id'] . ".json";
            $value = json_encode(($res), JSON_PRETTY_PRINT);
            Storage::disk('public')->put($fileName, $value);


            $variantArr = [];
            $images = [];
        }
    }

    public function setImageForCustomVariant()
    {
      //  return "no";
        $token = Token::first();
        $customVariants = DB::table('custom_variants')->get()->groupBy('product_id');
        $server = "https://stickybar.aivalabs.com/sql_images";
        foreach ($customVariants as $productId => $variantInfo) {
            for ($i = 0; $i < $variantInfo->count(); $i++) {
                $variantIds = [];
                $data = [];
                $product = shopifyProduct::where('product_code', $productId)->first();
                if($product->image_upload == 1){
                    continue;
                }
               // return $variantInfo;
                array_push($variantIds, $variantInfo[$i]->variant_id);
                $imgArr = json_decode($variantInfo[$i]->image);
                if(sizeof($imgArr) == 0){
                    if ($this->getImageSrc($product->images) != false) {
                        $src = $this->getImageSrc($product->images);
                            $data = [
                                "image" => [
                                    "variant_ids" => $variantIds,
                                    'src' => $src
                                ]
                            ];
                    }
                }
                elseif ($imgArr[$i] != "no") {
                    if ($this->getImageSrc($imgArr[$i]) != false) {
                        $src = $this->getImageSrc($imgArr[$i]);
                        $data = [
                            "image" => [
                                "variant_ids" => $variantIds,
                                'src' => $src
                            ]
                        ];
                    } else {
                        //find the prodcut take the image
                        $product = shopifyProduct::where('product_code', $productId)->first();
                        if ($this->getImageSrc($product->images) != false) {
                            $src = $this->getImageSrc($product->images);
                            $data = [
                                "image" => [
                                    "variant_ids" => $variantIds,
                                    'src' => $src
                                ]
                            ];
                        }
                    }
                } else {
                  
                    if ($this->getImageSrc($product->images) != false) {
                        $src = $this->getImageSrc($product->images);
                            $data = [
                                "image" => [
                                    "variant_ids" => $variantIds,
                                    'src' => $src
                                ]
                            ];
                    }
                }

                if(sizeof($data)> 0){
                    $token = Token::first();
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
                    $header_size = curl_getinfo($crl, CURLINFO_HEADER_SIZE);
                    $header = substr($response, 0, $header_size);
                    $body = substr($response, $header_size);
                    curl_close($crl);
            
                    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                    $output->writeln($body);

                    $product->image_upload = 1;
                    $product->save();
                    
                    $fileName = "database_product_" . $product->id . "_shopify_id_" . $product->shopify_id . ".json";
                    // $value = (($body), JSON_PRETTY_PRINT);
                    Storage::disk('image')->put($fileName, $body);
                }else{
                    $product->image_upload = -10;
                    $product->save();
                }
            }
        }
        return $customVariants;
    }

    public function getImageSrc($image)
    {
        if($image == null){
            return false;
        }
        $server = "https://stickybar.aivalabs.com/sql_images";
        $src = $server . "/products/" . $image;
        $header_response = get_headers($src, 1);
        if (strpos($header_response[0], "200") == true) {
            return $src;
        } 
        else if (strpos(get_headers($server . "/products/thumb/" . $image, 1)[0], "200") == true) {
            return $server . "/products/thumb/" . $image;
        } 
        else if (strpos(get_headers($server . "/products/large/" . $image, 1)[0], "200") == true) {
            return $server . "/products/large/" . $image;
        } 
        else if (strpos(get_headers($server . "/products/smallthumb/" . $image, 1)[0], "200") == true) {
            return $server . "/products/smallthumb/" . $image;
        } 
        else if (strpos(get_headers($server . "/products/medium/" . $image, 1)[0], "200") == true) {
            return $server . "/products/medium/" . $image;
        } 
        else if (strpos(get_headers($server . "/productattribute/" . $image, 1)[0], "200") == true) {
            return $server . "/productattribute/" . $image;
        } 
        else if (strpos(get_headers($server . "/productattribute/thumb/" . $image, 1)[0], "200") == true) {
            return $server . "/productattribute/thumb/" . $image;
        } 
        else if (strpos(get_headers($server . "/productattribute/large/" . $image, 1)[0], "200") == true) {
            return $server . "/productattribute/large/" . $image;
        } 
        else if (strpos(get_headers($server . "/productattribute/smallthumb/" . $image, 1)[0], "200") == true) {
            return $server . "/productattribute/smallthumb/" . $image;
        } 
        else if (strpos(get_headers($server . "/productattribute/medium/" . $image, 1)[0], "200") == true) {
            return $server . "/productattribute/medium/" . $image;
        } else {
            return false;
        }
    }

    public function setImage()
    {
        //  return "no";

        $products = shopifyProduct::where('status', 1)->where('image_upload', 0)->get();
        $server = "https://stickybar.aivalabs.com/sql_images";
        foreach ($products as $product) {

            if ($product->images == null) {
                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln("no image");
              //  $specificProduct = shopifyProduct::find($product->id);
                $product->image_upload = -100;
                $product->save();
            } else {
                $src = $server . "/products/" . $product->images;
                $header_response = get_headers($src, 1);
                if (strpos($header_response[0], "200") == true) {
                    $this->upImageByProduct($product, $src);
                } 
                else if (strpos(get_headers($server . "/products/thumb/" . $product->images, 1)[0], "200") == true) {
                    $this->upImageByProduct($product, $server . "/products/thumb/" . $product->images);
                } 
                else if (strpos(get_headers($server . "/products/large/" . $product->images, 1)[0], "200") == true) {
                    $this->upImageByProduct($product, $server . "/products/large/" . $product->images);
                } 
                else if (strpos(get_headers($server . "/products/smallthumb/" . $product->images, 1)[0], "200") == true) {
                    $this->upImageByProduct($product, $server . "/products/smallthumb/" . $product->images);
                } 
                else if (strpos(get_headers($server . "/products/medium/" . $product->images, 1)[0], "200") == true) {
                    $this->upImageByProduct($product, $server . "/products/medium/" . $product->images);
                } 
                else if (strpos(get_headers($server . "/productattribute/" . $product->images, 1)[0], "200") == true) {
                    return $server . "/productattribute/" . $product->images;
                }
                else {
                    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                    $output->writeln("no image");
                   // $specificProduct = shopifyProduct::find($product->id);
                    $product->image_upload = -10;
                    $product->save();
                }
            }
        }
        // return $arr;
    }


    public function upImageByProduct($product, $src)
    {
        $token = Token::first();
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
        $header_size = curl_getinfo($crl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($crl);

        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln($body);

       // $specificProduct = shopifyProduct::find($product->product_code);
        $product->image_upload = 1;
        $product->save();

        $fileName = "database_product_" . $product->id . "_shopify_id_" . $product->shopify_id . ".json";
        // $value = (($body), JSON_PRETTY_PRINT);
        Storage::disk('image')->put($fileName, $body);
    }

    public function deleteProduct()
    {
        // $products = shopifyProduct::get();
        return "hove na";
        DB::table('shopify_products')->truncate();
    }

    public function deleteCustomVariantRec()
    {
       // return "hove na";
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
        // return "hi";
        $token = Token::first();
        $config = array(
            'ShopUrl' => 'water-filter-men.myshopify.com',
            'AccessToken' => $token->access_token,
        );

        ShopifySDK::config($config);
        $shopify = new ShopifySDK($config);

        $products = shopifyProduct::where('status', 0)
            ->where('month12_price', '>', 0)
            ->where('month6_price', '>', 0)
            ->where('price', '>', 0)
            ->get();
        for ($i = 0; $i < $products->count(); $i++) {
            $options = [];
            $ex = "go";
            $variantArr = [];
            $imageArr = [];
            $attrs = DB::table('ecom_product_attributes')->where('product_id', $products[$i]->product_code)->get();
           
            if (sizeof($attrs) > 0 && DB::table('ecom_attributes')->where('id', $attrs[0]->attribute)->first() != null) {
                // return $attrs;
                $attribute = DB::table('ecom_attributes')->where('id', $attrs[0]->attribute)->first();

                $options = [
                    [
                        "name" => "LifeSpan",
                        "values" => [
                            "6 Months",
                            "12 Months"
                        ]
                    ],
                ];
                $exoptions =  [
                    "name" => $this->removeSpecial($attribute->name),
                    "values" => []
                ];
                array_push($options, $exoptions);
                $j=0;
                foreach ($attrs as $attr) {
                    $attributeValue = DB::table('ecom_attributes_values')->where('id', $attr->attribute_value)->first();
                    if ($attributeValue) {
                        array_push($options[1]['values'], $this->removeSpecial($attributeValue->attribute_value));
                    } else {
                        $ex = "no";
                    }
                    $rrp = "";
                    if ($attr->rrp_price == 0.00) {
                        if ($products[$i]->rrp > 0) {
                            $rrp = $products[$i]->rrp;
                        }
                    } else {
                        $rrp = $attr->rrp_price;
                    }

                    $price = "";
                    if ($attr->price == 0.00) {
                        if ($products[$i]->price > 0) {
                            $price = $products[$i]->price;
                        }
                    } else {
                        $price = $attr->price;
                    }

                    $rrp12 = $products[$i]->month12_rrp;
                   
                    $price12 = $products[$i]->month12_price;
                    
                    // return $rrp;
                    if ($attributeValue == "") {
                    } else {
                        $variantArr[] = [
                            "option1" => "6 Months",
                            "option2" => $attributeValue->attribute_value,
                            "price" =>  $price,
                            "compare_at_price" => $rrp,
                            "weight" => $products[$i]->weight,
                            "weight_unit" => "kg",
                            "inventory_management" => "shopify",
                            "inventory_quantity" => $products[$i]->quantity,
                            "sku" => "#" . $products[$i]->title . "#_" . rand(0, 234),
                            "taxable" => true
                        ];
                        $variantArr[] = [
                            "option1" => "12 Months",
                            "option2" => $attributeValue->attribute_value,
                            "price" =>  $price12,
                            "compare_at_price" => $rrp12,
                            "weight" => $products[$i]->weight,
                            "weight_unit" => "kg",
                            "inventory_management" => "shopify",
                            "inventory_quantity" => $products[$i]->quantity,
                            "sku" => "#" . $products[$i]->title . "#_" . rand(0, 234),
                            "taxable" => true
                        ];
                        if($rrp == "" || $rrp == 0){
                           unset($variantArr[$j]['compare_at_price']);
                        }
                        if($rrp12 == "" || $rrp12 == 0){
                            unset($variantArr[$j+1]['compare_at_price']);
                         }
                        $j = $j + 2;
                        if ($attr->images) {
                            array_push($imageArr, $attr->images);
                        } else {
                            array_push($imageArr, "no");
                        }
                    }
                }

                if ($ex != "go") {
                } else {

                    //  return $options;
                    $product = array(
                        "title" =>  $this->removeSpecial($products[$i]->title),
                        "body_html" => $products[$i]->description,
                        "handle" => $products[$i]->title,
                        "published_scope" => "global",
                        "published" => true,
                        "variants" => $variantArr,
                        "product_type" => $products[$i]->tags,
                        "tags" => [$products[$i]->tags],
                        "options" => $options,
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

                    $specificProduct = shopifyProduct::where('product_code', $products[$i]->product_code)->first();
                    $specificProduct->status = 1;
                    $specificProduct->custom_variant = 1;
                    $specificProduct->shopify_id = $res['id'];
                    $shpVariants = $res['variants'];
                    $shpVariantsId = [];
                    $inventoryItemId = [];
                    foreach ($shpVariants as $var) {
                        array_push($shpVariantsId, $var['id']);
                        array_push($inventoryItemId, $var['inventory_item_id']);
                        $customVar = new CustomVariant();
                        $customVar->variant_id = $var['id'];
                        $customVar->product_id = $products[$i]->product_code;
                        $customVar->image = json_encode($imageArr);
                        $customVar->save();
                    }
                    $specificProduct->variant_id = json_encode($shpVariantsId);
                    $specificProduct->inventory_item_id = json_encode($inventoryItemId);
                    $specificProduct->save();

                    $fileName = "database_product_" . $products[$i]->id . "_shopify_id_" . $res['id'] . ".json";
                    $value = json_encode(($res), JSON_PRETTY_PRINT);
                    Storage::disk('monthCustomVariant')->put($fileName, $value);
                }
            }
        }

        $this->upOnlyMonthBasedVariant($token, $shopify);
    }

    public function upOnlyMonthBasedVariant($token, $shopify)
    {
       //  return "hi";

        $products = shopifyProduct::where('status', 0)
            ->where('month12_price', '>', 0)
            ->where('month6_price', '>', 0)
            ->where('price', '>', 0)
            ->get();
        for ($i = 0; $i < $products->count(); $i++) {
            $variantArr = [];
            $variantArr[] = [
                "option1" => "6 Months",
                "price" =>  $products[$i]->price,
                "compare_at_price" => $products[$i]->rrp,
                "weight" => $products[$i]->weight,
                "weight_unit" => "kg",
                "inventory_management" => "shopify",
                "inventory_quantity" => $products[$i]->quantity,
                "sku" => "#" . $products[$i]->title . "#_" . rand(0, 234),
                "taxable" => true
            ];
            $variantArr[] = [
                "option1" => "12 Months",
                "price" =>  $products[$i]->month12_price,
                "compare_at_price" => $products[$i]->month12_rrp,
                "weight" => $products[$i]->weight,
                "weight_unit" => "kg",
                "inventory_management" => "shopify",
                "inventory_quantity" => $products[$i]->quantity,
                "sku" => "#" . $products[$i]->title . "#_" . rand(0, 234),
                "taxable" => true
            ];

            if($products[$i]->rrp == 0 || $products[$i]->rrp == ""){
                unset($variantArr[0]['compare_at_price']);
            }
            if($products[$i]->month12_rrp == 0 || $products[$i]->month12_rrp == ""){
                unset($variantArr[1]['compare_at_price']);
            }

            $product = array(
                "title" => $this->removeSpecial($products[$i]->title),
                "body_html" => $this->removeSpecial($products[$i]->description),
                "handle" => $products[$i]->title,
                "published_scope" => "global",
                "published" => true,
                "variants" => $variantArr,
                "product_type" => $products[$i]->tags,
                "tags" => [$products[$i]->tags],
                "options" => [
                    [
                        "name" => "Lifespan",
                        "values" => [
                            "6 Months",
                            "12 Months"
                        ]
                    ],

                ],

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

           // $specificProduct = shopifyProduct::find($products[$i]->product_code);
            $products[$i]->status = 1;
            $products[$i]->shopify_id = $res['id'];
            $shpVariants = $res['variants'];
            $shpVariantsId = [];
            $inventoryItemId = [];
            foreach ($shpVariants as $var) {
                array_push($shpVariantsId, $var['id']);
                array_push($inventoryItemId, $var['inventory_item_id']);
                $customVar = new CustomVariant();
                $customVar->variant_id = $var['id'];
                $customVar->product_id = $products[$i]->product_code;
                $customVar->image = $products[$i]->images == null ? "" : json_encode([$products[$i]->images, $products[$i]->images]);
                $customVar->save();
            }
            $products[$i]->variant_id = json_encode($shpVariantsId);
            $products[$i]->inventory_item_id = json_encode($inventoryItemId);
            $products[$i]->save();

            $fileName = "database_product_" . $products[$i]->id . "_shopify_id_" . $res['id'] . ".json";
            $value = json_encode(($res), JSON_PRETTY_PRINT);
            Storage::disk('monthBasedProduct')->put($fileName, $value);


            $variantArr = [];
            $images = [];
        }
        return 'done';
    }

    public function upVariant()
    {
        // return "hoise";
        $token = Token::first();
        $config = array(
            'ShopUrl' => 'water-filter-men.myshopify.com',
            'AccessToken' => $token->access_token,
        );

        ShopifySDK::config($config);
        $shopify = new ShopifySDK($config);

        $attrByProductId = DB::table('ecom_product_attributes')->get()->groupBy('product_id');
        $matul = 0;
        foreach ($attrByProductId as $productId => $attrs) {

            $ex = "go";
            $product = shopifyProduct::where('product_code', $productId)->first();
            if ($product->month6_price > 0 || $product->month12_price > 0  || $product->status == 1) {
            } else {
                $variantArr = [];
                $imageArr = [];
                if ($product == null) {
                    $ex = "no";
                } else {
                    $attribute = DB::table('ecom_attributes')->where('id', $attrs[0]->attribute)->first();
                    $options = [
                        [
                            "name" => $this->removeSpecial($attribute->name),
                            "values" => []
                        ],
                    ];

                    $j = 0;
                    foreach ($attrs as $attr) {

                        $attributeValue = DB::table('ecom_attributes_values')->where('id', $attr->attribute_value)->first();
                        if ($attributeValue) {
                            array_push($options[0]['values'], $this->removeSpecial($attributeValue->attribute_value));
                        } else {
                            $ex = "no";
                        }


                        $rrp = "";
                        if ($attr->rrp_price == 0.00) {
                            if ($product->rrp > 0) {
                                $rrp = $product->rrp;
                            }
                        } else {
                            $rrp = $attr->rrp_price;
                        }

                        $price = "";
                        if ($attr->price == 0.00) {
                            if ($product->price > 0) {
                                $price = $product->price;
                            }
                        } else {
                            $price = $attr->price;
                        }
                        // return $rrp;
                        if ($attributeValue == "" || $price == "") {
                        } else {
                            $variantArr[] = [
                                "option1" => $this->removeSpecial($attributeValue->attribute_value),
                                "price" =>  $price,
                                "compare_at_price" => $rrp,
                                "weight" => $product->weight,
                                "weight_unit" => "kg",
                                "inventory_management" => "shopify",
                                "inventory_quantity" => $product->quantity,
                                "sku" => "#" . $product->title . "#_" . rand(0, 234),
                                "taxable" => true
                            ];

                            if($rrp == 0 || $rrp == ""){
                                unset($variantArr[$j]["compare_at_price"]);
                            }
                            // if($price == 0 || $price == ""){
                            //     unset($variantArr[$j]["price"]);
                            // }
                            $j++;
                            if ($attr->images) {
                                array_push($imageArr, $attr->images);
                            } else {
                                array_push($imageArr, "no");
                            }
                        }
                    }
                }

                if ($ex != "go") {
                } else {

                    //  return $options;
                    $product = array(
                        "title" =>  $this->removeSpecial($product->title),
                        "body_html" => $product->description,
                        "handle" => $product->title,
                        "published_scope" => "global",
                        "published" => true,
                        "variants" => $variantArr,
                        "product_type" => $product->tags,
                        "tags" => [$product->tags],
                        "options" => $options,
                        "metafields" => [
                            [
                                "key" => "meta_field",
                                "value" => $product->meta_description,
                                "value_type" => "string",
                                "namespace" => "global"
                            ]
                        ]
                    );
                    // return ($product);die();
                    $res = $shopify->Product->post($product);

                    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                    $output->writeln(json_encode($res));

                    $specificProduct = shopifyProduct::where('product_code', $productId)->first();
                    $specificProduct->status = 1;
                    $specificProduct->custom_variant = 1;
                    $specificProduct->shopify_id = $res['id'];
                    $shpVariants = $res['variants'];
                    $shpVariantsId = [];
                    $inventoryItemId = [];
                    foreach ($shpVariants as $var) {
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
        foreach ($products as $product) {
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
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln(json_encode($response));
        }
    }


    public function removeSpecial($string)
    {
       return stripslashes($string);
    }

    public function test()
    {
        $data = DB::table('ecom_product_attributes')->join('ecom_product', 'ecom_product_attributes.product_id', '=', 'ecom_product.product_id')
            ->where('month6_price', ">", 0)
            ->select('ecom_product_attributes.product_id')
            ->get()
            // ->groupBy('ecom_product_attributes.product_id');
            ->count();

        // $data=DB::table('ecom_product') ->where('month6_price', ">", 0)
        // ->count();
        return $data;
    }
}
