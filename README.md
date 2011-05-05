This tool allows you tail a or multiple file(s) located on a Webserver. It Uses HTTP HEAD and Partial requests to save bandwidth.

Your webserver will have to support HEAD and Partial Requests properly, modern Webservers are usually not a problem.

    Usage: webtail.php [OPTION]... [FILE]...

    Valid options are:
                    -i            interval for updates
                    --version               output version information and exit
                    -q, --quiet, --silent   never output headers giving file names
                    -c                   output the last N bytes</pre>


## Example Output: ##

    # php -q webtail.php -c 500 -i 1 http://eurybe.local/logfiles/access.log http://eurybe.local/logfiles/error.log

    ==> http://eurybe.local/logfiles/access.log <==
    "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - test [23/Dec/2010:11:51:33 +0100] "HEAD /logfiles/access.log HTTP/1.1" 200 - "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - test [23/Dec/2010:11:51:33 +0100] "GET /logfiles/access.log HTTP/1.1" 206 592 "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - test [23/Dec/2010:11:51:33 +0100] "HEAD /logfiles/error.log HTTP/1.1" 200 - "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - - [23/Dec/2010:11:52:15 +0100] "HEAD /logfiles/access.log HTTP/1.1" 200 - "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - - [23/Dec/2010:11:52:15 +0100] "HEAD /logfiles/error.log HTTP/1.1" 200 - "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - - [23/Dec/2010:11:52:15 +0100] "HEAD /logfiles/access.log HTTP/1.1" 200 - "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"

    ==> http://eurybe.local/logfiles/error.log <==
    /eurybe.local:9080/characters/index
    [Thu Dec 23 11:29:48 2010] [error] [client 192.168.1.47] ale curl query http://api.eve-online.com/eve/CharacterInfo.xml.aspx, referer: http://eurybe.local:9080/characters/index
    [Thu Dec 23 11:29:48 2010] [error] [client 192.168.1.47] , referer: http://eurybe.local:9080/characters/index
    [Thu Dec 23 11:51:57 2010] [notice] Graceful restart requested, doing restart
    [Thu Dec 23 11:51:57 2010] [notice] Apache/2.2.9 (Debian) configured -- resuming normal operations

    ==> http://eurybe.local/logfiles/access.log <==
    192.168.1.47 - - [23/Dec/2010:11:52:15 +0100] "GET /logfiles/access.log HTTP/1.1" 206 934 "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - - [23/Dec/2010:11:52:15 +0100] "HEAD /logfiles/error.log HTTP/1.1" 200 - "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - - [23/Dec/2010:11:52:15 +0100] "GET /logfiles/error.log HTTP/1.1" 206 500 "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"
    192.168.1.47 - - [23/Dec/2010:11:52:16 +0100] "HEAD /logfiles/access.log HTTP/1.1" 200 - "-" "$Id: webtail.php 847 2010-12-23 10:40:55Z claus $"

