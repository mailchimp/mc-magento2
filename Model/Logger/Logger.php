<?php

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
