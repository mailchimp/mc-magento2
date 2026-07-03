<?php
/**
 * Ebizmarts_MailChimp
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Test\Unit\Block\Pixel;

use Ebizmarts\MailChimp\Block\Pixel\Identity;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\TestCase;

class IdentityTest extends TestCase
{
    private function makeBlock(bool $isLoggedIn, string $email = ''): Identity
    {
        // Use DataObject to avoid mocking magic getEmail() on Customer model
        $customer = new DataObject(['email' => $email]);

        $customerSession = $this->createMock(CustomerSession::class);
        $customerSession->method('isLoggedIn')->willReturn($isLoggedIn);
        $customerSession->method('getCustomer')->willReturn($customer);

        $context = $this->getMockBuilder(Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper = $this->createMock(MailChimpHelper::class);

        return new Identity($context, $helper, $customerSession);
    }

    public function testGetIdentityDataReturnsEmailForLoggedInCustomer(): void
    {
        $block  = $this->makeBlock(true, 'customer@example.com');
        $result = $block->getIdentityData();

        $this->assertNotNull($result);
        $this->assertSame('customer@example.com', $result['email']);
    }

    public function testGetIdentityDataReturnsNullForGuest(): void
    {
        $this->assertNull($this->makeBlock(false)->getIdentityData());
    }

    public function testGetIdentityDataReturnsNullWhenEmailIsEmpty(): void
    {
        $this->assertNull($this->makeBlock(true, '')->getIdentityData());
    }

    public function testGetIdentityDataEmailIsString(): void
    {
        $result = $this->makeBlock(true, 'user@example.com')->getIdentityData();

        $this->assertIsString($result['email']);
    }
}
