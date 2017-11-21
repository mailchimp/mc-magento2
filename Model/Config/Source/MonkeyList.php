<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 3/28/17 10:57 AM
 * @file: MonkeyStore.php
 */

namespace Ebizmarts\MailChimp\Model\Config\Source;

/**
 * Source model for MailChimp lists
 */
class MonkeyList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * MonkeyList constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * Get lists options array
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        if (empty($this->toArray())) {
            $options[] = ['value' => 0, 'label' => __('---No Data---')];
        } else {
            foreach ($this->toArray() as $id => $name) {
                $options[] = ['value' => $id, 'label' => $name];
            }
        }

        return $options;
    }

    /**
     * Get array of lists
     * @return array
     */
    public function toArray()
    {
        $lists = $this->_helper->getApi()->lists->getLists();
        $options = [];

        if (is_array($lists) && is_array($lists['lists'])) {
            foreach ($lists['lists'] as $list) {
                $options[$list['id']] = $list['name'];
            }
        }

        return $options;
    }
}