<?php

namespace Ebizmarts\MailChimp\Model\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/MailChimp.log';

    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;
}
