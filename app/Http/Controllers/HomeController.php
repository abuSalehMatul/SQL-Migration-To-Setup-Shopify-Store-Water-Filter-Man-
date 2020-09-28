<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Shop;
use App\Service\ApiService;
use \PHPShopify\AuthHelper;
use \PHPShopify\ShopifySDK;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Mail;
use Exception;

class HomeController extends Controller
{

    protected $shopifyApiKey;
    protected $shopifyApiSecret;

    public function __construct(Shop $shopModel, ApiService $apiService)
    {
        $this->shopModel = $shopModel;
        $this->apiService = $apiService;

        $this->shopifyApiKey = config('shopify.SHOPIFY_APP_KEY');
        $this->shopifyApiSecret = config('shopify.SHOPIFY_APP_SECRET');
        $this->UrlPattern = "/[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com[\/]?/";
    }


    public function index(Request $request)
    {
        $hmac =  $request->get('hmac');
        if (isset($hmac)) {

            $myShopifyDomain =  $request->get('shop');
            $timestamp =  $request->get('timestamp');

            $shop = $this->shopModel->getShopByMyshopifydomain($myShopifyDomain);

            $config = array(
                'ShopUrl' => $myShopifyDomain,
                'ApiKey' => $this->shopifyApiKey,
                'SharedSecret' => $this->shopifyApiSecret,
            );
            ShopifySDK::config($config);

            if (AuthHelper::verifyShopifyRequest()) {

                if ($shop->status == 'new' || $shop->status == 'uninstall') {
                    $redirectUri = route('redirect_url');

                    $scopes = "write_customers,write_translations,write_content,"
                        . "write_products,read_locations,read_product_listings,write_inventory,"
                        . "write_themes,read_analytics,write_orders,write_price_rules,write_discounts";

                    $oauthUrl = "https://$myShopifyDomain/admin/oauth/authorize?"
                        . "client_id={$this->shopifyApiKey}"
                        . "&scope={$scopes}"
                        . "&redirect_uri={$redirectUri}"
                        . "&state=state";

                    return Redirect::to($oauthUrl);
                } elseif ($shop->status == 'install') {
                    return view('home')->with('shop', $myShopifyDomain);
                }
            }
        }
    }

    public function callback(Request $request)
    {
        $myShopifyDomain = $request->get('shop');
        $code = $request->get('code');
        $state = $request->get('state');
        $state = $state ?? 'state';

        if (!$this->isValidRequest($_GET)) {
            return "un-authorized request";
        }
        $shop = $this->shopModel->getShopByMyshopifydomain($myShopifyDomain);
       // if (preg_match($this->UrlPattern, $myShopifyDomain)) {
            $accessTokenResult = json_decode($this->apiService->getAccesToken($code, $this->shopifyApiKey, $this->shopifyApiSecret, $myShopifyDomain), true);
            $accessToken = $accessTokenResult['access_token'];
            $receiveScope = $accessTokenResult['scope'];
          //  $scripTagResponse = $this->apiService->createScriptTag($myShopifyDomain, $accessToken);


            //shop details with graphql.............
            $shopDetailsString = $this->apiService->getShopDetails($myShopifyDomain, $accessToken);
            $result = explode(',"extensions"', $shopDetailsString);

            $jsonResultDecoded = json_decode($result[0]);

            $shopName = $jsonResultDecoded->shop->name;
            $url = $jsonResultDecoded->shop->url;
            $myshopifyDomain = $jsonResultDecoded->shop->myshopifyDomain;
            $uninstallationHookAddress = config('shopify.APP_URL') . "/uninstall";

            $this->shopModel->saveDetails($shopName, $url, $result[0], $shop->id);

            $this->apiService->createUnInstallWhook($myshopifyDomain, $accessToken, $uninstallationHookAddress);
            $previousStaus = $shop->status;
            $this->shopModel->changeStatus('install', $shop->id);
            if ($previousStaus == 'new') {
                $insertedToken = $this->shopModel->insertTokens( $accessToken, $shop->id);
            } elseif ($previousStaus == 'uninstall') {
                $insertedToken = $this->shopModel->updateTokens( $accessToken, $shop->id);
            }
            $appPage = "https://{$myshopifyDomain}/admin/apps";
            return Redirect::to($appPage);
       // }
    }

    public function uninstall(Request $request)
    {

        $hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
        $data = file_get_contents('php://input');
        $verified = $this->verify_webhook($data, $hmac_header);
        if ($verified) {
            $shopData = json_decode($data);
            $this->shopModel->uninstall($shopData->domain);
            $shop = new Shop();
            $shop = $shop->getShopByMyshopifydomain($shopData->domain);
            $this->updateActionList($shop->id, 'uninstall the app', 'successful');
        } else {
            error_log('Webhook verified: ' . var_export($verified, true)); //check error.log to see the result

        }
    }

    private function verify_webhook($data, $hmac_header)
    {
        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, $this->shopifyApiSecret, true));
        return hash_equals($hmac_header, $calculated_hmac);
    }

    private function isValidRequest($query)
    {
        $expectedHmac = $query['hmac'] ?? '';
        unset($query['hmac'], $query['signature']);
        ksort($query);
        $pairs = [];
        foreach ($query as $key => $value) {
            $key = strtr($key, ['&' => '%26', '%' => '%25', '=' => '%3D']);
            $value = strtr($value, ['&' => '%26', '%' => '%25']);
            $pairs[] = $key . '=' . $value;
        }
        $key = implode('&', $pairs);
        return (hash_equals($expectedHmac, hash_hmac('sha256', $key, $this->shopifyApiSecret)));
    }

    public function getEmbedded(Request $request)
    {
        $shop = $request->get('shop');
        return view('home', compact('shop'));
    }

    public function test(Request $request)
    { 
        $url = 'https://des-kohan.myshopify.com/admin/api/2020-10/products/count.json'; // . '&fields=';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: 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'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        
        $c = curl_exec($curl);
        curl_close($curl);
        preg_match('/{\"count\":(.*?)}/', $c, $matches);
        $itr = (int) $matches[1] % 250;
        $next_page = '';
        $finalArr = '';
       // return $itr;
        for($i = 0 ; $i<$itr; $i++){
            $items_per_page = 250;
       
            $previous_page= '';
            $last_page = false;
    
            $url = 'https://des-kohan.myshopify.com/admin/api/2020-10/products.json?limit=' . $items_per_page .'&page_info='. $next_page; // . '&fields=';
    
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: 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'));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) >= 2) {
                    $headers[strtolower(trim($header[0]))] = trim($header[1]);
                }
                return $len;
            });
            $response = curl_exec($curl);
            curl_close($curl);
            if (isset($headers['link'])) {
                $links = explode(',', $headers['link']);
                // return ($links);
                foreach ($links as $link) {
                    if (strpos($link, 'rel="next"')) {
                        preg_match('~<(.*?)>~', $link, $next);
                        $url_components = parse_url($next[1]);
                        parse_str($url_components['query'], $params);
                        $next_page =  $params['page_info'];
                        if($next_page == ""){
                        break;
                        }
                        // return Session::get('next_page');
                    } else {
                        //$last_page = true;
                        preg_match('~<(.*?)>~', $link, $next);
                        $url_components = parse_url($next[1]);
                        parse_str($url_components['query'], $params);
                        $previous_page =  $params['page_info'];
                    }
                }
            } else {
                $next_page = ""; // if missing "link" parameter - there's only one page of results = last_page
    
            }
            if($i > 0){
               
                preg_match('/{\"products\":(.*?)}]}/', $response, $matches);
                $result = $matches[1] . '}';
                $result = ltrim($result, '[');
               // return $result;
            }
            else{
               // return $response;
               preg_match('/{\"products\":(.*?)}]}/', $response, $matches);
               $result = $matches[1] . '}';
              // return $result;
            }
            $finalArr = $finalArr . $result;
           /// $result = json_decode($result);
           // return $result;
           // array_merge($finalArr, $result);
        }

        return $finalArr. ']';
        return [
            'result' => $finalArr,
            'next_page' => $next_page,
            'previous_page' => $previous_page
        ];
       
    }

    public function getAccessToken()
    {return "ni";
        $accessTokenResult = json_decode($this->apiService->getAccesToken($code, $this->shopifyApiKey, $this->shopifyApiSecret, "water-filter-men.myshopify.com"), true);
        return $accessToken = $accessTokenResult['access_token'];
        $receiveScope = $accessTokenResult['scope'];
    }
}
