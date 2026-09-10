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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Config\Backend;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Ebizmarts\MailChimp\Model\Config\Backend\MonkeyStore;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

/**
 * beforeSave() is the only thing standing between the admin form and the two
 * values the dropdown offers that are not stores, and it is also where a failed
 * lookup used to overwrite a working audience id with nothing.
 */
class MonkeyStoreBeforeSaveTest extends TestCase
{
    /** @var array  every saveConfigValue() call, path => value */
    private $saved = [];

    /** @var int */
    private $webhooksCreated = 0;

    /**
     * The backend model with its collaborators injected by hand. Value's own
     * constructor wants half the framework and none of it is under test.
     *
     * @param  bool  $valueChanged
     * @param  mixed $listLookupAnswer  audience id, or a throwable
     * @return MonkeyStore
     */
    private function backend($valueChanged, $listLookupAnswer = 'list-new', $inheritedActive = null)
    {
        $this->saved = [];
        $this->webhooksCreated = 0;

        $stores = new class($listLookupAnswer) {
            private $answer;
            public function __construct($answer)
            {
                $this->answer = $answer;
            }
            public function get($id)
            {
                if ($this->answer instanceof \Throwable) {
                    throw $this->answer;
                }

                return ['list_id' => $this->answer];
            }
        };
        $ecommerce = new \stdClass();
        $ecommerce->stores = $stores;
        $api = new \stdClass();
        $api->ecommerce = $ecommerce;

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getApiKey')->willReturn('key-us1');
        $helper->method('getApiByApiKey')->willReturn($api);
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path) use ($inheritedActive) {
                if ($path === MailChimpHelper::XML_PATH_ECOMMERCE_ACTIVE) {
                    return $inheritedActive;
                }

                return 'list-existing';
            }
        );
        $helper->method('saveConfigValue')->willReturnCallback(function ($path, $value) {
            $this->saved[$path] = $value;
        });
        $helper->method('createWebHook')->willReturnCallback(function () {
            $this->webhooksCreated++;
        });

        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn([]);

        $backend = new class($valueChanged) extends MonkeyStore {
            private $changed;
            public function __construct($changed)
            {
                $this->changed = $changed;
            }
            public function isValueChanged()
            {
                return $this->changed;
            }
            public function getOldValue()
            {
                return 'store-old';
            }
            public function getScopeId()
            {
                return 0;
            }
            public function getScope()
            {
                return 'default';
            }
        };

        $this->inject($backend, MonkeyStore::class, '_helper', $helper);
        $this->inject($backend, MonkeyStore::class, 'syncHelper', $this->createMock(SyncHelper::class));
        $this->inject($backend, MonkeyStore::class, '_storeManager', $storeManager);
        $this->inject(
            $backend,
            \Magento\Framework\Model\AbstractModel::class,
            '_eventManager',
            $this->createMock(\Magento\Framework\Event\ManagerInterface::class)
        );

        return $backend;
    }

    private function inject($object, $class, $property, $value)
    {
        $p = new \ReflectionProperty($class, $property);
        $p->setAccessible(true);
        $p->setValue($object, $value);
    }

    /**
     * @param  MonkeyStore $backend
     * @param  mixed       $value
     * @param  int         $active
     * @return void
     */
    private function save(MonkeyStore $backend, $value, $active = 1)
    {
        $backend->setData('groups', ['ecommerce' => ['fields' => ['active' => ['value' => $active]]]]);
        $backend->setValue($value);
        $backend->beforeSave();
    }

    /**
     * "Use Default" posts inherit => 1 and no value, whatever the inherited
     * setting is. Reading that flag as the answer made every inheriting scope
     * look active -- and a scope inheriting ecommerce OFF would then be told to
     * turn ecommerce off, with no field at that scope it could change to comply.
     */
    public function testAScopeInheritingEcommerceOffAcceptsTheNonStore()
    {
        $backend = $this->backend(true, 'list-new', 0);
        $backend->setData('groups', ['ecommerce' => ['fields' => ['active' => ['inherit' => 1]]]]);
        $backend->setValue('-1');

        $backend->beforeSave();

        $this->assertSame([], $this->saved);
    }

    public function testAScopeInheritingEcommerceOnStillRejectsIt()
    {
        $this->expectException(LocalizedException::class);

        $backend = $this->backend(true, 'list-new', 1);
        $backend->setData('groups', ['ecommerce' => ['fields' => ['active' => ['inherit' => 1]]]]);
        $backend->setValue('-1');

        $backend->beforeSave();
    }

    /**
     * -1 is the dropdown's placeholder and 0 is the '---No Data---' option it
     * shows when there is no key at this scope or the listing threw. Neither is
     * a store, and persisting one leaves a setting every later reader has to
     * special-case.
     *
     * @return array
     */
    public static function nonStoreProvider()
    {
        return [
            'the placeholder'            => [-1],
            'the placeholder as a string' => ['-1'],
            'the ---No Data--- option'   => [0],
            'that option as a string'    => ['0'],
            'nothing at all'             => [''],
        ];
    }

    /**
     * @dataProvider nonStoreProvider
     * @param mixed $value
     */
    public function testAValueThatIsNotAStoreIsRejectedWhileEcommerceIsActive($value)
    {
        $this->expectException(LocalizedException::class);

        $this->save($this->backend(true), $value);
    }

    /**
     * The scoping that matters: with ecommerce off, "no store selected" is a
     * legitimate state. Rejecting it would leave a merchant unable to save any
     * Mailchimp configuration -- including the switch that turns ecommerce off.
     *
     * @dataProvider nonStoreProvider
     * @param mixed $value
     */
    public function testTheSameValueIsAcceptedWhileEcommerceIsOff($value)
    {
        $this->save($this->backend(true), $value, 0);

        $this->assertSame([], $this->saved);
    }

    public function testARealStoreIsNotRejected()
    {
        $this->save($this->backend(false), 'abc123def456');

        $this->assertSame([], $this->saved);
    }

    /**
     * The second defect: getStore() answers null when its own lookup fails, and
     * that null was written over whatever audience id was already configured.
     * A failed lookup destroyed the answer it could not confirm.
     */
    public function testAFailedLookupDoesNotOverwriteTheConfiguredAudience()
    {
        $backend = $this->backend(true, new \Mailchimp_Error('/', 'GET', '', 'Not Found', 'nope'));

        $this->save($backend, 'abc123def456');

        $this->assertArrayNotHasKey(MailChimpHelper::XML_PATH_LIST, $this->saved);
    }

    /**
     * And one consequence further on: that same null went on to register a
     * webhook against no audience at all.
     */
    public function testAFailedLookupRegistersNoWebhook()
    {
        $backend = $this->backend(true, new \Mailchimp_Error('/', 'GET', '', 'Not Found', 'nope'));

        $this->save($backend, 'abc123def456');

        $this->assertSame(0, $this->webhooksCreated);
    }

    /**
     * A lookup that succeeds still writes, so the guard did not simply disable
     * the feature.
     */
    public function testASuccessfulLookupStillWritesTheAudience()
    {
        $backend = $this->backend(true, 'list-new');

        $this->save($backend, 'abc123def456');

        $this->assertSame('list-new', $this->saved[MailChimpHelper::XML_PATH_LIST]);
        $this->assertSame(1, $this->webhooksCreated);
    }
}
