<?php namespace ADL;

class Sms
{
    protected $curl;
    protected $timeout = 30;
    protected $jstoken;
    protected $way2smsHost;
    protected $refurl;

    public function login($username, $password)
    {
        $this->curl = curl_init();
        $uid = urlencode($username);
        $pwd = urlencode($password);
        curl_setopt($this->curl, CURLOPT_URL, "http://way2sms.com");
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
        $a = curl_exec($this->curl);

        if (preg_match('#Location: (.*)#', $a, $r)) {
            $this->way2smsHost = trim($r[1]);
        }
        
        curl_setopt($this->curl, CURLOPT_URL, $this->way2smsHost . "Login1.action");
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt(
            $this->curl, 
            CURLOPT_POSTFIELDS, 
            "username={$uid}&password={$pwd}&button=Login"
        );

        curl_setopt($this->curl, CURLOPT_COOKIESESSION, 1);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, "cookie_way2sms");
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl, CURLOPT_MAXREDIRS, 20);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt(
            $this->curl, 
            CURLOPT_USERAGENT, 
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36"
        );

        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($this->curl, CURLOPT_REFERER, $this->way2smsHost);
        
        $text = curl_exec($this->curl);
        
        if (curl_errno($this->curl)) {
            return "access error : " . curl_error($this->curl);
        }
        $pos = stripos(curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL), "main.action");
        
        if ($pos === "FALSE" || $pos == 0 || $pos == "") {
            return "invalid login";
        }

        $this->refurl  = curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL);
        $tokenLocation = strpos($this->refurl, "Token");
        $this->jstoken = substr($this->refurl, $tokenLocation + 6, 37);

        return true;
    }

    public function send($phone, $msg)
    {
        $result = [];
        
        if (trim($msg) == "" || strlen($msg) == 0) {
            return "invalid message";
        }
        
        $msg   = substr($msg, 0, 140);
        $pharr = explode(",", $phone);
        foreach ($pharr as $p) {
            if (strlen($p) != 10 || !is_numeric($p) || strpos($p, ".") != false) {
                $result[] = [
                    'phone' => $p,
                    'msg' => $msg,
                    'result' => "invalid number"
                ];
                continue;
            }

            curl_setopt(
                $this->curl, 
                CURLOPT_URL, 
                "{$this->way2smsHost}smstoss.action"
            );

            curl_setopt(
                $this->curl, CURLOPT_REFERER, curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL)
            );
            
            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt(
                $this->curl, 
                CURLOPT_POSTFIELDS, 
                "ssaction=ss&Token={$this->jstoken}&mobile={$p}message={$msg}&button=Login"
            );
            
            $contents = curl_exec($this->curl);
            $pos = strpos($contents, 'Message has been submitted successfully');
            $res = ($pos !== false) ? true : false;
            
            $result[] = [
                'phone' => $p,
                'msg' => $msg,
                'result' => $res
            ];
        }
        return $result;
    }

    public function logout()
    {
        curl_setopt($this->curl, CURLOPT_URL, "{$this->way2smsHost}LogOut");
        curl_setopt($this->curl, CURLOPT_REFERER, $this->refurl);
        $text = curl_exec($this->curl);
        curl_close($this->curl);
    }
}
