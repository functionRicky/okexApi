<?php

/*
 * okex钱包api接口
 */

class OkexApi {

    protected $url;
    protected $apiUrl;
    protected $apiSecret;
    protected $apiKey;
    protected $passPhrase;
    protected $currency;
    private $giftModels;

    function __construct() {
        parent::__construct();
        $this->url = 'https://www.okex.me';
        $this->apiUrl = '/api/account/v3/deposit/history/';
        $this->apiSecret = '';
        $this->apiKey = '';
        $this->passPhrase = '';
        $this->currency = 'usdt'; //查询币种
        $this->serverTime = '/api/general/v3/time'; //okex服务器时间
    }

    /*
     * 获取okex单个币种充值记录
     */

    public function index() {
        $res = $this->okexCurrency();
        echo json_encode($res);
    }

    /*
     * 获取okex单个币种充值记录
     */

    public function okexCurrency() {
        try {
            $params = [
                'currency' => $this->currency
            ];
            $path = $this->apiUrl . $this->currency;
            $res = self::request($path, $params, 'GET');
            return $res;
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    /*
     * 接口发起请求
     */

    private function request($requestPath, $params, $method, $cursor = false) {
        try {
            if (strtoupper($method) == 'GET') {
                $requestPath .= $params ? '?' . http_build_query($params) : '';
                $params = [];
            }
            $url = $this->url . $requestPath;
            $body = $params ? json_encode($params, JSON_UNESCAPED_SLASHES) : '';
            $timestamp = self::getServerTimestamp();
            $sign = self::signature($timestamp, $method, $requestPath, $body, $this->apiSecret);
            $headers = self::getHeader($this->apiKey, $sign, $timestamp, $this->passPhrase);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($method == "POST") {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $return = curl_exec($ch);
            $return = json_decode($return, true);
            return $return;
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    /*
     * 请求头
     */

    private function getHeader($apiKey, $sign, $timestamp, $passphrase) {
        $headers = array();
        $headers[] = "Content-Type: application/json";
        $headers[] = "OK-ACCESS-KEY: $apiKey";
        $headers[] = "OK-ACCESS-SIGN: $sign";
        $headers[] = "OK-ACCESS-TIMESTAMP: $timestamp";
        $headers[] = "OK-ACCESS-PASSPHRASE: $passphrase";
        return $headers;
    }

    /*
     * 时间
     */

    private function getTimestamp() {
        return date("Y-m-d\TH:i:s") . substr((string) microtime(), 1, 4) . 'Z';
    }

    /*
     * 获取ok服务器时间
     */

    private function getServerTimestamp() {
        try {
            $response = file_get_contents($this->url . $this->serverTime);
            $response = json_decode($response, true);
            return $response['iso'];
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    /*
     * sign签名
     */

    private function signature($timestamp, $method, $requestPath, $body, $secretKey) {
        $message = (string) $timestamp . strtoupper($method) . $requestPath . (string) $body;
        return base64_encode(hash_hmac('sha256', $message, $secretKey, true));
    }

}
