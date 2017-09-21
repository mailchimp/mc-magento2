<?php
/**
 * Ebizmarts_MailChimp Magento JS component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


namespace Ebizmarts\MailChimp\Model\Config\Source;

class Details implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var null
     */
    private $_options = null;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper  = null;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_message;
    private $_error = '';
    private $storeId;

    /**
     * Details constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Framework\Message\ManagerInterface $message
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\Message\ManagerInterface $message,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_message = $message;
        $this->_helper  = $helper;
        $this->storeId = (int) $request->getParam("store", 0);

        if ($this->_helper->getApiKey()) {
            $api = $this->_helper->getApi();
            try {
                $this->_options = $api->root->info();
                $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,$this->storeId);
                if ($mailchimpStoreId && $mailchimpStoreId!=-1 && $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ECOMMERCE_ACTIVE,$this->storeId)) {
                    $storeInfo = $api->ecommerce->stores->get($mailchimpStoreId);
                    if(!$storeInfo['is_syncing']) {
                        $this->_options['is_syncing'] = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC,$this->storeId);
                        $this->_helper->log($this->_options['is_syncing']);
                    }
                    else {
                        $this->_options['is_syncing'] = false;
                    }
                    $this->_options['store_exists'] = true;
                    $totalCustomers = $api->ecommerce->customers->getAll($mailchimpStoreId, 'total_items');
                    $this->_options['total_customers'] = $totalCustomers['total_items'];
                    $totalProducts = $api->ecommerce->products->getAll($mailchimpStoreId, 'total_items');
                    $this->_options['total_products'] = $totalProducts['total_items'];
                    $totalOrders = $api->ecommerce->orders->getAll($mailchimpStoreId, 'total_items');
                    $this->_options['total_orders'] = $totalOrders['total_items'];
                    $totalCarts = $api->ecommerce->carts->getAll($mailchimpStoreId, 'total_items');
                    $this->_options['total_carts'] = $totalCarts['total_items'];
                } else {
                    $this->_options['store_exists'] = false;
                }
            } catch (\Mailchimp_Error $e) {
                $this->_error = $e->getMessage();
                $this->_options['store_exists'] = false;
            }
        } else {
            $this->_options = '--- Enter your API Key ---';
        }
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $ret = '';
        if (is_array($this->_options)) {
            if (isset($this->_options['account_name'])) {
                $ret = [
                    ['label' => __('Username'), 'value' => $this->_options['account_name']],
                    ['label' => 'Total Subscribers', 'value' => $this->_options['total_subscribers']],
                    ['label' => 'Ecommerce Data uploaded to MailChimp', 'value' => '']
                ];
                if (isset($this->_options['store_exists']) && $this->_options['store_exists']) {
                    $ret = array_merge($ret, [
                        ['label' => '  Total Customers', 'value' => $this->_options['total_customers']],
                        ['label' => '  Total Products', 'value' => $this->_options['total_products']],
                        ['label' => '  Total Orders', 'value' => $this->_options['total_orders']],
                        ['label' => '  Total Carts', 'value' => $this->_options['total_carts']]
                    ]);
                    if (!$this->_options['is_syncing']) {
                        $ret = array_merge($ret, [
                           ['label'=> __('This account is currently syncing'), 'value'=>'']
                        ]);
                    } else {
                        $ret = array_merge($ret, [
                            ['label'=> __('Synced since'), 'value'=>$this->_options['is_syncing']]
                        ]);
                    }
                } else {
                    $ret = array_merge($ret, [
                         ['label'=>'This MailChimp account is not connected to Magento.','value'=>'']
                        ]);
                }
            }
        } elseif (!$this->_options) {
            $ret = [
                ['label' => 'Error', 'value' => __('--- Invalid API Key ---')]
            ];
        } else {
            $ret = [['label' => 'Important', 'value' => __($this->_options)]];
        }
        return $ret;
    }
}
