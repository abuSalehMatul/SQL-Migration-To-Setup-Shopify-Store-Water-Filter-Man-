<?php

namespace App\Http\Controllers;

use App\ShopifyCustomer;
use App\Token;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPShopify\Customer;

use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

class CustomerController extends Controller
{
    public function setCustomer()
    {
        $customerIrs = DB::table('ecom_members')->get();
        foreach ($customerIrs as $customer) {
            $this->saveCustomer($customer, "Irish Customer");
        }

        $customerIrs = DB::table('ecom_members_uk')->get();
        foreach ($customerIrs as $customer) {
            $this->saveCustomer($customer, "UK Customer");
        }
    }

    public function saveCustomer($customer, $tag)
    {
        $check = ShopifyCustomer::where('email', $customer->email)->first();
        if ($check == null) {
            $cus = new ShopifyCustomer();
            $cus->user_name = $customer->username;
            $cus->email = $customer->email;
            $cus->first_name = $customer->firstname;
            $cus->last_name = $customer->lastname;
            $cus->phone = $customer->phone;
            $cus->mobile = $customer->mobile;
            $cus->current_status = $customer->status;
            $cus->date_added = $customer->date_added;
            $cus->order_count = $customer->order_count;
            $cus->order_value = $customer->order_value;
            $cus->spl_offer = $customer->spl_offer;
            $cus->guest_status = $customer->guest_status;
            $cus->tag = $tag;
            $cus->member_id = $customer->id;
            $cus->password = $customer->password;
            $cus->save();
            if ($tag == "Irish Customer") {
                $addresses = DB::table('ecom_members_address')->where('member_id', $cus->member_id)->get();
                $addr = [];
                foreach ($addresses as $address) {
                    array_push($addr, $address);
                }
                $difCus = DB::table('ecom_members_uk')->where('email', $cus->email)->first();
                if ($difCus) {
                    $addresses = DB::table('ecom_members_address_uk')->where('member_id', $difCus->id)->get();
                    foreach ($addresses as $address) {
                        array_push($addr, $address);
                    }
                }
                $cus->addresses = json_encode($addr);
                $cus->save();
            } else {

                $addr = [];
                $difCus = DB::table('ecom_members')->where('email', $cus->email)->first();
                if ($difCus) {
                    $addresses = DB::table('ecom_members_address')->where('member_id', $difCus->id)->get();
                    foreach ($addresses as $address) {
                        array_push($addr, $address);
                    }
                }
                $addresses = DB::table('ecom_members_address_uk')->where('member_id', $cus->member_id)->get();
                foreach ($addresses as $address) {
                    array_push($addr, $address);
                }


                $cus->addresses = json_encode($addr);
                $cus->save();
            }
        }
    }


    public function upCustomer()
    {
        $token = Token::first();
        $customers = ShopifyCustomer::where('status_update', 0)->whereNotNull('email')->get();
        for ($i = 0; $i < 30; $i++) {
            $addresses = [];
            $customerAddressess = json_decode($customers[$i]->addresses);
            if (sizeof($customerAddressess) > 0) {
                for ($j = 0; $j < sizeof($customerAddressess); $j++) {
                    $country = DB::table('ecom_country_description')->where('country_id', $customerAddressess[$j]->country_id)->first();
                    $addresses[] = [
                        "address1" => $customerAddressess[$j]->address_1,
                        "city" => $customerAddressess[$j]->city,
                        "phone" => $customerAddressess[$j]->phone,
                        "zip" => $customerAddressess[$j]->postcode,
                        "last_name" => $customerAddressess[$j]->lastname,
                        "first_name" => $customerAddressess[$j]->firstname,
                        "country" => optional($country)->name == "UK" ? "United Kingdom" : optional($country)->name
                    ];
                    if ($customerAddressess[$j]->phone == null) {
                        unset($addresses[$j]['phone']);
                    }
                    if ($country == null) {
                        unset($addresses[$j]['country']);
                    }
                }
            }
            $password = $customers[$i]->password;
            if (strlen($customers[$i]->password) < 7) {
                $password = "AA#" . rand(0, 8) . "aa";
            }

            $data = [
                "customer" => [
                    "first_name" => $customers[$i]->first_name,
                    "last_name" => $customers[$i]->last_name,
                    "email" => $customers[$i]->email,
                    "phone" => $customers[$i]->phone,
                    "tags" =>  $customers[$i]->tag,
                    "verified_email" => true,
                    "accepts_marketing" => true,
                    "tax_exempt" => true,
                    "addresses" => $addresses,
                    "password" => $password,
                    "password_confirmation" => $password,
                    "send_email_welcome" => false
                ]
            ];

            if (sizeof($addresses) == 0) {
                unset($data['customer']['addresses']);
            }

            $token = Token::first();
            $data = json_encode($data);
            // return $data;
            $url =  "https://water-filter-men.myshopify.com/admin/api/2020-07/customers.json";
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
            $output->writeln(json_encode($body));

            $deBody = json_decode($body);
            if (property_exists($deBody, 'customer')) {
                $deBody = json_decode($body);
                $customers[$i]->status_update = 1;
                $customers[$i]->shopify_id = $deBody->customer->id;
                $customers[$i]->save();
                $fileName = "database_product_" .  $customers[$i]->id . "_shopify_id_" . $deBody->customer->id . ".json";
                // $value = (($body), JSON_PRETTY_PRINT);
                Storage::disk('customer')->put($fileName, $body);
            }
            if (property_exists($deBody, 'errors')) {
                $customers[$i]->shopify_id = json_encode($deBody->errors);
                $customers[$i]->save();
            }


           
        }
        return "done";
    }
}
