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
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
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

        // getSearchData() reads the result count off the current search query.
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->addMethods(['getNumResults'])
            ->getMock();
        $query->method('getNumResults')->willReturn(7);

        $queryFactory = $this->getMockBuilder(QueryFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $queryFactory->method('get')->willReturn($query);

        return new Search($context, $helper, $queryFactory);
    }

    public function testGetSearchDataReturnsQueryWhenPresent(): void
    {
        $block = $this->makeBlock('red shoes');

        // The block also reports the result count (added with QueryFactory);
        // the stubbed query returns 7.
        $this->assertSame(['query' => 'red shoes', 'resultsCount' => 7], $block->getSearchData());
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
