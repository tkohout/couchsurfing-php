<?php


class CSAPI{
    
    const PRIVATE_KEY = 'v3#!R3v44y3ZsJykkb$E@CG#XreXeGCh';
    const BASE_URL = "https://hapi.couchsurfing.com";
    const LOGGING = false;

    protected $uid;
    protected $access_token;
    protected $curl;

    

    function CSAPI($username = NULL, $password = NULL, $uid = NULL, $access_token = NULL) {

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($this->curl, CURLOPT_PROXY, "127.0.0.1"); 
        //curl_setopt($this->curl, CURLOPT_PROXYPORT, 8888); 
        //curl_setopt($this->curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);


        if ($uid && $access_token){
          $this->uid = $uid;
          $this->access_token = $access_token;
        }else{
          assert($username && $password);
          $this->login($username, $password);

        }

    }

    protected function getUrlSignature($key, $msg){
      
      return hash_hmac('sha1', utf8_encode($msg), utf8_encode($key), false);
    }

    protected function getInitialHeaders(){
      return array(
                "X-CS-Url-Signature" => "",
                "Content-Length" => 0,
                "Accept-Language" => "en;q=1",
                //"Accept-Encoding" => "gzip, deflate",
                "Accept" => "application/json",
                "User-Agent" => "Dalvik/2.1.0 (Linux; U; Android 5.0.1; Android SDK built for x86 Build/LSX66B) Couchsurfing/android/20141121013910661/Couchsurfing/3.0.1/ee6a1da",
                "Content-Type" => "application/json; charset=utf-8",
                
      );
      }

    protected function encodeHeaders($headers){
      $new_array = array();

      foreach ($headers as $key=>$value){
        $new_array[] = $key . ": " . $value;

      }

      return $new_array;
    }

    protected function setupHeaderSignature($url, $data, $signature){
      $headers = $this->getInitialHeaders();
      $headers["X-CS-Url-Signature"] = $signature;
      $headers["Content-Length"] = strlen($data);
      if ($this->access_token){
        $headers["X-Access-Token"] = $this->access_token;
      }
      return $headers;
    }

    function login($username, $password){
      $login_payload = json_encode(array(
                        "credentials" => array( "authToken"=> $password, "email"=> $username),
                        "actionType" => "manual_login",
                       ));

      $response = $this->postRequest("/api/v2/sessions", $login_payload);
 
      if (isset($response->sessionUser)){
        $session = $response->sessionUser;
        $this->uid = $session->id;
        $this->access_token = $session->accessToken;

        if (CSAPI::LOGGING){
          echo "Logged in with access_token: " . $this->access_token . " uid: " . $this->uid;
        }
      }else{
        return false;
      }
    }

    function getConversations($since, $conversationsPerPage=20, $messagesPerConversation=1){
      $url = "/api/v2/users/" . $this->uid . "/conversations/sync?&conversationsPerPage=" . $conversationsPerPage . "&since=". $since->format('Y-m-dTH:i:s') . "&messagesPerConversation=" . $messagesPerConversation;
      $response = $this->postRequest($url, "");
      return $response;
    }

    function postRequest($url, $data){
      $signature = $this->getUrlSignature(CSAPI::PRIVATE_KEY . (($this->uid) ? "." . $this->uid : ""), $url.$data);
      $headers = $this->setupHeaderSignature($url, $data, $signature);
      
      curl_setopt($this->curl, CURLOPT_URL, self::BASE_URL . $url);
      curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);   
                                                                     
                                                                             
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->encodeHeaders($headers));                      
      $response = curl_exec($this->curl);

      return json_decode($response);
    }

}

