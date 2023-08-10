<?php

namespace Ebizmarts\MailChimp\Cron;

class WebhookClean
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpWebhookRequest
     */
    protected $webhooks;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\MailChimpWebhookRequest $webhookRequest
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\MailChimpWebhookRequest $webhookRequest
    ) {
        $this->helper = $helper;
        $this->webhooks = $webhookRequest;
    }

    public function execute()
    {
        try {
            $connection = $this->webhooks->getResource()->getConnection();
            $tableName = $this->webhooks->getResource()->getMainTable();
            $quoteInto = $connection->quoteInto('processed = ? and date_add(fired_at, interval 1 month) < now()', 1);
            $connection->delete($tableName, $quoteInto);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }
}
