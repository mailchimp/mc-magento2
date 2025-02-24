<?php

namespace Ebizmarts\MailChimp\Helper;

use Ebizmarts\MailChimp\Helper\Curl;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;

class Http
{
    /**
     * @var Curl
     */
    protected $curl;
    protected $url;
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
    public function post($body)
    {
        $this->curl->post($this->url , $body);
        $response = $this->curl->getBody();
        return $response;
    }
    public function extractResponse($response)
    {
        $data = json_decode($response, true);
        if (key_exists('error', $data) && !$data['error']) {
            return true;
        }
        return false;
    }
}