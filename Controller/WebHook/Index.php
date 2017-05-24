<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/23/17 3:36 PM
 * @file: Index.php
 */

namespace Ebizmarts\MailChimp\Controller\WebHook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends Action{
    const WEBHOOK__PATH = 'mailchimp/webhook/index';

    public function execute()
    {
        // TODO: Implement execute() method.
    }
}