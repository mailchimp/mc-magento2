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

use Ebizmarts\MailChimp\Block\Pixel\Search;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase
{
    private function makeBlock(string $queryParam): Search
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')
            ->with('q', '')
            ->willReturn($queryParam);

        $context = $this->getMockBuilder(Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->method('getRequest')->willReturn($request);

        $helper = $this->createMock(MailChimpHelper::class);

        return new Search($context, $helper);
    }

    public function testGetSearchDataReturnsQueryWhenPresent(): void
    {
        $block = $this->makeBlock('red shoes');

        $this->assertSame(['query' => 'red shoes'], $block->getSearchData());
    }

    public function testGetSearchDataReturnsEmptyArrayWhenNoQuery(): void
    {
        $block = $this->makeBlock('');

        $this->assertSame([], $block->getSearchData());
    }

    public function testGetSearchDataPreservesQueryAsString(): void
    {
        $block = $this->makeBlock('boots & shoes');

        $result = $block->getSearchData();

        $this->assertSame('boots & shoes', $result['query']);
    }
}
