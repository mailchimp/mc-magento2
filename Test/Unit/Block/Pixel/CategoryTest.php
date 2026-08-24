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

use Ebizmarts\MailChimp\Block\Pixel\Category;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private function makeBlock(?object $category): Category
    {
        $registry = $this->createMock(Registry::class);
        $registry->method('registry')
            ->with('current_category')
            ->willReturn($category);

        $context = $this->getMockBuilder(Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper = $this->createMock(MailChimpHelper::class);

        return new Category($context, $helper, $registry);
    }

    private function makeCategory(string $id, string $name, string $url): object
    {
        $category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $category->method('getId')->willReturn($id);
        $category->method('getName')->willReturn($name);
        $category->method('getUrl')->willReturn($url);

        return $category;
    }

    public function testGetCategoryDataReturnsCorrectStructure(): void
    {
        $category = $this->makeCategory('5', 'Women', 'https://example.com/women');
        $block    = $this->makeBlock($category);

        $result = $block->getCategoryData();

        // The payload keys are categoryId/categoryName — that is what
        // view/frontend/web/js/pixel/category.js reads. There is no url field.
        $this->assertSame('5', $result['categoryId']);
        $this->assertSame('Women', $result['categoryName']);
        $this->assertArrayNotHasKey('url', $result);
    }

    public function testGetCategoryDataReturnsEmptyArrayWhenNoCategory(): void
    {
        $block = $this->makeBlock(null);

        $this->assertSame([], $block->getCategoryData());
    }

    public function testGetCategoryDataCastsFieldsToString(): void
    {
        $category = $this->makeCategory(12, 'Sale', 'https://example.com/sale');
        $block    = $this->makeBlock($category);

        $result = $block->getCategoryData();

        $this->assertIsString($result['categoryId']);
        $this->assertIsString($result['categoryName']);
    }
}
