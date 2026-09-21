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

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

class OauthWizard extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template    = 'system/config/oauth_wizard.phtml';

    /**
     * The only address this extension knows about for connecting an account.
     *
     * A constant rather than configuration on purpose: a merchant has no use
     * for changing it, and a setting would be one more thing that can be wrong
     * on an installation nobody can see.
     */
    const CONNECT_URL = 'https://apps.ebizmarts.com/mc4magento/v1/mc-magento2/connect/go';

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $label = $originalData['button_label'];

        $this->addData([
            'button_label' => __($label),
            'button_url'   => $this->authorizeRequestUrl(),
            'html_id' => $element->getHtmlId(),
        ]);
        return parent::_toHtml();
        ;
    }
    /**
     * Where the connect button sends the merchant.
     *
     * One ebizmarts URL, and after this the whole of the extension's coupling
     * to the connect flow. The relay behind it mints the `state`, holds the
     * client id and the secret, builds the Mailchimp authorize URL and
     * exchanges the code. Nothing is sent from here and nothing comes back:
     * the merchant copies the key off the page exactly as before, which is the
     * only way an installed extension can receive one.
     *
     * The authorize endpoint, the token endpoint, the redirect URI and the
     * client id used to live above this method and are deliberately gone
     * rather than left unused. While any of them sits in a released extension,
     * changing one means another release -- and a release reaches a fraction
     * of the installed base over months. Behind this URL the same change is a
     * deploy, which is also what makes a `state` possible at all: the flow can
     * now begin somewhere we control rather than at Mailchimp.
     *
     * @return string
     */
    public function authorizeRequestUrl()
    {
        return self::CONNECT_URL;
    }
}
