<?php

namespace App\Service;

ini_set("display_errors", 1);

use Illuminate\Support\Facades\Session;

class ApiService
{
    function __construct()
    {
    }

    public function getProduct($shop, $token)
    {

        $url = "https://{$shop}/admin/api/2020-10/products/count.json"; // . '&fields=';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token:'. $token));
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
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token:'. $token));
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
            if($i == 0){
                $finalArr = $finalArr . $result;
            }
            else{
                $finalArr = $finalArr. ',' . $result;
            }
            
           /// $result = json_decode($result);
           // return $result;
           // array_merge($finalArr, $result);
        }

        return $finalArr. ']';
    }

    function getShopDetails($shop, $access_token)
    {
        $url = "https://{$shop}/admin/api/2019-10/graphql.json";
        $query = json_encode(
            [
                "query" =>
                "{
                        shop {
                          name
                          url
                          billingAddress {
                            id
                            country
                            countryCodeV2
                            zip
                            city
                            address1
                            province
                            phone
                          } 
                          myshopifyDomain
                          timezoneAbbreviation  
                          currencyCode
                          contactEmail
                          email
                          description
                          primaryDomain {
                            url
                          }
                          plan {
                            displayName
                          }
                        }
                      }"
            ]
        );
        $token = $access_token;

        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_VERBOSE, 0);
        curl_setopt($crl, CURLOPT_HEADER, 1);
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($crl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($crl);
        curl_close($crl);

        $result = $this->checkIfCurlWorkingAndGetData($response, $token, $query, $url);
        return $result;
    }

    function checkIfCurlWorkingAndGetData($response, $token, $query, $url)
    {
        if (
            preg_match("/HTTP\/2 200/", $response) ||
            preg_match("/HTTP\/2 201/", $response)
        ) {
            $res = preg_split("/\"data\"\:/", $response);
        } else {
            $requestBody = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token),
                    'content' => $query
                )
            );

            $context = stream_context_create($requestBody);

            $res = file_get_contents($url, false, $context);
            $res = preg_split("/\"data\"\:/", $res);
        }
        return $res[1];
    }

    public function metaDelete($productId, $shop, $token, $metaValue)
    {
        $url = "https://{$shop}/admin/products/{$productId}/metafields.json";
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_VERBOSE, 0);
        curl_setopt($crl, CURLOPT_HEADER, 1);
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($crl);
        curl_close($crl);

        preg_match('/{\"metafields\":(.*?)]}/', $response, $matches);
        $result = $matches[1] . ']';
        $result = json_decode($result);
        
        $id="";
        foreach ($result as $res) {
            if ($res->key == 'fit-info') {
                $id = $res->id;
            }
        }

        if($id !=""){
            $url = "https://{$shop}/admin/api/2020-07/products/{$productId}/metafields/{$id}.json";
            $crl = curl_init();
            curl_setopt($crl, CURLOPT_URL, $url);
            curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token));
            curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($crl, CURLOPT_VERBOSE, 0);
            curl_setopt($crl, CURLOPT_HEADER, 1);
            curl_setopt($crl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($crl);
            curl_close($crl);
        }
        
        return $response;
    }

    function getAccesToken($code, $api_key, $secret, $shop)
    {
        $query = array(
            "client_id" => $api_key,
            "client_secret" => $secret,
            "code" => $code
        );
        // Generate access token URL
        $url = "https://" . $shop . "/admin/oauth/access_token";

        // Configure curl client and execute request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($query));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    function createScriptTag($shopUrl, $accessToken)
    {
        $scriptTagSrc = config('shopify.SCRIPT_TAG_SRC');
        $scriptTagSrc =  "\"$scriptTagSrc\"";
        $query = '{"script_tag": {"event": "onload","src":  ' . $scriptTagSrc . '}}';
        $url =  'https://' . $shopUrl . '/admin/api/2019-10/script_tags.json';
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $accessToken));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_VERBOSE, 0);
        curl_setopt($crl, CURLOPT_HEADER, 1);
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($crl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($crl);
        curl_close($crl);

        if (
            preg_match("/HTTP\/1.1 200/", $response) ||
            preg_match("/HTTP\/2 200/", $response) ||
            preg_match("/HTTP\/1.1 201/", $response) ||
            preg_match("/HTTP\/2 201/", $response)
        ) {
            if (preg_match("/{\"script_tag\":{\"id\"/", $response)) {
                $array = preg_split("/{\"script_tag\":{/", $response);

                if (preg_match('/\"id\":(.*?),\"src\":\"/', $array[1], $id) == 1) {
                    $scriptTagId = $id[1];
                }
                if (preg_match('/,\"src\":\"(.*?)\",\"event\":\"/', $array[1], $src) == 1) {
                    $scriptTagSrc = $src[1];
                }
                if (preg_match('/,\"event\":\"(.*?)\",\"created_at\":/', $array[1], $event) == 1) {
                    $scriptTagEvent = $event[1];
                }
                if (preg_match('/,\"created_at\":\"(.*?)\",\"updated_at\":/', $array[1], $created) == 1) {
                    $scriptTagCreated_at = $created[1];
                }
                if (preg_match('/,\"display_scope\":\"(.*?)\"}}/', $array[1], $scope) == 1) {
                    $scriptTagDisplayScope = $scope[1];
                }
            }

            $scriptTagDetails['script_tag_id'] = $scriptTagId;
            $scriptTagDetails['script_tag_src'] = $scriptTagSrc;
            $scriptTagDetails['script_tag_event'] = $scriptTagEvent;
            $scriptTagDetails['script_tag_created_at'] = $scriptTagCreated_at;
            $scriptTagDetails['script_tag_display_scope'] = $scriptTagDisplayScope;
            $scriptTagDetails['created_at'] = time();
        } else {
            $requestBody = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $accessToken),
                    'content' => $query
                )
            );
            $context = stream_context_create($requestBody);
            $response = file_get_contents($url, false, $context);
            $scriptTag = json_decode($response);

            $scriptTagDetails['script_tag_id'] = $scriptTag->script_tag->id;
            $scriptTagDetails['script_tag_src'] = $scriptTag->script_tag->src;
            $scriptTagDetails['script_tag_event'] = $scriptTag->script_tag->event;
            $scriptTagDetails['script_tag_created_at'] = $scriptTag->script_tag->created_at;
            $scriptTagDetails['script_tag_display_scope'] = $scriptTag->script_tag->display_scope;
            $scriptTagDetails['created_at'] = time();
        }

        return $scriptTagDetails;
    }

    function createUnInstallWhook($shop, $accessToken, $address)
    {
        $url = "https://" . $shop . "/admin/api/2020-04/webhooks.json";
        $address = "\"$address\"";
        $query = '{"webhook": {"topic": "app/uninstalled", "address": ' . $address . ', "format": "json"}}';
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "X-Shopify-Access-Token: " . $accessToken));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_VERBOSE, 0);
        curl_setopt($crl, CURLOPT_HEADER, 1);
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($crl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($crl);
        curl_close($crl);
        //  print_r($response);
        // $response = $this->testCurl($response, $accessToken, $query, $url);
        return $response;
    }

    function testCurl($response, $token, $query, $url)
    {
        if (
            preg_match("/HTTP\/1.1 200/", $response) || preg_match("/HTTP\/2 200/", $response) ||
            preg_match("/HTTP\/1.1 201/", $response) ||  preg_match("/HTTP\/2 201/", $response)
        ) {
            return $response;
        } else {
            $requestBody = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token),
                    'content' => $query
                )
            );
            $context = stream_context_create($requestBody);
            $response = file_get_contents($url, false, $context);
            return $response;
        }
    }

    public function metaService($productId, $shop, $token, $meta)
    {
        $url = "https://{$shop}/admin/products/{$productId}/metafields.json";
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_VERBOSE, 0);
        curl_setopt($crl, CURLOPT_HEADER, 1);
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($crl);
        curl_close($crl);

        preg_match('/{\"metafields\":(.*?)]}/', $response, $matches);
        $result = $matches[1] . ']';
        $result = json_decode($result);
        $found = 0;
        foreach ($result as $res) {
            if ($res->key == 'fit-info') {
                $found = 1;
                $id = $res->id;
            }
        }
        if ($found == 0) {
            return $this->createMetaField($productId, $shop, $token, $meta);
        } else {
            return $this->updateMetaField($productId, $shop, $token, $meta, $id);
        }
        //  return $result;
    }

    public function updateMetaField($productId, $shop, $token, $meta, $id)
    {
        $url = "https://" . $shop . "/admin/api/2020-07/products/{$productId}/metafields/{$id}.json";
        $meta =  "\"$meta\"";
        $query = '{"metafield": {"id": "' . $id . '","value": ' . $meta . ',"value_type": "string"}}';
        // return $query;
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "X-Shopify-Access-Token: " . $token));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_VERBOSE, 0);
        curl_setopt($crl, CURLOPT_HEADER, 1);
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($crl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($crl);
        curl_close($crl);
        return $response;
    }

    public function createMetaField($productId, $shop, $token, $meta)
    {
        $url = "https://" . $shop . "/admin/api/2020-07/products/{$productId}/metafields.json";
        $meta =  "\"$meta\"";
        $query = '{"metafield": {"namespace": "global","key": "fit-info","value": ' . $meta . ',"value_type": "string"}}';
        // return $query;
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "X-Shopify-Access-Token: " . $token));
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_VERBOSE, 0);
        curl_setopt($crl, CURLOPT_HEADER, 1);
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($crl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($crl);
        curl_close($crl);
        return $response;
    }
}
