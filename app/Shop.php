<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Shop extends Model
{
    protected $table = "shops";
    protected $fillable =['shop_url', 'my_shopify_domain',  'status', 'shop_details_json', 'created_at'];
    protected $dbTransaction;

   public function changeStatus($status, $shopId)
   {
      return DB::table('shops')->where('id', $shopId)->update(['status' => $status]);
   }

    public function getShopByMyshopifydomain($myshopifydomain)
    {
        $shop= DB::table('shops')->where('my_shopify_domain', $myshopifydomain)->first();
        if($shop){
            return $shop;
        }
        return $this->createShop($myshopifydomain, 'new');
    }

    public function saveDetails($shopName, $url, $details, $shopId)
    {
        $shop = Shop::findOrFail($shopId);
        $shop->shop_details_json = $details;
        $shop->shop_url = $url;
        $shop->name = $shopName;
        $shop->save();
    }


    public function createShop($myshopifydomain, $status)
    {
        $shop = new Shop;
        $shop->my_shopify_domain = $myshopifydomain;
        $shop->status = $status;
        $shop->setting_json = json_encode(config('shopify.SETTINGS'));
        $shop->save();
        return $shop;
    }


    public function insertTokens($accessToken, $shopId)
    {
        DB::table('tokens')->insert(
            [
            'access_token' => $accessToken, 
            'shop_id' => $shopId, 'created_at' => now()
            ]
        );
        
    }

    public function uninstall($myshopifydomain)
    {
        $shop = Shop::where('my_shopify_domain', $myshopifydomain)->first();
        $shop->status = "uninstall";
        $shop->save();
    }

    public function updateTokens( $accessToken, $shopId)
    {
        DB::table('tokens')->where('shop_id', $shopId)->update(
            [
            'access_token' => $accessToken,
        
            'created_at' => now()
            ]
        );
    }

}
