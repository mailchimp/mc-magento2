<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:48 PM
 * @file: Reports.php
 */
class Mailchimp_Reports extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_ReportsCampaignAdvice
     */
    public $campaignAdvice;
    /**
     * @var Mailchimp_ReportsClickReports
     */
    public $clickReports;
    /**
     * @var Mailchimp_ReportsDomainPerformance
     */
    public $domainPerformance;
    /**
     * @var ReportsEapURLReport
     */
    public $eapURLReport;
    /**
     * @var Mailchimp_ReportsEmailActivity
     */
    public $emailActivity;
    /**
     * @var ReportsLocation
     */
    public $location;
    /**
     * @var Mailchimp_ReportsSentTo
     */
    public $sentTo;
    /**
     * @var Mailchimp_ReportsSubReports
     */
    public $subReports;
    /**
     * @var Mailchimp_ReportsUnsubscribes
     */
    public $unsubscribes;
}