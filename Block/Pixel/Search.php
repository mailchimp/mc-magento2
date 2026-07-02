<?php
/**
 * Ebizmarts_MailChimp Magento Component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Block\Pixel;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\View\Element\Template;
use Magento\Search\Model\QueryFactory;

/**
 * Exposes search query data as JSON for the Mailchimp Pixel JS module.
 * Rendered on catalogsearch_result_index pages.
 */
class Search extends Template
{
    /**
     * @var MailChimpHelper
     */
    protected $helper;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @param Template\Context $context
     * @param MailChimpHelper  $helper
     * @param QueryFactory     $queryFactory
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        MailChimpHelper $helper,
        QueryFactory $queryFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper       = $helper;
        $this->queryFactory = $queryFactory;
    }

    /**
     * Returns search data for SEARCH_SUBMITTED payload.
     *
     * @return array
     */
    public function getSearchData(): array
    {
        $queryText = (string)$this->getRequest()->getParam('q', '');
        if ($queryText === '') {
            return [];
        }

        $resultsCount = (int)$this->queryFactory->get()->getNumResults();

        return [
            'query'        => $queryText,
            'resultsCount' => $resultsCount,
        ];
    }

    /**
     * Only render when pixel is enabled and a search query exists.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();
        if (!$this->helper->isPixelEnabled($storeId)) {
            return '';
        }
        if (empty($this->getSearchData())) {
            return '';
        }
        return parent::_toHtml();
    }
}
