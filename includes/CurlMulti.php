<?php
/**
 * Description of CurlMulti
 *
 * @author javier
 */
class CurlMulti {

    private $multiCurlH;
    private $handleList;

    public function __construct() {
        $this->multiCurlH = curl_multi_init();
        if ($this->multiCurlH === false) {
            throw new ErrorException("Failed to initialize CURLM");
        }
        $this->handleList = new SplObjectStorage();
    }

    public function add($url, $body = null, $maxResponseWait = 3600, $maxConnectWait = 5) {
        $ch = new Curl($url, $body, $maxResponseWait, $maxConnectWait);
        $res = curl_multi_add_handle($this->multiCurlH, $ch->getHandle());
        if ($res !== 0) {
            throw new ErrorException("Unable to add CurlM instance ($res)");
        }
        $this->handleList[$ch] = $url;
    }

    public function execute() {
        // execute all queries simultaneously, and continue when all are complete
        $running = null;
        do {
            $res = curl_multi_exec($this->multiCurlH, $running);
            if ($res !== CURLM_OK) {
                error_log("Failed execute with error ($res)");
            }
        } while ($running);
    }

    public function end() {
        $responses = array();
        foreach ($this->handleList as  $ch => $url) {
            //close the handles
            $res = curl_multi_remove_handle($this->multiCurlH, $ch->getHandle());
            if ($res !== 0) {
                throw new ErrorException("Unable to remove CurlM instance ($res)");
            }
            unset($this->handleList[$ch]);
            // all of our requests are done, we can now access the results
            $response_1 = curl_multi_getcontent($ch->getHandle());
            $httpCode = curl_getinfo($ch->getHandle(), CURLINFO_HTTP_CODE);
            if ($httpCode != 200) {
                $responses[$url] = new Exception("PHPClientException::httpRequest($url,$httpCode, $response_1);");
            }else{
                $responses[$url] = json_decode($response_1, true);
            }
        }
        return $responses;
    }

    public function __destruct() {
        foreach ($this->handleList as $ch => $url) {
            curl_multi_remove_handle($this->multiCurlH, $ch->getHandle());
        }
        unset($this->handleList);
        curl_multi_close($this->multiCurlH);
    }

    public static function request($url, $body = null, $maxResponseWait = 3600, $maxConnectWait = 5) {
        $requestor = new self();
        $requestor->add($url, $body, $maxResponseWait, $maxConnectWait);
        $requestor->execute();
        $res = $requestor->end();
        return array_pop($res);
    }

    public static function format_url(array $urlParts) {
        $finalUrl = "{$urlParts['scheme']}://";
        if (isset($urlParts['user'])) {
            $finalUrl .= "{$urlParts['username']}:{$urlParts['pass']}@";
        }
        $finalUrl .= "{$urlParts['host']}";
        if (isset($urlParts['port'])) {
            $finalUrl .= ":{$urlParts['port']}";
        }
        $finalUrl .= "{$urlParts['path']}";
        if (isset($urlParts['query'])) {
            $finalUrl .= "?{$urlParts['query']}";
        }
        if (isset($urlParts['fragment'])) {
            $finalUrl .= "#{$urlParts['fragment']}";
        }
        return $finalUrl;
    }

}


class Curl {
    private $handle;
    public static function format_url(array $urlParts) {
        $finalUrl = "{$urlParts['scheme']}://";
        if (isset($urlParts['user'])) {
            $finalUrl .= "{$urlParts['username']}:{$urlParts['pass']}@";
        }
        $finalUrl .= "{$urlParts['host']}";
        if (isset($urlParts['port'])) {
            $finalUrl .= ":{$urlParts['port']}";
        }
        $finalUrl .= "{$urlParts['path']}";
        if (isset($urlParts['query'])) {
            $finalUrl .= "?{$urlParts['query']}";
        }
        if (isset($urlParts['fragment'])) {
            $finalUrl .= "#{$urlParts['fragment']}";
        }
        return $finalUrl;
    }
    public function __construct($url, $body = null, $maxResponseWait = 7200, $maxConnectWait = 5) {
        $urlParts = parse_url($url);

        $urlParts['scheme'] = (!isset($urlParts['scheme'])) ? (($_SERVER['HTTPS']) ? 'https' : 'http') : $urlParts['scheme'];
        $urlParts['host'] = (!isset($urlParts['host'])) ? $_SERVER['HTTP_HOST'] : $urlParts['host'];
        $finalUrl = self::format_url($urlParts);
        echo "\tConnecting: $finalUrl\n";
        $ch = curl_init();
        if(false === $ch) {
            throw new ErrorException("Unable to Create Curl");
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $maxConnectWait);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $maxResponseWait);
        curl_setopt($ch, CURLOPT_URL, $finalUrl);
        if ($body) {
            $json = json_encode($body);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json))
            );
        } else {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->handle=$ch;    
    }
    
    public function execute() {
        $response = curl_exec($this->getHandle());
        $httpCode = curl_getinfo($this->getHandle(), CURLINFO_HTTP_CODE);
        $errorCode = curl_errno($this->getHandle());
        if($errorCode != 0) {
            throw new Exception("PHPClientError::httpRequest:".curl_error($this->getHandle()));
        }
        if ($httpCode != 200) {
            throw new Exception("PHPClientException::httpRequest($httpCode, $response);");
        }
        return json_decode($response, true);
    }
    
    
    public function __destruct() {
        curl_close($this->handle);
    }
    public function getHandle() {
        return $this->handle;
    }
    
    public static function request($url, $body = null, $maxResponseWait = 7200, $maxConnectWait = 5) {
        $requestor = new self($url, $body = null, $maxResponseWait = 7200, $maxConnectWait = 5);
        return $requestor->execute();
    }
}