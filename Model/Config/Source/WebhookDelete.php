<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/31/17 11:26 AM
 * @file: WebhookDelete.php
 */

namespace Ebizmarts\MailChimp\Model\Config\Source;

class WebhookDelete implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label'=>__('Unsubscribe')],
            ['value' => 1, 'label'=>__('Delete subscriber')]
        ];
    }
}
