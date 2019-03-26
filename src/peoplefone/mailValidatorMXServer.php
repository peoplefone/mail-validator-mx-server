<?php

namespace peoplefone;

class mailValidatorMXServer {
    
    /**
     * @var bool
     */
    private $debug = false;
    
    /**
     * @var string
     */
    private $from_host;
    
    /**
     * @var string
     */
    private $from_user;
    
    /**
     * @var array
     */
    private $user_list = array();
    
    /**
     * @var bool
     */
    private $show_text = true;
    
    /**
     * @var string
     */
    private $sock;
    
    /**
     * @var int
     */
    private $sock_port = 25;
    
    /**
     * @var int
     */
    private $sock_timeout = 30;
    
    /**
     * @var int
     */
    private $stream_timeout = 15;
    
    
    /**
     * https://tools.ietf.org/html/rfc5321
     * @var array
     */
    private $reply_codes = array(
        "211" => "System status, or system help reply",
        "214" => "Help message (Information on how to use the receiver or the meaning of a particular non-standard command; this reply is useful only to the human user)",
        "220" => "<domain> Service ready",
        "221" => "<domain> Service closing transmission channel",
        "250" => "Requested mail action okay, completed",
        "251" => "User not local; will forward to <forward-path> (See Section 3.4)",
        "252" => "Cannot VRFY user, but will accept message and attempt delivery (See Section 3.5.3)",
        "354" => "Start mail input; end with <CRLF>.<CRLF>",
        "421" => "<domain> Service not available, closing transmission channel (This may be a reply to any command if the service knows it must shut down)",
        "450" => "Requested mail action not taken: mailbox unavailable (e.g., mailbox busy or temporarily blocked for policy reasons)",
        "451" => "Requested action aborted: local error in processing",
        "452" => "Requested action not taken: insufficient system storage",
        "455" => "Server unable to accommodate parameters",
        "500" => "Syntax error, command unrecognized (This may include errors such as command line too long)",
        "501" => "Syntax error in parameters or arguments",
        "502" => "Command not implemented (see Section 4.2.4)",
        "503" => "Bad sequence of commands",
        "504" => "Command parameter not implemented",
        "550" => "Requested action not taken: mailbox unavailable (e.g., mailbox not found, no access, or command rejected for policy reasons)",
        "551" => "User not local; please try <forward-path> (See Section 3.4)",
        "552" => "Requested mail action aborted: exceeded storage allocation",
        "553" => "Requested action not taken: mailbox name not allowed (e.g., mailbox syntax incorrect)",
        "554" => "Transaction failed (Or, in the case of a connection-opening response, 'No SMTP service here')",
        "555" => "MAIL FROM/RCPT TO parameters not recognized or not implemented",
    );
    
    /**
     * @param string $host Domain issuing the request
     * @param string $user Email issuing the request
     * @return void
     */
    public function __construct($host, $user)
    {
        $this->from_host = preg_replace("/[^a-z0-9\-\.]/", "", strtolower($host));
        $this->from_user = preg_replace("/[^a-z0-9\-\.\@]/", "", strtolower($user));
    }
    
    /**
     * @param int $sock_port
     * @return int
     */
    public function setConnectionPort($sock_port=25)
    {
        $this->sock_port = $sock_port;
        return 1;
    }
    
    /**
     * @param int $sock_timeout
     * @return int
     */
    public function setConnectionTimeOut($sock_timeout=30)
    {
        $this->sock_timeout = $sock_timeout;
        return 1;
    }
    
    /**
     * @param int $stream_timeout
     * @return int
     */
    public function setStreamTimeOut($stream_timeout=15)
    {
        $this->stream_timeout = $stream_timeout;
        return 1;
    }
    
    /**
     * @param string $user Email Address
     * @return bool
     */
    public function setContact($user)
    {
        $user = preg_replace("/[^a-z0-9\-\.\@]/", "", strtolower($user));
        
        if(!empty($user) && !in_array($user, $this->user_list))
        {
            $this->user_list[] = $user;
            sort($this->user_list);
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * @return array
     */
    public function getContacts()
    {
        return $this->user_list;
    }
    
    /**
     * @param string $user Email Address
     * @return array
     */
    public function unsetContact($user)
    {
        $user = preg_replace("/[^a-z0-9\-\.\@]/", "", strtolower($user));
        
        unset($this->user_list[array_search($user, $this->user_list)]);
        
        return;
    }
    
    /**
     * @return array
     */
    public function clearContacts()
    {
        $this->user_list = [];
    }
    
    /**
     * @param bool $debug
     * @return array
     */
    public function validate($debug=false)
    {
        $this->debug = $debug;
        
        $return = array();
        
        foreach($this->user_list as $user)
        {
            $obj = (object)array();
            
            $obj->mail = $user;
            $obj->host = $this->getMXDomains($user);
            $obj->code = 0;
            
            $sock = $this->getMXConnection($obj->host);
            
            if($sock)
            {
                $code = $this->readSock($sock);
                
                if($code=="220")
                {
                    $this->sendSock($sock, "HELO ".$this->from_host);
                    $code = $this->readSock($sock);
                    
                    if($code=="250")
                    {
                        $this->sendSock($sock, "MAIL FROM: <".$this->from_user.">");
                        $code = $this->readSock($sock);
                    }
                    
                    if($code=="250")
                    {
                        $this->sendSock($sock, "RCPT TO: <".$user.">");
                        $code = $this->readSock($sock);
                        
                        $obj->code = $code;
                        
                        if($this->show_text)
                        {
                            $obj->text = isset($this->reply_codes[$code]) ? $this->reply_codes[$code] : null;
                        }
                    }
                    
                    $this->sendSock($sock, "quit");
                    $this->readSock($sock);
                }
            }
            
            $return[] = $obj;
        }
        
        return $return;
    }
    
    private function getMXDomains($user)
    {
        $hosts = $lines = $found = array();
        
        $host = (strpos($user,"@")!==false) ? substr($user, strrpos($user,'@')+1) : $user;
        $host = strtolower(preg_replace("/[^a-zA-Z0-9\-\.]/", "", $host));
        
        if(`which nslookup`) {
            exec("nslookup -querytype=mx ".$host, $lines);
        }
        elseif(`which dig`) {
            exec("dig mx ".$host." | grep -v '^;' | grep ".$host, $lines);
        }
        else {
            return;
        }
        
        foreach($lines as $line)
        {
            preg_match('/([0-9]+)\s([a-z0-9\-\.]+)$/i', $line, $found);
            
            if(count($found)==3)
            {
                $hosts[trim($found[2],'.')] = $found[1];
            }
        }
        
        asort($hosts);
        
        return array_keys($hosts);
    }
    
    private function getMXConnection($hosts)
    {
        $errno = $errstr = NULL;
        
        if(is_array($hosts))
        {
            foreach($hosts as $host)
            {
                try
                {
                    $sock = fsockopen($host, $this->sock_port, $errno, $errstr, (float)$this->sock_timeout);
                }
                catch (Exception $e) {
                    print $e->getMessage().PHP_EOL;
                }
                
                if($sock)
                {
                    stream_set_timeout($sock, $this->stream_timeout);
                }
                
                return $sock;
            }
        }
        
        return false;
    }
    
    private function sendSock($sock, $cmd)
    {
        if($this->debug)
        {
            print "===> " . $cmd . PHP_EOL;
        }
        
        return fwrite($sock, $cmd."\r\n");
    }
    
    private function readSock($sock)
    {
        $time = 0;
        while ($line = trim(fgets($sock)))
        {
            if($this->debug)
            {
                print str_pad(++$time, 5, " ") . $line . PHP_EOL;
            }
            
            if(substr($line, 3, 1)==" " || $time>50)
            {
                break;
            }
        }
        
        return substr($line, 0, 3);
    }
}
