<?php
/**
 * Created by PhpStorm.
 * User: Gabriel B.R
 * Email: gabriel.bentara@gmail.com
 * Date: 23/10/18
 * Time: 8:02 AM
 */

require "model/SessionModel.php";

class Session
{
    private $session;

    public function __construct()
    {
        $this->session = new SessionModel();
    }

    public function generateSessionId() {
        $sessionId = "";
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < 30; $i++) {
            $sessionId .= $chars[random_int(0, strlen($chars)-1)];
        }

        return $sessionId;
    }

    public function getBrowserName(){
        $u_agent = $_SERVER["HTTP_USER_AGENT"];

        if (preg_match('/Firefox/i',$u_agent)) {
            $bname = 'Mozilla Firefox';
        } elseif (preg_match('/OPR/i',$u_agent)){
            $bname = 'Opera';
        } elseif( preg_match('/Chrome/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
            $bname = 'Google Chrome';
        } elseif (preg_match('/Safari/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
            $bname = 'Apple Safari';
        } elseif (preg_match('/Edge/i',$u_agent)){
            $bname = 'Edge';
        } elseif (preg_match('/Trident/i',$u_agent)){
            $bname = 'Internet Explorer';
        }
        return $bname;
    }

    public function getIPAddr(){
//        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
//            $ip = $_SERVER['HTTP_CLIENT_IP'];
//        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
//            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
//        } else {
//            $ip = $_SERVER['REMOTE_ADDR'];
//        }
        $ip = "197";
        return $ip;
    }

    public function setSession($userId, $username) {
        if ($this->inSession()) {
            return;
        }

        $browsername = $this->getBrowserName();
        $ip = $this->getIPAddr();
        $sessionId = $this->generateSessionId();

        if ($this->session->checkSessionUnavailable($userId)) {
//            session_start();

            $this->session->setSessionId($sessionId);
            $this->session->setUserId($userId);
            $this->session->setExpire(date('Y-m-d H:i:s', strtotime("+10 minutes")));
            $this->session->setBrowser($browsername);
            $this->session->setIp($ip);

            $this->session->insert();

            setcookie("session", $sessionId);
            setcookie("username", $username);
        } else {
            $this->session->loadByUserID($userId);
            if (($this->session->getBrowser() == $browsername) and ($this->session->getIp() == $ip)){
                $this->session->setExpire(date('Y-m-d H:i:s', strtotime("+10 minutes")));
                setcookie("session", $this->session->getSessionId());
                setcookie("username", $username); 
            }
            if (($this->session->getBrowser() != $browsername) or ($this->session->getIp() != $ip)){
//                session_start();
                $this->session->setSessionId($sessionId);
                $this->session->setUserId($userId);
                $this->session->setExpire(date('Y-m-d H:i:s', strtotime("+10 minutes")));
                $this->session->setBrowser($browsername);
                $this->session->setIp($ip);

                $this->session->insert();

                setcookie("session", $sessionId);
                setcookie("username", $username);             
            }
            return;
        }
    }

    public function inSession() {
        if (!isset($_COOKIE["session"])) {
            return False;
        }

        $sessionId = $_COOKIE["session"];
        $this->session->setSessionId($sessionId);
        $this->session->load();

        if ($this->session->getUserId() == null) {
            $this->session->delete();
            return False;
        }

        if ($this->session->getExpire() < date('Y-m-d H:i:s')) {
            $this->session->delete();
            return False;
        }

        $browsername = $this->getBrowserName();
        $ip = $this->getIPAddr();

        if (($this->session->getBrowser() != $browsername) and ($this->session->getIp() == $ip)){
            $this->session->delete();
            return False;
        }

        if ($this->session->getIp() != $ip){
            $this->session->delete();
            return False;          
        }
        return $this->session->getUserId();
    }

    public function destroy() {
        if (!isset($_COOKIE["session"])) {
            return;
        }

        $sessionId = $_COOKIE["session"];
        $this->session->setSessionId($sessionId);
        $this->session->load();
        $this->session->delete();
//        session_destroy();

        unset($_COOKIE["session"]);
    }
}