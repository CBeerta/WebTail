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
*
**/

class WebTail
{

    /**
    * Where are we right now, to continue the partial request 
    **/
    private $offset = 0;

    /**
    * Url to tail
    **/
    private $url;    
    
    /**
    * interval for updates
    **/
    private $interval;


    /**
    * Initial HEAD request to figure out content length and check general availability of the file
    *
    **/
    public function __construct($url, $interval = 5)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
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
        $this->url = $url;
        $this->interval = $interval;
        curl_close($ch);
    }
    
    /**
    * Partial download of the file with previous content length as offset
    *
    **/
    public function tail()
    {
        while (true)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url); 
            curl_setopt($ch, CURLOPT_RESUME_FROM, $this->offset);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_USERAGENT, "webtail.php"); 
            $res = curl_exec($ch);
            
            $info = curl_getinfo($ch);
            
            if ($info['download_content_length'] > 1)
            {
                $this->offset += $info['download_content_length'];
                print $res;
            }
        
            curl_close($ch);
            sleep($this->interval);    
        }
    }
}



if (empty($argv[1]))
{
    print "Usage: \n";
    print "webtail.php <url> [interval]\n";
    exit (0);
}

$url = $argv[1];
$interval = isset($argv[2]) ? $argv[2] : 5;

try 
{
    $wt = new WebTail($url, $interval);
}
catch (Exception $e)
{
    print $e->getMessage();
    exit (1);
}

$wt->tail();


?>
