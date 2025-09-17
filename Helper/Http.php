<?php

namespace Ebizmarts\MailChimp\Helper;

use Ebizmarts\MailChimp\Model\HTTP\Client\Curl;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;

class Http
{
    const NO_ERROR = 0;
    const ERROR_JSON = 1;
    const ERROR_AUTH = 2;
    const ERROR_GENERIC = 3;
    /**
     * @var Curl
     */
    protected $curl;
    protected $url;
    /**
     * @var MailChimpHelper
     */
    protected $helper;
    public function __construct(
        Curl $curl,
        MailChimpHelper $helper
    ) {
        $this->curl = $curl;
        $this->url = $helper->getConfigValue(MailChimpHelper::SYNC_NOTIFICATION_URL);
        $token = $helper->getConfigValue(MailChimpHelper::SYNC_TOKEN);
        $headers = ['Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setHeaders($headers);
        $this->helper = $helper;
    }
    public function setUrl($url)
    {
        $this->url = $url;
        $token = $this->helper->getConfigValue(MailChimpHelper::SYNC_TOKEN);
        $headers = ['Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setHeaders($headers);
    }
    public function post($body)
    {
        $this->curl->post($this->url , $body);
        $response = $this->curl->getBody();
        return $response;
    }
    public function patch($id,$body)
    {
        $this->curl->patch($this->url .'/'. $id , $body);
        $response = $this->curl->getBody();
        return $response;
    }
    public function get($id)
    {
        $this->curl->get($this->url .'/'. $id);
        $response = $this->curl->getBody();
        return $response;
    }
    public function put($id,$body)
    {
        $this->curl->put($this->url .'/'. $id , $body);
        $response = $this->curl->getBody();
        return $response;
    }
    public function extractResponse($response)
    {
        try {
            $data = json_decode($response, true);
            if (is_null($data) || json_last_error() !== JSON_ERROR_NONE) {
                return self::ERROR_JSON;
            }
            if (key_exists('error', $data)) {
                if (!$data['error']) {
                    return self::NO_ERROR;
                } else {
                    $this->helper->log("Error found");
                    $this->helper->log($response);
                    $this->helper->log($data['message']);
                    return self::ERROR_AUTH;
                }
            }
        } catch (\Exception $e) {
            $this->helper->log("Exception found");
            $this->helper->log($response);
            $this->helper->log($e->getMessage());
            return self::ERROR_GENERIC;
        }
        return self::ERROR_GENERIC;
    }
}
