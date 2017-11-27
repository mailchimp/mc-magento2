<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/23/17 3:39 PM
 * @file: AccountManage.php
 */

namespace Ebizmarts\MailChimp\Controller;

abstract class AccountManage extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;

    /**
     * AccountManage constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->_helper          = $helper;
    }


    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (!$this->_customerSession->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

}