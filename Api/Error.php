<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/27/16 4:45 PM
 * @file: Error.php
 */
class Mailchimp_Error extends Exception
{
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var string
     */
    protected $detail;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var array
     */
    protected $errors;
    /**
     * @var string
     */
    protected $params;

    public function __construct($url,$method='',$params='',$title='',$detail='',$errors=null)
    {
        $titleComplete = $title . " for Api Call: " . $url;
        parent::__construct($titleComplete . " - " . $detail);
        $this->url = $url;
        $this->title = $title;
        $this->detail = $detail;
        $this->method = $method;
        $this->errors = $errors;
        $this->params = $params;
    }
    public function getFriendlyMessage()
    {
        $friendlyMessage = $this->title . " for Api Call: [" . $this->url. "] using method [".$this->method."]\n";
        $friendlyMessage .= "\tDetail: [".$this->detail."]\n";
        if(is_array($this->errors)) {
            $errorMessage = '';
            foreach ($this->errors as $error) {
                $field = array_key_exists('field', $error) ? $error['field'] : '';
                $message = array_key_exists('message', $error) ? $error['message'] : '';
                $line = "\t\t field [$field] : $message\n";
                $errorMessage .= $line;
            }
            $friendlyMessage .= "\tErrors:\n".$errorMessage;
        }
        $lineParams = "\tParams:\n";
        if(is_array($this->params)) {
            if(count($this->params)) {
                $lineParams .= "\t\t" . json_encode($this->params);
            } else {
                $lineParams = "";
            }
        } else {
            $lineParams = $this->params;
        }
        $friendlyMessage .= $lineParams;
        return $friendlyMessage;
    }
    public function getUrl()
    {
        return $this->url;
    }
    public function getTitle()
    {
        return$this->title;
    }
    public function getDetail()
    {
        return $this->detail;
    }
    public function getMethod()
    {
        return $this->method;
    }
    public function getErrors()
    {
        return $this->errors;
    }
}
