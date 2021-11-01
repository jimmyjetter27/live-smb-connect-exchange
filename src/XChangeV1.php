<?php


/**
 * Class XChangeV1 at src/XChangeV1.php.
 * File containing XChangeV1 class
 * @api
 * @author Isaac Adzah Sai <isaacsai030@gmail.com>
 * @version 2.5.2
 */

//namespace JimmyJetter\SmbExchange;
namespace Korba;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Class XChangeV1 makes interruption with exchange easier.
 * @todo Write the Docs
 * @package Korba
 */
class XChangeV1 extends API
{
    protected $secret_key;
    protected $client_key;
    protected $client_id;
    protected static $live_url = 'https://xchange.korba365.com/api/v1.0';
//    protected static $live_url = 'https://xchange.korbaweb.com/api/v1.0'; // Cyber Sai's url
//    protected static $live_url = 'https://khc1qo46gk-vpce-0966664bebf81f8fa.execute-api.eu-west-1.amazonaws.com/private '; // new private (smb) exchange url
//    protected static $test_url = 'https://korba-xchange.herokuapp.com/api/v1.0';
    protected static $test_url = 'https://xchange.korba365.com/api/v1.0/'; // Rais Test
    protected static $aws_url = 'http://internal-awseb-e-e-awsebloa-kxexw3t2bgt7-1521297916.eu-west-1.elb.amazonaws.com/api/v1.0';

    public function __construct($secret_key, $client_key, $client_id, $mode = 'test', $proxy = null)
    {
        $headers = array(
            'Cache-Control: no-cache',
            'Content-Type: application/json'
        );
        if ($mode == 'aws') {
            $url = XChangeV1::$aws_url;
        } else if ($mode == 'test') {
            $url = XChangeV1::$test_url;
        } else {
            $url = XChangeV1::$live_url;
        }
        parent::__construct($url, $headers, $proxy);
        $this->secret_key = $secret_key;
        $this->client_key = $client_key;
        $this->client_id = $client_id;
    }

    private function getHMACHeader($data)
    {
        $data = (gettype($data) == 'string') ? json_decode($data, true) : $data;
        $data = array_merge($data, ['client_id' => $this->client_id]);
        $message = '';
        $i = 0;
        ksort($data);
        foreach ($data as $key => $value) {
            $message .= ($i == 0) ? "{$key}={$value}" : "&{$key}={$value}";
            $i++;
        }
        $hmac_signature = hash_hmac('sha256', $message, $this->secret_key);
        return ["Authorization: HMAC {$this->client_key}:{$hmac_signature}"];
    }

    protected function call($endpoint, $data, $extra_headers = null, $timeout = 20, $connection_timeout = 5)
    {
        $data = array_merge($data, ['client_id' => $this->client_id]);
        $extra_headers = ($extra_headers) ? array_merge($extra_headers, $this->getHMACHeader($data)) : $this->getHMACHeader($data);
        return parent::call($endpoint, $data, $extra_headers, $timeout, $connection_timeout);
    }

    protected function add_optional_data(&$data, $optional_data)
    {
        foreach ($optional_data as $key => $value) {
            if ($optional_data[$key]) {
                $data[$key] = $value;
            }
        }
    }

    public function collect(
        $customer_number, $amount, $transaction_id, $network_code, $callback_url,
        $vodafone_voucher_code = null, $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => Util::numberGHFormat($customer_number),
            'amount' => $amount,
            'transaction_id' => $transaction_id,
            'network_code' => $network_code,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'vodafone_voucher_code' => $vodafone_voucher_code,
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('collect/', $data);
    }

    public function disburse(
        $customer_number, $amount, $transaction_id, $network_code, $callback_url,
        $description = null, $extra_info = null, $bank_account_number = null,
        $bank_name = null, $bank_branch_name = null, $payer_name = null, $payer_mobile = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'amount' => $amount,
            'transaction_id' => $transaction_id,
            'network_code' => $network_code,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'extra_info' => $extra_info,
            'bank_account_number' => $bank_account_number,
            'bank_name' => $bank_name,
            'bank_branch_name' => $bank_branch_name,
            'payer_name' => $payer_name,
            'payer_mobile' => $payer_mobile
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('disburse/', $data);
    }

    public function top_up(
        $customer_number, $amount, $transaction_id, $network_code, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'amount' => $amount,
            'transaction_id' => $transaction_id,
            'network_code' => $network_code,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('topup/', $data);
    }

    private function internet_bundle_data(
        $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'transaction_id' => $transaction_id,
            'bundle_id' => $bundle_id,
            'amount' => $amount,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $data;
    }

    public function surfline_purchase(
        $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_bundle_data(
            $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('purchase_surfline_bundle/', $data);
    }

    public function surfline_new_purchase(
        $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_bundle_data(
            $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('new_purchase_surfline/', $data);
    }

    public function surfline_final_purchase(
        $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_bundle_data(
            $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('purchase_final_surfline_bundle/', $data);
    }

    public function surfline_bundles($customer_number)
    {
        $data = [
            'customer_number' => $customer_number
        ];
        $result = $this->call('get_surfline_bundles/', $data);
        if (isset($result['success']) && $result['success']) {
            $list = [];
            foreach ($result['bundles'] as $bundle) {
                array_push($list, [
                    'id' => $bundle['bundle_id'],
                    'description' => $bundle['description'],
                    'price' => $bundle['price'],
                    'validity' => $bundle['validity']
                ]);
            }
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function surfline_updated_bundles($customer_number, $filter = null)
    {
        $data = [
            'customer_number' => $customer_number
        ];
        $result = $this->call('get_updated_surfline_bundles/', $data);
        if (isset($result['success']) && $result['success'] && in_array($filter, array('AlwaysON', 'Unlimited', 'All Weather'))) {
            $list = [];
            if (isset($result['bundles'][$filter])) {
                foreach ($result['bundles'][$filter] as $bundle) {
                    array_push($list, [
                        'id' => $bundle['bundle_id'],
                        'description' => "{$bundle['description']} - GHC {$bundle['price']} - {$bundle['validity']}",
                        'price' => $bundle['price'],
                        'validity' => $bundle['validity']
                    ]);
                }
            }
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function surfline_final_bundles($customer_number, $filter = null)
    {
        $data = [
            'customer_number' => $customer_number
        ];
        $result = $this->call('get_final_surfline_bundles/', $data);
        if (isset($result['success']) && $result['success'] && in_array($filter, array('AlwaysON', 'Unlimited', 'AllWeather'))) {
            $list = [];
            if (isset($result['bundles'][$filter])) {
                foreach ($result['bundles'][$filter] as $bundle) {
                    array_push($list, [
                        'id' => $bundle['bundle_id'],
                        'description' => "{$bundle['description']} - GHC {$bundle['price']} - {$bundle['validity']}",
                        'price' => $bundle['price'],
                        'validity' => $bundle['validity']
                    ]);
                }
            }
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function busy_purchase(
        $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_bundle_data(
            $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('purchase_busy_bundle/', $data);
    }

    public function busy_updated_purchase(
        $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_bundle_data(
            $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('updated_purchase_busy_bundle/', $data);
    }

    public function busy_bundles($customer_number)
    {
        $data = [
            'customer_number' => $customer_number
        ];
        $result = $this->call('get_busy_bundles/', $data);
        if (isset($result['success']) && $result['success']) {
            $list = [];
            foreach ($result['list'] as $bundles) {
                foreach ($bundles['Bundle'] as $bundle) {
                    foreach ($bundle as $item) {
                        array_push($list, [
                            'id' => $item['PricePlanCode'],
                            'description' => $item['PricePlanName'],
                            'price' => $item['SalesPrice']
                        ]);
                    }
                }
            }
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function busy_updated_types($customer_number)
    {
        $data = [
            'customer_number' => $customer_number
        ];
        $result = $this->call('get_updated_busy_bundles/', $data);
        Log::debug(json_encode($result));
        if (isset($result['success']) && $result['success'] == true) {
            $list = [];
            foreach ($result['data']['list'] as $bundles) {
                array_push($list, $bundles['OfferGroupName']);
            }
            $out = [
                'success' => true,
                'bundles' => $list
            ];
            return $out;
        }
        return $result;
    }

    public function busy_updated_bundles($customer_number, $number)
    {
        $data = [
            'customer_number' => $customer_number
        ];
//        $result = $this->call('get_updated_busy_bundles/', $data);
        $result = $this->call('get_updated_busy_bundles/', $data);
        if (isset($result['success']) && $result['success']) {
            $list = [];
            foreach ($result['data']['list'] as $key => $bundles) {
                if ($key == $number - 1) {
                    foreach ($bundles['Bundle']['BundleList'] as $item) {
                        array_push($list, [
                            'id' => $item['PricePlanCode'],
                            'description' => " {$item['PricePlanName']} - GHC {$item['SalesPrice']}",
                            'price' => $item['SalesPrice']
                        ]);
                    }
                }
            }
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function busy_updated_balance($customer_number)
    {
        $data = [
            'customer_number' => $customer_number
        ];

        return $this->call('new_check_busy_balance/', $data);
    }

    public function telesol_purchase(
        $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_bundle_data(
            $customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('purchase_telesol_bundle/', $data);
    }

    public function telesol_bundles()
    {
        return $this->call('get_telesol_bundles/', []);
    }

    public function ecg_pay(
        $customer_number, $transaction_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('ecg_pay_bill/', $data);
    }

    public function gwcl_lookup($customer_number, $account_number, $transaction_id)
    {
        $data = [
            'customer_number' => Util::number233Format($customer_number),
            'account_number' => $account_number,
            'transaction_id' => $transaction_id
        ];
        return $this->call('gwcl_customer_lookup/', $data);
    }

    public function gwcl_pay($transaction_id, $gwcl_transaction_id, $amount, $callback_url, $description = null)
    {
        $data = [
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'callback_url' => $callback_url,
            'gwcl_lookup_session_id' => $gwcl_transaction_id,
        ];
        $opt_data = ['description' => $description];
        $this->add_optional_data($data, $opt_data);
        return $this->call('gwcl_pay_bill/', $data);
    }

    private function internet_product_data(
        $customer_number, $transaction_id, $product_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'transaction_id' => $transaction_id,
            'product_id' => $product_id,
            'amount' => $amount,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $data;
    }

    public function mtn_purchase(
        $customer_number, $transaction_id, $product_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_product_data(
            $customer_number, $transaction_id, $product_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('mtn_data_topup/', $data);
    }

    public function mtn_bundles($filter = null)
    {
        $result = $this->call('get_mtndata_product_id/', []);
//        Log::debug(json_encode($result));
//        Log::debug($result);
        $list = [];
        if (isset($result['success']) && $result['success']) {
//            foreach ($result['bundles'][0]['bundles'] as $bundle) {
//            foreach ($result['bundles'] as $bundle) {
//                array_push($list, [
//                        'id' => $bundle['product_id'],
//                        'price' => $bundle['amount'],
//                        'description' => $bundle['name'],
//                        'short_description' => preg_replace('/^MTN ((Daily)|(Weekly)|(Monthly)|(YouTube)) Data Bundle /', '', $bundle['name'],)
//                    ]);
//            }
            dd($result);
            foreach ($result['bundles'] as $data_list) {
                foreach ($data_list as $firstBundles) {
                    foreach ($firstBundles['bundles'] as $secondBundles) {
                        foreach ($secondBundles['bundles'] as &$bundle) {
                            array_push($list, [
                                'id' => $bundle['product_id'],
                                'price' => $bundle['amount'],
                                'description' => $bundle['name']
                            ]);
                        }
                    }
                }
            }
            $list = $this->mtn_filter($list, $filter);
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    private function mtn_filter($bundles, $filter = null)
    {
        if (in_array($filter, ['daily', 'weekly', 'monthly', 'midnight', 'lifestyle', 'youtube'])) {
            if ($filter == 'daily') {
                $result = array_filter($bundles, function ($product) {
                    return preg_match("/^MTNDLY*/", $product['id']);
                });
            } else if ($filter == 'weekly') {
                $result = array_filter($bundles, function ($product) {
                    return preg_match("/^MTNWKLY*/", $product['id']);
                });
            } else if ($filter == 'monthly') {
                $result = array_filter($bundles, function ($product) {
                    return preg_match("/^MTNMTH*/", $product['id']);
                });
            } else if ($filter == 'midnight') {
                $result = array_filter($bundles, function ($product) {
                    return preg_match("/^MTNMIDNGT3G$/", $product['id']) || preg_match("/^MTNMIDNIGHT$/", $product['id']);
                });
            } else if ($filter == 'lifestyle') {
                $result = array_filter($bundles, function ($product) {
                    return preg_match("/^MTNLIFESTYLE$/", $product['id']);
                });
            } else {
                $result = array_filter($bundles, function ($product) {
                    return preg_match("/^MTNYT*/", $product['id']);
                });
            }
            return array_values($result);
        }
        return $bundles;
    }

    public function mtn_fibre_purchase(
        $customer_number, $transaction_id, $product_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_product_data(
            $customer_number, $transaction_id, $product_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('mtn_fibre_topup/', $data);
    }

    public function mtn_fibre_bundles()
    {
        $result = $this->call('get_mtnfibre_product_id/', []);
        $list = [];
        if (isset($result['success']) && $result['success']) {
            foreach ($result['bundles'] as $bundle) {
                array_push($list, [
                    'id' => $bundle['product_id'],
                    'price' => $bundle['amount'],
                    'description' => "{$bundle['name']} - GHC " . preg_replace('/.00/', '', $bundle['amount'])
                ]);
            }
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function vodafone_bundles($filter = null)
    {
        $result = $this->call('get_vodafonedata_product_id/', []);
        if (isset($result['success']) && $result['success']) {
            $list = [];
            foreach ($result['bundles'] as $bundle) {
                array_push($list, [
                    'id' => $bundle['bundle_size'],
                    'price' => $bundle['amount'],
                    'description' => " {$bundle['bundle_size']} - GHC {$bundle['amount']} - {$bundle['validity']}",
                    'size' => $bundle['bundle_size'],
                    'validity' => $bundle['validity'],
                    'name' => $bundle['name']
                ]);
            }

            if ($filter != null && in_array($filter, ['daily', 'weekly', 'monthly'])) {
                $list = array_filter($list, function ($value) use ($filter) {
                    $validity = explode(' ', $value['validity']);
                    if ($filter == 'daily') {
                        return intval($validity[0]) >= 1 && intval($validity[0]) < 7;
                    } else if ($filter == 'weekly') {
                        return intval($validity[0]) >= 7 && intval($validity[0]) < 30;
                    } else {
                        return intval($validity[0]) >= 30;
                    }
                });
                $list = array_values($list);
            }

            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function new_vodafone_bundles($filter = null)
    {
        $result = $this->call('new_get_vodafonedata_product_id/', []);

        if (isset($result['success']) && $result['success']) {
            $list = [];
            foreach ($result['bundles'] as $bundle) {
                array_push($list, [
                    'id' => $bundle['bundle_id'],
                    'price' => $bundle['amount'],
                    'description' => " {$bundle['bundle_size']} - GHC {$bundle['amount']} - {$bundle['validity']}",
                    'size' => $bundle['bundle_size'],
                    'category' => $bundle['category'],
                    'validity' => $bundle['validity'],
                    'name' => $bundle['name']
                ]);
            }

            if ($filter != null && in_array($filter, ['DAILY', 'WEEKLY', 'MONTHLY', 'NO EXPIRY', 'JUMBO'])) {
                $list = array_filter($list, function ($value) use ($filter) {
                    return $value['category'] == $filter;
                });

                $list = array_values($list);
            }

            return [
                'success' => true,
                'bundles' => $list
            ];
        }

        return $result;
    }

    public function vodafone_purchase($customer_number, $transaction_id, $amount, $callback_url,
                                      $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('vodafone_data_topup/', $data);
    }

    public function new_vodafone_purchase($customer_number, $transaction_id, $bundle_id, $amount, $callback_url,
                                          $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'transaction_id' => $transaction_id,
            'bundle_id' => $bundle_id,
            'amount' => $amount,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('new_vodafone_data_topup/', $data);
    }

    public function airteltigo_purchase(
        $customer_number, $transaction_id, $product_id, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = $this->internet_product_data(
            $customer_number, $transaction_id, $product_id, $amount, $callback_url,
            $description, $payer_name, $extra_info);
        return $this->call('airteltigo_data_topup/', $data);
    }

    public function airteltigo_bundles($filter = null)
    {
        $result = $this->call('get_airteltigodata_product_id/', []);
        $list = [];
        if (isset($result['success']) && $result['success']) {
            foreach ($result['bundles'] as $bundle) {
                array_push($list, [
                    'id' => $bundle['product_id'],
                    'price' => $bundle['amount'],
                    'description' => $bundle['category'] == 'XTRA_UNLIMITED_CALLS' ?
                        "{$bundle['name']} @ GHC {$bundle['amount']} - {$bundle['validity']}" :
                        "{$bundle['name']}+{$bundle['name']} @ GHC {$bundle['amount']} - {$bundle['validity']}",
                    'size' => $bundle['name'],
                    'category' => $bundle['category'],
                    'validity' => $bundle['validity'],
                ]);
            }
            $list = $this->airteltigo_filter($list, $filter);
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    private function airteltigo_filter($bundles, $filter)
    {
        if ($filter != null && in_array($filter, ['BIGTIME', 'SIKA_KOKOOR', 'XTRA_UNLIMITED_CALLS'])) {
            $result = array_filter($bundles, function ($product) use ($filter) {
                return $product['category'] == $filter;
            });
            return array_values($result);
        }
        return $bundles;
    }

    public function glo_types()
    {
        return $this->call('glo_data_get_bundle_types/', []);
    }

    public function new_glo_types()
    {
        return $this->call('new_glo_data_get_bundle_types/', []);
    }

    public function glo_bundles($bundle_type_id)
    {
        $data = ['bundle_type_id' => $bundle_type_id];
        $result = $this->call('glo_data_get_bundles/', $data);
        $list = [];
        if (isset($result['success']) && $result['success']) {
            foreach ($result['results'] as $bundle) {
                array_push($list, [
                    'id' => $bundle['productId'],
                    'description' => $bundle['name'],
                    'price' => $bundle['price'],
                    'volume' => $bundle['volume'],
                    'validity' => $bundle['validity']
                ]);
            }
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function new_glo_bundles($bundle_type_id)
    {
        $data = ['bundle_type_id' => $bundle_type_id];
        $result = $this->call('new_glo_data_get_bundles/', $data);
        $list = [];
        if (isset($result['success']) && $result['success']) {
            foreach ($result['results'] as $bundle) {
                array_push($list, [
                    'id' => $bundle['productId'],
                    'description' => $bundle['name'],
                    'price' => $bundle['price'],
                    'volume' => $bundle['volume'],
                    'validity' => $bundle['validity'],
                ]);
            }
            return [
                'success' => true,
                'bundles' => $list
            ];
        }
        return $result;
    }

    public function glo_purchase($customer_number, $bundle_id, $amount, $transaction_id, $callback_url, $description = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'bundle_id' => $bundle_id,
            'amount' => $amount,
            'transaction_id' => $transaction_id,
            'callback_url' => $callback_url
        ];
        $opt_data = ['description' => $description];
        $this->add_optional_data($data, $opt_data);

        $result = $this->call('glo_data_purchase/', $data);
        return $result;
    }

    public function new_glo_purchase($customer_number, $bundle_id, $amount, $transaction_id, $callback_url, $description = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'bundle_id' => $bundle_id,
            'amount' => $amount,
            'transaction_id' => $transaction_id,
            'callback_url' => $callback_url
        ];
        $opt_data = ['description' => $description];
        $this->add_optional_data($data, $opt_data);

        $result = $this->call('new_glo_data_purchase/', $data);
        return $result;
    }

    public function etransact_validate($customer_number, $bill_type, $transaction_id)
    {
        $data = [
            'customer_number' => $customer_number,
            'bill_type' => $bill_type,
            'transaction_id' => $transaction_id
        ];
        return $this->call('etransact_validate_user/', $data);
    }

    public function new_etransact_validate($customer_number, $bill_type, $transaction_id)
    {
        $data = [
            'customer_number' => $customer_number,
            'bill_type' => $bill_type,
            'transaction_id' => $transaction_id
        ];
        return $this->call('new_etransact_validate_user/', $data);
//        $result = $this->call('new_etransact_validate_user/', $data);
//        Log::debug(json_encode($result));
    }

    public function etransact_pay(
        $customer_number, $bill_type, $transaction_id, $sender_name, $address, $amount, $callback_url,
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'bill_type' => $bill_type,
            'transaction_id' => $transaction_id,
            'sender_name' => $sender_name,
            'address' => $address,
            'amount' => $amount,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('etransact_pay_bill/', $data);
    }

    public function new_etransact_pay(
        $customer_number, $bill_type, $transaction_id, $sender_name, $address, $amount, $callback_url,
        $customer_phone = null, $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => $customer_number,
            'bill_type' => $bill_type,
            'transaction_id' => $transaction_id,
            'sender_name' => $sender_name,
            'address' => $address,
            'amount' => $amount,
            'callback_url' => $callback_url
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info,
            'customer_phone_number' => $customer_phone
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('new_etransact_pay_bill/', $data);
    }

    public function transaction_status($transaction_id)
    {
        $data = [
            'transaction_id' => $transaction_id
        ];
        return $this->call('transaction_status/', $data);
    }

    public function mtn_recurring_create_mandate(
        $customer_number, $transaction_id, $amount, $mandate_creation_callback_url, $debit_customer_callback_url,
        $debit_day, $frequency_type, $frequency = 1, $start_date = 'today', $end_date = 'infinite',
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => Util::number233Format($customer_number),
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'frequency_type' => $frequency_type,
            'frequency' => $frequency,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'mandate_creation_call_back_url' => $mandate_creation_callback_url,
            'debit_customer_call_back_url' => $debit_customer_callback_url,
            'debit_day' => $debit_day
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('mtn_recurring_create_mandate/', $data);
    }

    public function mtn_recurring_update_mandate(
        $customer_number, $transaction_id, $amount, $mandate_id,
        $debit_day, $frequency_type, $frequency = 1, $start_date = 'today', $end_date = 'infinite',
        $description = null, $payer_name = null, $extra_info = null)
    {
        $data = [
            'customer_number' => Util::number233Format($customer_number),
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'frequency_type' => $frequency_type,
            'frequency' => $frequency,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'mandate_id' => $mandate_id,
            'debit_day' => $debit_day
        ];
        $opt_data = [
            'description' => $description,
            'payer_name' => $payer_name,
            'extra_info' => $extra_info
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('mtn_recurring_update_mandate/', $data);
    }

    public function mtn_recurring_cancel_mandate($customer_number, $transaction_id, $mandate_id, $description = null)
    {
        $data = [
            'customer_number' => Util::number233Format($customer_number),
            'transaction_id' => $transaction_id,
            'mandate_id' => $mandate_id
        ];
        $opt_data = [
            'description' => $description
        ];
        $this->add_optional_data($data, $opt_data);
        return $this->call('mtn_recurring_cancel_mandate/', $data);
    }

    public function mtn_recurring_cancel_pre_approval($customer_number)
    {
        $data = [
            'customer_number' => Util::number233Format($customer_number)
        ];
        return $this->call('mtn_recurring_cancel_pre_approval/', $data);
    }

    public function mtn_recurring_check_mandate_status($transaction_id)
    {
        $data = [
            'transaction_id' => $transaction_id
        ];
        return $this->call('mtn_recurring_check_mandate_status/', $data);
    }
}
