<?php
/**
 * Ebizmarts_MailChimp Magento JS component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Model\Logger;

class Logger extends \Monolog\Logger
{

    public function mailchimpLog($message, $file)
    {
        if ($file) {
            $fileName = BP. DIRECTORY_SEPARATOR .'var'. DIRECTORY_SEPARATOR.'log'.
                DIRECTORY_SEPARATOR.$file.'_Request.log';
            $this->pushHandler(new \Monolog\Handler\StreamHandler($fileName));
        }

        try {
            if ($message===null) {
                $message = "NULL";
            }
            if (is_array($message)) {
                $message = json_encode($message, JSON_PRETTY_PRINT);
            }
            if (is_object($message)) {
                $message = json_encode($message, JSON_PRETTY_PRINT);
            }
            if (!empty(json_last_error())) {
                $message = (string)json_last_error();
            }
            $message = (string)$message;
        } catch (\Exception $e) {
            $message = "INVALID MESSAGE";
        }
        $message .= "\r\n";
        $this->info($message);
        if ($file) {
            $this->popHandler();
        }
    }
}
