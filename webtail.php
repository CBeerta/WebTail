<?php

/**
* webtail.php 
*
* Tails a Logfile located on a webserver (inspired by http://www.jibble.org/webtail/)
*
* @author Claus Beerta <claus@beerta.de>
*
**/

define("WEBTAIL_VERSION", '$Id$');

class WebTail
{
    /**
    * Where are we right now, to continue the partial request 
    **/
    public $offset = 0;

    /**
    * Url to tail
    **/
    public $url;    
    
    /**
    * interval for updates
    **/
    public $interval;


    /**
    * Initial HEAD request to figure out content length and check general availability of the file
    *
    **/
    public function __construct($url, $interval = 5, $last_bytes = 0)
    {
        $this->url = $url;

        $ch = $this->_init_curl();
        curl_setopt($ch, CURLOPT_NOBODY, True); 
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        print_r($info);  
        if ($info['http_code'] !== 200)
        {
            throw new Exception ("Unable to open {$url}\n");
        }

        if ($info['download_content_length'] - $last_bytes >= 0)
        {
            $this->offset = $info['download_content_length'] - $last_bytes;
        }
        else
        {
            $this->offset = $info['download_content_length'];
        }

        print_r($this);
            
        $this->interval = $interval;
    }

    /**
    * Check via head request if new content has arrived or file got truncated
    *
    **/
    public function has_new_content()
    {
        $ch = $this->_init_curl();
        curl_setopt($ch, CURLOPT_NOBODY, True); 
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] !== 200)
        {
            /** File vanished, so no updates **/
            return false;
        }
        else if ($this->offset > $info['download_content_length'])
        {
            /** file got truncated, store new offset **/
            $this->offset = $info['download_content_length'];
            return false;
        }
        else if ($this->offset < $info['download_content_length'])
        {
            return true;
        }
        return false;
    }
    
    /**
    * Update since last pull, display
    *
    **/
    public function update()
    {
        if ($this->has_new_content() !== false)
        {
            $ch = $this->_init_curl();
            curl_setopt($ch, CURLOPT_RESUME_FROM, $this->offset);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            $res = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            
            if ($info['download_content_length'] > 1)
            {
                $this->offset += $info['download_content_length'];
                return ($res);
            }
        }
        return False;
    }

    /**
    * Some Global Curl options to setup
    *
    **/
    private function _init_curl()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url); 
        curl_setopt($ch, CURLOPT_USERAGENT, WEBTAIL_VERSION);

        /** Ignore SSL Errors **/
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 

        if (preg_match('#^http(s)?://((.*):(.*))@#i', $this->url, $matches))
        {
            curl_setopt($ch, CURLOPT_USERPWD, $matches[2]); 
        }

        return ($ch);
    }


    /**
    * Tail given file
    *
    **/
    public function tail()
    {
        while (true)
        {
            if (($res = $this->update()) !== False)
            {
                print $res;
            }
            sleep($this->interval);    
        }
    }
}

list($first_include) = get_included_files();
if ($first_include == __FILE__)
{
    /** Check if this file got included for the WebTail class, otherwise execute below **/
    
    $interval = 5;
    $last_bytes = 0;
    $urls = array();

    foreach ($argv as $k => $v)
    {
        if ($v == "-i" && is_numeric($argv[$k+1]))
        {
            $interval = $argv[$k+1];
        }
        else if ($v == "--version")
        {
            print "Version: ".WEBTAIL_VERSION."\n";
            exit(0);
        }
        else if (in_array($v, array('-q', '--quiet', '--silent')))
        {
            $no_headers = True;
        }
        else if ($v == '-c' && is_numeric($argv[$k+1]))
        {
            $last_bytes = $argv[$k+1];
        }
        else if (stristr($v, 'http'))
        {
            try
            {
                $wt = new WebTail($v, $interval, $last_bytes);
            }
            catch (Exception $e)
            {
                print "ERROR: {$v} is invalid, ignoring.\n";
                continue;
            }
            $urls[] = $wt ;
            unset ($wt);
        }
    }

    if (empty($urls))
    {
        print <<<EOF
Usage: {$argv[0]} [OPTION]... [FILE]...

Valid options are:
\t\t-i <interval>\t\tinterval for updates
\t\t--version\t\toutput version information and exit
\t\t-q, --quiet, --silent\tnever output headers giving file names
\t\t-c <n>\t\t\toutput the last N bytes

EOF;
        exit (0);
    }

    do
    {
        foreach ($urls as $wt)
        {
            if (($res = $wt->update()) !== False)
            {
                if ($prev_print !== $wt->url && !$no_headers)
                {
                    print "\n==> {$wt->url} <==\n";
                    print $res;
                }
                else
                {
                    print "$res";
                }

                $prev_print = $wt->url;
            }
        }
        sleep ($interval);
    }
    while ($running = true);
}

?>
