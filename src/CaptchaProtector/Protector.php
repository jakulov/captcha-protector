<?php
namespace CaptchaProtector;

use Gregwar\Captcha\CaptchaBuilder;

/**
 * Class Protector
 * @package CaptchaProtector
 */
class Protector
{
    /** @var string */
    protected $storageDir;
    /** @var bool */
    protected $needCaptcha = false;
    /** @var bool */
    protected $captchaShown = false;
    /** @var int */
    protected $requestLimit = 0;
    /** @var int */
    protected $timeLimit;
    /** @var int */
    protected $requestCount = 0;
    /** @var CaptchaBuilder */
    protected $captchaBuilder;
    /** @var string */
    public $captchaCookieName = 'captcha_protector';
    /** @var int */
    public $gcFrequency = 10;
    /** @var string */
    protected $request;
    /** @var int */
    public $clientInfoExpires = 3600;
    /** @var int */
    public $captchaInfoExpires = 3600;

    /**
     * @param string $storageDir
     * @param int $requestLimit
     * @param int|null $timeLimit
     */
    public function __construct($storageDir = '/tmp', $requestLimit = 3, $timeLimit = null)
    {
        $this->storageDir = $storageDir .'/captcha_protector';
        $this->requestLimit = $requestLimit;
        $this->timeLimit = $timeLimit;
    }

    /**
     * @param null|string $request
     * @return $this
     */
    public function protect($request = null)
    {
        if($request === null) {
            $uri = $_SERVER['REQUEST_METHOD'] . '/ ' . $_SERVER['REQUEST_URI'];
            $request = explode('?', $uri)[0];
        }

        if(!$request) {
            throw new \RuntimeException('Unable to protect empty request: '. var_export($request, true));
        }
        $this->request = $request;

        $clientIp = $this->getClientIp();
        $info = $this->processClientInfo($this->getClientInfo($clientIp));
        $this->setClientInfo($clientIp, $info);

        $this->needCaptcha = $this->requestCount > $this->requestLimit;
        if($this->needCaptcha) {
            $this->captchaShown = $this->requestCount - $this->requestLimit === 1 ? false : true;
        }

        return $this;
    }

    /**
     * @param $info
     * @return mixed
     */
    protected function processClientInfo($info)
    {
        if($this->timeLimit !== null) {
            if(!isset($info[$this->request])) {
                $info[$this->request] = [
                    time()
                ];
            }
            else {
                $info[$this->request][] = time();
            }
            $actualRequests = [];
            $actualTime = time() - $this->timeLimit;
            foreach($info[$this->request] as $time) {
                if($time > $actualTime) {
                    $this->requestCount++;
                    $actualRequests[] = $time;
                }
            }
            $info[$this->request] = $actualRequests;
        }
        else {
            if(!isset($info[$this->request])) {
                $info[$this->request] = 1;
            }
            else {
                $info[$this->request] += 1;
            }
            $this->requestCount = $info[$this->request];
        }

        return $info;
    }

    /**
     * @return int
     */
    public function getRequestCount()
    {
        return $this->requestCount;
    }

    /**
     * @param null|string $request
     * @return $this
     */
    public function forgive($request = null)
    {
        if($request === null) {
            $uri = $_SERVER['REQUEST_METHOD'] . '/ ' . $_SERVER['REQUEST_URI'];
            $request = explode('?', $uri)[0];
        }

        if(!$request) {
            throw new \RuntimeException('Unable to protect empty request: '. var_export($request, true));
        }
        $this->request = $request;

        $clientIp = $this->getClientIp();
        $info = $this->getClientInfo($clientIp);
        if(isset($info[$request])) {
            $info[$request] = $this->timeLimit === null ? 0 : [];
            $this->setClientInfo($clientIp, $info);
        }

        $this->needCaptcha = false;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isNeedCaptcha()
    {
        return $this->needCaptcha;
    }

    /**
     * @param $ip
     * @return array
     */
    public function getClientInfo($ip)
    {
        $file = $this->getClientFilename($ip);
        if(!file_exists($file)) {
            if(!is_dir(dirname($file))) {
                if(!mkdir(dirname($file), 0777, true)) {
                    throw new \RuntimeException('Unable to create storage dir: '. $this->storageDir);
                }
            }
            file_put_contents($file, json_encode([]));
        }

        $info = file_get_contents($file);
        $json = json_decode($info, true);

        return $json ? $json : [];
    }

    /**
     * @param $ip
     * @param array $info
     * @return int
     */
    public function setClientInfo($ip, $info = [])
    {
        if(mt_rand(0, 100) < $this->gcFrequency) {
            $this->runGC();
        }

        return file_put_contents($this->getClientFilename($ip), json_encode($info));
    }

    /**
     * @return int
     */
    public function runGC()
    {
        $ipDir = $this->storageDir;
        $time = time() - $this->clientInfoExpires;
        $c = $this->clearUnused($ipDir, $time);

        $captchaDir = $this->storageDir .'/captcha';
        $time = time() - $this->captchaInfoExpires;
        $c += $this->clearUnused($captchaDir, $time);

        return $c;
    }

    /**
     * @param $dir
     * @param $modifiedLaterThan
     * @return int
     */
    protected function clearUnused($dir, $modifiedLaterThan)
    {
        $c = 0;
        if(is_dir($dir)) {
            $dh = opendir($dir);
            while ($f = readdir($dh)) {
                $file = $dir .'/'. $f;
                if(is_file($file) && (filemtime($file) < $modifiedLaterThan)) {
                    unlink($file);
                    $c++;
                }
            }
        }

        return $c;
    }

    /**
     * @param $ip
     * @return string
     */
    public function getClientFilename($ip)
    {
        return $this->storageDir .'/'. $ip .'.json';
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        $client  = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '';
        $forward = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        }
        else {
            $ip = $remote;
        }

        return $ip;
    }

    /**
     * @return CaptchaBuilder
     */
    protected function getCaptchaBuilder()
    {
        if($this->request === null) {
            throw new \RuntimeException('Unable to start captcha protecting without calling protect method');
        }

        if($this->captchaBuilder === null) {
            $this->captchaBuilder = new CaptchaBuilder();
        }

        return $this->captchaBuilder;
    }

    /**
     * @param $width
     * @param $height
     * @param null $phrase
     * @param int $quality
     * @return string
     */
    public function getCaptcha($width, $height, $phrase = null, $quality = 90)
    {
        if($phrase) {
            $this->getCaptchaBuilder()->setPhrase($phrase);
        }

        $content = $this->getCaptchaBuilder()->build($width, $height)->inline($quality);
        $this->saveCaptchaRequest();

        return $content;
    }

    /**
     * @return int
     */
    protected function saveCaptchaRequest()
    {
        $file = $this->getCaptchaRequestFile($this->getCaptchaRequestUid());
        if(!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        return file_put_contents($file, $this->getCaptchaBuilder()->getPhrase());
    }

    /**
     * @return string
     */
    protected function getCaptchaRequestUid()
    {
        if(!isset($_COOKIE[$this->captchaCookieName]) || !$_COOKIE[$this->captchaCookieName]) {
            $requestUid = md5(mt_rand(10000, 999999) .'_'. time());
            setcookie($this->captchaCookieName, $requestUid, time() + 3600, '/');
        }
        else {
            $requestUid = $_COOKIE[$this->captchaCookieName];
        }

        return $requestUid;
    }

    /**
     * @param $requestId
     * @return string
     */
    public function getCaptchaRequestFile($requestId)
    {
        return $this->storageDir .'/captcha/'. $requestId;
    }

    /**
     * @param $userInput
     * @return bool
     */
    public function isCaptchaResolved($userInput)
    {
        $file = $this->getCaptchaRequestFile($this->getCaptchaRequestUid());
        if(file_exists($file) && trim($userInput)) {
            if ($userInput == file_get_contents($file)) {
                unlink($file);
                return true;
            }
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function isCaptchaShown()
    {
        return $this->captchaShown;
    }
}