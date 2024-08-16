<?php

namespace App\Extensions\Gateways\Epay;

use App\Classes\Extensions\Gateway;
use App\Helpers\ExtensionHelper;
use Illuminate\Http\Request;

class Epay extends Gateway
{
    /**
     * Get the extension metadata
     * 
     * @return array
     */
    public function getMetadata()
    {
        return [
            'display_name' => 'EPay',
            'version' => '1.0.0',
            'author' => 'IMZCC',
            'website' => 'https://github.com/IMZCC',
        ];
    }

    /**
     * Get all the configuration for the extension
     * 
     * @return array
     */
    public function getConfig()
    {
        return [
            [
                'name' => 'pid',
                'type' => 'text',
                'friendlyName' => '商户ID',
                'required' => true,
            ],
            [
                'name' => 'key',
                'type' => 'text',
                'friendlyName' => '密钥',
                'required' => true,
            ],
            [
                'name' => 'url',
                'type' => 'text',
                'friendlyName' => '地址',
                'required' => true,
            ],
        ];
    }

    /**
     * Get the URL to redirect to
     * 
     * @param int $total
     * @param array $products
     * @param int $invoiceId
     * @return string
     */
    public function pay($total, $products, $invoiceId)
    {
        $url = ExtensionHelper::getConfig('EPay', 'url');
        $pid = ExtensionHelper::getConfig('EPay', 'pid');
        $key = ExtensionHelper::getConfig('EPay', 'key');
        $description = '';
        foreach ($products as $product) {
            $description .= $product->name . 'x' . $product->quantity . ',';
        }

        $querystring = 'money=' . number_format($total, 2, '.', '') . '&';
        $querystring .= 'name=' . $description . '&';
        $querystring .= 'notify_url=' . url('/extensions/epay/webhook') . '&';
        $querystring .= 'out_trade_no=' . $invoiceId . '&';
        $querystring .= 'pid=' . $pid . '&';
        $querystring .= 'return_url=' . url('/extensions/epay/webhook');

        // call calculateSign
        $sign = md5($querystring . $key);

        $querystring .= '&sign=' . $sign . '&sign_type=MD5';

        return $url . 'submit.php' . '?' . $querystring;
    }

    public function webhook(Request $request)
    {
        $key = ExtensionHelper::getConfig('Epay', 'key');

        $data = $request->query();

        $get_sign = $data['sign'];

        unset($data['sign'], $data['sign_type']);
        ksort($data);
        $queryString = http_build_query($data, '', '&');

        $orderId = $data['out_trade_no'];

        $sign = md5($queryString . $key);

        if ($get_sign !== $sign) {
            ExtensionHelper::error('Epay', $data);
            return redirect()->route('clients.invoice.show', $orderId)->with('error', 'Payment Failed');
        }

        ExtensionHelper::paymentDone($orderId, 'Epay', $data['trade_no']);
        return redirect()->route('clients.invoice.show', $orderId)->with('success', 'Payment Successful');
    }
}
