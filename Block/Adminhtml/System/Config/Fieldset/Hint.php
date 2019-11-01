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

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config\Fieldset;

class Hint extends \Magento\Backend\Block\Template implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'Ebizmarts_MailChimp::system/config/fieldset/hint.phtml';
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $_metaData;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var \Magento\Backend\Block\Template\Context
     */
    private $_context;
    /**
     * @var \Ebizmarts\MailChimp\Model\Config\ModuleVersion
     */
    private $_moduleVersion;

    /**
     * Hint constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaData
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\Config\ModuleVersion $moduleVersion
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\Config\ModuleVersion $moduleVersion,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_metaData = $productMetaData;
        $this->_helper = $helper;
        $this->_moduleVersion   = $moduleVersion;
        $this->_context = $context;
    }
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->toHtml();
    }

    public function getModuleVersion()
    {
        return $this->_moduleVersion->getModuleVersion('Ebizmarts_MailChimp');
    }
    public function getHasApiKey()
    {
        $apikey = $this->_helper->getApiKey($this->_context->getStoreManager()->getStore()->getId());
        if ($apikey) {
            return true;
        } else {
            return false;
        }
    }
}
