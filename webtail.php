<?php

/**
* webtail.php 
*
* Tails a Logfile located on a webserver (inspired by http://www.jibble.org/webtail/)
*
* @author Claus Beerta <claus@beerta.de>
*
* @todo: 
*           basicauth ?
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
    public function __construct($url, $interval = 5)
    {
        $this->url = $url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url); 
        curl_setopt($ch, CURLOPT_NOBODY, True); 
        curl_setopt($ch, CURLOPT_USERAGENT, "webtail.php"); 
        $res = curl_exec($ch);

        $info = curl_getinfo($ch);
  
        if ($info['http_code'] !== 200)
        {
            throw new Exception ("Unable to open {$url}\n");
        }
        curl_setopt($ch, CURLOPT_NOBODY, False); 
        
        $this->offset = $info['download_content_length'];
        $this->interval = $interval;
        curl_close($ch);
    }

    /**
    * Check via http head request if new content has arrived or file got truncated
    *
    **/
    public function has_new_content()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url); 
        curl_setopt($ch, CURLOPT_NOBODY, True); 
        curl_setopt($ch, CURLOPT_USERAGENT, "webtail.php"); 
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] !== 200)
        {
            /** File vanished, so no updates **/
            return false;
        }
        curl_setopt($ch, CURLOPT_NOBODY, False); 
        curl_close($ch);

        if ($this->offset > $info['download_content_length'])
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
            $ch = curl_init();
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
    private function _setup_curl($ch)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url); 
        curl_setopt($ch, CURLOPT_USERAGENT, "webtail.php: ".WEBTAIL_VERSION);

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
    $urls = array();

    foreach ($argv as $k => $v)
    {
        if ($v == "-i" && is_numeric($argv[$k+1]))
        {
            $interval = $argv[$k+1];
        }
        else if (stristr($v, 'http'))
        {
            try
            {
                $wt = new WebTail($v, $interval);
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
        print "Usage: \n";
        print "webtail.php [-i <interval>] <url> <url...n>\n";
        exit (0);
    }

    do
    {
        foreach ($urls as $wt)
        {
            if (($res = $wt->update()) !== False)
            {

                if ($prev_print !== $wt->url)
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
