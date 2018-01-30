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

class MonkeyStore implements \Magento\Framework\Option\ArrayInterface
{
    private $options = null;

    /**
     * MonkeyStore constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $storeId = (int) $request->getParam("store", 0);

        if ($helper->getApiKey($storeId)) {
            try {
                $this->options = $helper->getApi()->ecommerce->stores->get(null, null, null, \Ebizmarts\MailChimp\Helper\Data::MAXSTORES);
            } catch (\Exception $e) {
                $helper->log($e->getMessage());
            }
        }
    }
    public function toOptionArray()
    {
        if (is_array($this->options)) {
            $rc = [];
            $rc[] = ['value' => -1, 'label' => 'Select one Mailchimp Store'];
            foreach ($this->options['stores'] as $store) {
                if ($store['platform'] == \Ebizmarts\MailChimp\Helper\Data::PLATFORM) {
                    if($store['list_id']=='') {
                        continue;
                    }
                    $rc[] = ['value'=> $store['id'], 'label' => $store['name']];
                }
            }
        } else {
            $rc[] = ['value' => 0, 'label' => __('---No Data---')];
        }
        return $rc;
    }
    public function toArray()
    {
        $rc = [];
        foreach ($this->options['stores'] as $store) {
            $rc[$store['id']] = $store['name'];
        }
        return $rc;
    }
}
