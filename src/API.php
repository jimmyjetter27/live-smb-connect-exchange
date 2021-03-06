<?php

/**
 * Class API at src/API.php.
 * File containing API class
 * @api
 * @author Isaac Adzah Sai <isaacsai030@gmail.com>
 * @version 2.5.2
 */
//namespace JimmyJetter\SmbExchange;
namespace Korba;
/**
 * Class API helps access API quickly.
 * A class to help quickly make request to api endpoints.
 * Only Content-Type _application/json_ is supported.
 * And methods currently supported are *GET* and *POST*.
 * @package Korba
 */
class API
{
    /** @var string The base url of the api request endpoint. */
    private $base_url;
    /** @var array The headers that will be appended to each request. */
    private $headers;
    /** @var string|null The proxy url to be used request.  */
    private $proxy_url;
    /** @var string|null The proxy authentication in the form basic auth. */
    private $proxy_auth;

    /**
     * API constructor.
     * It used to create a new instance of the API Class.
     * @param string $base_url Base url of the api endpoint.
     * @param array $headers Headers to include in each api request.
     * @param null|string $proxy Proxy url to parse into the various components.
     */
    public function __construct($base_url, $headers, $proxy = null)
    {
        $this->base_url = $base_url;
        $this->headers = $headers;
        if ($proxy) {
            $quota_guard = parse_url($proxy);
            $this->proxy_url = "{$quota_guard['host']}:{$quota_guard['port']}";
            $this->proxy_auth = "{$quota_guard['user']}:{$quota_guard['pass']}";
        }
    }

    /**
     * API private function engine.
     * A function used by the API class to actually make a curl request to the intended api endpoint.
     * @param string $end_point Endpoint to be used in addition to the base_url to construct full url path.
     * @param string $data JsonEncoded string of the intended request data to send as the request body.
     * @param array $extra_headers Any Extra headers that needs to be added to specific request.
     * @return bool|string|array The result return after try to or connecting to the api endpoint.
     */

    private function engine($end_point, $data = null, $extra_headers = null, $timeout = 0, $connection_timeout = 300) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->base_url}/{$end_point}");
        if ($data != null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setOpt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connection_timeout);
        if ($extra_headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($this->headers, $extra_headers));
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        if ($this->proxy_url) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy_url);
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy_auth);
        }
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        return ($error) ? $error : $result;
    }

    /**
     * API protected function call.
     * A function used to make request to api endpoint. It clean the input data to a form suitable usable by the class
     * @uses \Korba\API::engine to make the actual request
     * @param string $endpoint Endpoint to be used in addition to the base_url to construct full url path.
     * @param string|array $data JsonEncoded string of the intended request data to send as the request body.
     * @param array $extra_headers The result return after try to or connecting to the api endpoint.
     * @return bool|string|array
     */
    protected function call($endpoint, $data, $extra_headers = null, $timeout = 0, $connection_timeout = 300) {
        $res = (gettype($data) == 'array') ? $this->engine($endpoint, json_encode($data), $extra_headers, $timeout, $connection_timeout) : $this->engine($endpoint, $data, $extra_headers, $timeout, $connection_timeout);
        $result = json_decode($res, true);
        return $result;
    }
}
