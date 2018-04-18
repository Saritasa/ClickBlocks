<?php

namespace ClickBlocks\Utils;

use ClickBlocks\Cache\Cache;
use Monolog\Logger;

class Mailer extends \PHPMailer
{
    protected $config;

    public function __construct()
    {
        parent::__construct();
        $config = $this->config = \CB::getInstance()['email'];
        $this->CharSet = $config['charset'] ?: 'utf-8';
        if ($config['isSMTP']) {
            $this->IsSMTP();
            $this->SMTPDebug = $config['smtp_debug'];    // enables SMTP debug information (for testing)
                                                         // 1 = errors and messages
                                                         // 2 = messages only
            $this->SMTPAuth     = (bool)$config['smtp_user']; // enable SMTP authentication
            $this->SMTPSecure   = $config['smtp_secure'];
            $this->Host         = $config['smtp_server'];// sets the SMTP server
            $this->Port         = $config['smtp_port'];  // set the SMTP port for the GMAIL server
            $this->Username     = $config['smtp_user'];  // SMTP account username
            $this->Password     = $config['smtp_pass'];  // SMTP account password
        }

        $this->SetFrom($config['fromEmail'], $config['fromName']);
        
        if ($config['isHTML']) {
            $this->IsHTML();
        }
    }
    
    public function PreSend()
    {
        $config = $this->config;
        if ($config['fromEmailForce']) $this->From = $config['fromEmail'];
        // this is done here because otherwise AddAddress may not add actual "To" recipient 
        // because that email already existed in "CC" or "BCC"
        if (isset($config['emailCC'])) {
            $emailCC = explode(",", $config['emailCC']);
            foreach ($emailCC as $cc) {
                if ($cc) $this->AddCC($cc);
            }
        }
        if (isset($config['emailBCC'])) {
            $emailBCC = explode(",", $config['emailBCC']);
            foreach ($emailBCC as $bcc) {
                if ($bcc) $this->addBCC($bcc);
            }
        }


        
        
        return parent::PreSend();
    }

}