<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/2/17 11:46 AM
 * @file: DateFormat.php
 */
namespace Ebizmarts\MailChimp\Model\Config\Source;

class DateFormat implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label'=>__('DD/MM/YYYY')],
            ['value' => 1, 'label'=>__('MM/DD/YYYY')]
        ];
    }
}
