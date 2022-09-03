<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 5:24 PM
 * @file: TemplatesDefaultContent.php
 */

class Mailchimp_TemplatesDefaultContent extends Mailchimp_Abstract
{
    /**
     * @param int $id                   The template id.
     * @param string[] $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param string[] $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws \Mailchimp_Error
     * @throws \Mailchimp_HttpError
     */
    public function get($id, $fields = null, $excludeFields = null)
    {
        $params = array();

        if ($fields) $params['fields'] = $fields;
        if ($excludeFields) $params['exclude_fields'] = $excludeFields;

        return $this->master->call('templates/' . $id . '/default-content', $params, \Mailchimp::GET);
    }
}
