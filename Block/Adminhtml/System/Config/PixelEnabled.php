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

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Locks the Pixel toggle until the store view is connected to a Mailchimp store.
 *
 * The Pixel is turned on when a store view connects, and a merchant's explicit
 * No is never overridden. Between those two rules sits a store view that is not
 * connected yet: the toggle renders there, so a No could be given before there
 * is anything to attach it to, and the connection would then turn the Pixel on
 * over it.
 *
 * Disabling the control closes that at the form rather than working around it
 * afterwards. A disabled control is not submitted, so a Save Config before the
 * connection writes nothing for this field -- there is no early No to lose, and
 * no row for the backend model to read as a decision. The same holds for the
 * save that performs the connection: the field was rendered disabled when that
 * page was drawn, so it is not posted, and the Pixel is enabled by the
 * connection hook.
 *
 * `setDisabled(true)` is enough on its own: `disabled` is among the attributes
 * a select renders (`framework/Data/Form/Element/Select.php`), and the only
 * admin script that re-enables a config field is the one bound to the inherit
 * checkbox, which this field does not render.
 */
class PixelEnabled extends Field
{
    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @param Context         $context
     * @param MailChimpHelper $helper
     * @param array           $data
     */
    public function __construct(
        Context $context,
        MailChimpHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $storeId = $this->resolveStoreId();

        // A null store id is not passed to the helper on purpose: with no store
        // it would resolve the default scope, and a connected default scope
        // would unlock a field that is being rendered somewhere it should not
        // be. Locked is the safe answer to "I do not know which store view".
        if ($storeId === null || !$this->helper->isStoreConnected($storeId)) {
            $element->setDisabled(true);
            $element->setComment(
                (string)__(
                    'Available once this store view is connected to a Mailchimp store. '
                    . 'The Pixel is then enabled automatically, and you can turn it off here.'
                )
            );
        }

        return parent::render($element);
    }

    /**
     * The store view this field is being rendered for.
     *
     * The group is store-view only, so the config page always carries the
     * `store` parameter when this renders. A missing one means the field is
     * somewhere it was not meant to be, and treating that as "not connected"
     * leaves it locked rather than writable by accident.
     *
     * @return int|null
     */
    private function resolveStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');

        return $storeId === null || $storeId === '' ? null : (int)$storeId;
    }
}
