<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/27/16 4:36 PM
 * @file: Mailchimp.php
 */

require_once './Abstract.php';
require_once './Root.php';
require_once './Automation.php';
require_once './AutomationEmails.php';
require_once './AutomationEmailsQueue.php';
require_once './AutomationRemovedSubscribers.php';
require_once './Error.php';
require_once './AuthorizedApps.php';
require_once './BatchOperations.php';
require_once './CampaignFolders.php';
require_once './Campaigns.php';
require_once './CampaignsContent.php';
require_once './CampaignsFeedback.php';
require_once './CampaignsSendChecklist.php';
require_once './Conversations.php';
require_once './ConversationsMessages.php';
require_once './Ecommerce.php';
require_once './EcommerceStores.php';
require_once './EcommerceCarts.php';
require_once './EcommerceCustomers.php';
require_once './EcommerceOrders.php';
require_once './EcommerceOrdersLines.php';
require_once './EcommerceProducts.php';
require_once './EcommerceProductsVariants.php';
require_once './EcommercePromoRules.php';
require_once './EcommercePromoCodes.php';
require_once './FileManagerFiles.php';
require_once './FileManagerFolders.php';
require_once './Lists.php';
require_once './ListsAbuseReports.php';
require_once './ListsActivity.php';
require_once './ListsClients.php';
require_once './ListsGrowthHistory.php';
require_once './ListsInterestCategory.php';
require_once './ListsInterestCategoryInterests.php';
require_once './ListsMembers.php';
require_once './ListsMembersActivity.php';
require_once './ListsMembersGoals.php';
require_once './ListsMembersNotes.php';
require_once './ListsMergeFields.php';
require_once './ListsSegments.php';
require_once './ListsSegmentsMembers.php';
require_once './ListsWebhooks.php';
require_once './Reports.php';
require_once './ReportsCampaignAdvice.php';
require_once './ReportsClickReports.php';
require_once './ReportsClickReportsMembers.php';
require_once './ReportsDomainPerformance.php';
require_once './ReportsEapURLReport.php';
require_once './ReportsEmailActivity.php';
require_once './ReportsLocation.php';
require_once './ReportsSentTo.php';
require_once './ReportsSubReports.php';
require_once './ReportsUnsubscribes.php';
require_once './TemplateFolders.php';
require_once './Templates.php';
require_once './TemplatesDefaultContent.php';

class Mailchimp
{
    protected $_apiKey;
    protected $_ch = null;
    protected $_root    = 'https://api.mailchimp.com/3.0';
    protected $_debug   = false;

    const POST      = 'POST';
    const GET       = 'GET';
    const PATCH     = 'PATCH';
    const DELETE    = 'DELETE';
    const PUT       = 'PUT';

    const SUBSCRIBED = 'subscribed';
    const UNSUBSCRIBED = 'unsubscribed';

    /**
     * Mailchimp constructor.
     * @param string $apiKey
     * @param array $opts
     * @param string $userAgent
     */
    public function __construct()
    {

        $this->_ch = curl_init();

        if (isset($opts['CURLOPT_FOLLOWLOCATION']) && $opts['CURLOPT_FOLLOWLOCATION'] === true) {
            curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($this->_ch, CURLOPT_USERAGENT, 'Ebizmart-MailChimp-PHP/3.0.0');
        curl_setopt($this->_ch, CURLOPT_HEADER, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, 10);

        $this->root                                         = new Mailchimp_Root($this);
        $this->authorizedApps                               = new Mailchimp_AuthorizedApps($this);
        $this->automation                                   = new Mailchimp_Automation($this);
        $this->automation->emails                           = new Mailchimp_AutomationEmails($this);
        $this->automation->emails->queue                    = new Mailchimp_AutomationEmailsQueue($this);
        $this->automation->removedSubscribers               = new Mailchimp_AutomationRemovedSubscribers($this);
        $this->batchOperation                               = new Mailchimp_BatchOperations($this);
        $this->campaignFolders                              = new Mailchimp_CampaignFolders($this);
        $this->campaigns                                    = new Mailchimp_Campaigns($this);
        $this->campaigns->content                           = new Mailchimp_CampaignsContent($this);
        $this->campaigns->feedback                          = new Mailchimp_CampaignsFeedback($this);
        $this->campaigns->sendChecklist                     = new Mailchimp_CampaignsSendChecklist($this);
        $this->conversations                                = new Mailchimp_Conversations($this);
        $this->conversations->messages                      = new Mailchimp_ConversationsMessages($this);
        $this->ecommerce                                    = new Mailchimp_Ecommerce($this);
        $this->ecommerce->stores                            = new Mailchimp_EcommerceStores($this);
        $this->ecommerce->carts                             = new Mailchimp_EcommerceCarts($this);
        $this->ecommerce->customers                         = new Mailchimp_EcommerceCustomers($this);
        $this->ecommerce->orders                            = new Mailchimp_EcommerceOrders($this);
        $this->ecommerce->orders->lines                     = new Mailchimp_EcommerceOrdersLines($this);
        $this->ecommerce->products                          = new Mailchimp_EcommerceProducts($this);
        $this->ecommerce->products->variants                = new Mailchimp_EcommerceProductsVariants($this);
        $this->ecommerce->promoRules                        = new Mailchimp_EcommercePromoRules($this);
        $this->ecommerce->promoCodes                        = new Mailchimp_EcommercePromoCodes($this);
        $this->fileManagerFiles                             = new Mailchimp_FileManagerFiles($this);
        $this->fileManagerFolders                           = new Mailchimp_FileManagerFolders($this);
        $this->lists                                        = new Mailchimp_Lists($this);
        $this->lists->abuseReports                          = new Mailchimp_ListsAbuseReports($this);
        $this->lists->activity                              = new Mailchimp_ListsActivity($this);
        $this->lists->clients                               = new Mailchimp_ListsClients($this);
        $this->lists->growthHistory                         = new Mailchimp_ListsGrowthHistory($this);
        $this->lists->interestCategory                      = new Mailchimp_ListsInterestCategory($this);
        $this->lists->interestCategory->interests           = new Mailchimp_ListsInterestCategoryInterests($this);
        $this->lists->members                               = new Mailchimp_ListsMembers($this);
        $this->lists->members->memberActivity               = new Mailchimp_ListsMembersActivity($this);
        $this->lists->members->memberGoal                   = new Mailchimp_ListsMembersGoals($this);
        $this->lists->members->memberNotes                  = new Mailchimp_ListsMembersNotes($this);;
        $this->lists->mergeFields                           = new Mailchimp_ListsMergeFields($this);
        $this->lists->segments                              = new Mailchimp_ListsSegments($this);
        $this->lists->segments->segmentMembers              = new Mailchimp_ListsSegmentsMembers($this);
        $this->lists->webhooks                              = new Mailchimp_ListsWebhooks($this);
        $this->reports                                      = new Mailchimp_Reports($this);
        $this->reports->campaignAdvice                      = new Mailchimp_ReportsCampaignAdvice($this);
        $this->reports->clickReports                        = new Mailchimp_ReportsClickReports($this);
        $this->reports->clickReports->clickReportMembers    = new Mailchimp_ReportsClickReportsMembers($this);
        $this->reports->domainPerformance                   = new Mailchimp_ReportsDomainPerformance($this);
        $this->reports->eapURLReport                        = new Mailchimp_ReportsEapURLReport($this);
        $this->reports->emailActivity                       = new Mailchimp_ReportsEmailActivity($this);
        $this->reports->location                            = new Mailchimp_ReportsLocation($this);
        $this->reports->sentTo                              = new Mailchimp_ReportsSentTo($this);
        $this->reports->subReports                          = new Mailchimp_ReportsSubReports($this);
        $this->reports->unsubscribes                        = new Mailchimp_ReportsUnsubscribes($this);
        $this->templateFolders                              = new Mailchimp_TemplateFolders($this);
        $this->templates                                    = new Mailchimp_Templates($this);
        $this->templates->defaultContent                    = new Mailchimp_TemplatesDefaultContent($this);
    }
    public function setApiKey($apiKey)
    {
        $this->_root    = 'https://api.mailchimp.com/3.0';
        if (!$this->_ch) {
            $this->init();
        }
        $this->_apiKey   = $apiKey;
        $dc             = 'us1';
        if (strstr($this->_apiKey, "-")){
            list($key, $dc) = explode("-", $this->_apiKey, 2);
            if (!$dc) {
                $dc = "us1";
            }
        }
        $this->_root = str_replace('https://api', 'https://' . $dc . '.api', $this->_root);
        $this->_root = rtrim($this->_root, '/') . '/';
        curl_setopt($this->_ch, CURLOPT_USERPWD, "noname:" . $this->_apiKey);
    }

    /**
     * @return string
     */
    public function getAdminUrl()
    {
        $url = str_replace('.api', '.admin', $this->_root);
        $url = rtrim(str_replace('/3.0', '', $url), '/') . '/';

        return $url;
    }

    public function setUserAgent($userAgent)
    {
        if (!$this->_ch) {
            $this->init();
        }
        curl_setopt($this->_ch, CURLOPT_USERAGENT, $userAgent);
    }
    public function call($url,$params,$method=Mailchimp::GET)
    {
        $hasParams = true;
        if(is_array($params)&&count($params)==0||$params == null)
        {
            $hasParams = false;
        }
        if($hasParams&&$method!=Mailchimp::GET)
        {
            $params = json_encode($params);
        }

        $ch = $this->_ch;
        if($hasParams&&$method!=Mailchimp::GET)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, null);
            if ($hasParams) {
                $_params = http_build_query($params);
                $url .= '?' . $_params;
            }
        }
        curl_setopt($ch, CURLOPT_URL, $this->_root . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_VERBOSE, $this->_debug);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$method);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);


        $response_body = curl_exec($ch);

        $info = curl_getinfo($ch);
        if(curl_error($ch)) {
            throw new Mailchimp_HttpError($url, $method, $params, '', curl_error($ch));
        }
        $result = json_decode($response_body, true);

        if(floor($info['http_code'] / 100) >= 4) {
            if(is_array($result)) {
                $detail = array_key_exists('detail', $result) ? $result['detail'] : '';
                $errors = array_key_exists('errors', $result) ? $result['errors'] : null;
                $title = array_key_exists('title', $result) ? $result['title'] : '';
                throw new Mailchimp_Error($this->_root . $url, $method, $params, $title, $detail, $errors);
            } else {
                throw new Mailchimp_Error($this->_root . $url, $method, $params, $result);
            }
        }

        return $result;
    }
}
