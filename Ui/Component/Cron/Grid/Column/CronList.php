<?php

/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/3/17 3:10 PM
 * @file: Batch.php
 */

namespace Ebizmarts\MailChimp\Ui\Component\Cron\Grid\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class CronList extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var Helper
     */
    protected $mailChimpSyncB;
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Batch constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\MailChimpCron $mailChimpCron,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->$mailChimpCron = $mailChimpCron;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function getDataSourceData()
    {
        return $this->getContext()->getDataProvider()->getData();
    }
}
