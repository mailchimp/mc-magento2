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

namespace Ebizmarts\MailChimp\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Removes orphaned bare-scalar mailchimp/general/issync rows from
 * core_config_data that coexist with per-store issync/<mailchimpStoreId>
 * child rows at the same scope.
 *
 * When both shapes exist at the same scope Magento's Scope\Converter sets
 * issync = "<date>" (string) and then tries issync['<id>'] = … resulting in
 * "Cannot access offset of type string on string" — a site-wide fatal on
 * every request.
 *
 * Safe to run on clean installs (DELETE WHERE … is a no-op when no orphans
 * exist). Idempotent.
 */
class CleanIssyncOrphanRows implements DataPatchInterface
{
    private const ISSYNC_PATH = 'mailchimp/general/issync';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $connection = $this->resourceConnection->getConnection();
        $table      = $this->resourceConnection->getTableName('core_config_data');

        // Find every (scope, scope_id) pair that has BOTH the bare scalar path
        // AND at least one per-store child path.  Only delete the bare scalar
        // from those pairs — leave clean installs untouched.
        $childSelect = $connection->select()
            ->from(['child' => $table], [new \Zend_Db_Expr('1')])
            ->where('child.scope    = main.scope')
            ->where('child.scope_id = main.scope_id')
            ->where('child.path LIKE ?', self::ISSYNC_PATH . '/%');

        $deleteSelect = $connection->select()
            ->from(['main' => $table], ['config_id'])
            ->where('main.path = ?', self::ISSYNC_PATH)
            ->where('EXISTS (' . $childSelect . ')');

        $connection->query(
            'DELETE FROM ' . $table . ' WHERE config_id IN (' . $deleteSelect . ')'
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
