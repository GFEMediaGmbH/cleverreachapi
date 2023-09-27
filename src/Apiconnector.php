<?php

namespace Gfe\Cleverreachapi;

class Apiconnector
{
    protected $token;
    protected $tokenObject;
    protected $token_lifetime;
    protected $token_invalid_stamp;
    public $URL_TOKEN = 'https://rest.cleverreach.com/oauth/token.php';
    public $URL_SERVER = 'https://rest.cleverreach.com/v3/';
    protected $user;
    protected $pass;

    public function __construct($user , $pass , $tokenObject = null)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->tokenObject = $tokenObject;

    }
    /*
     * returns the current access token for storage and if none is there get a new one
     */
    public function getTokenObject(){
        if(!$this->tokenObject || $this->tokenObject->token_invalid_stamp < time()){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->URL_TOKEN);
            curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->pass);
            curl_setopt($curl, CURLOPT_POSTFIELDS, ['grant_type' => 'client_credentials']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $res_obj = json_decode($result);
            if(isset($res_obj->access_token)){

                $this->tokenObject = $res_obj;
                $this->token = $res_obj->access_token;
                $res_obj->token_invalid_stamp = time() + $res_obj->expires_in;
                $this->token_invalid_stamp = $res_obj->token_invalid_stamp;
                $this->token_lifetime = $res_obj->expires_in;
                //this can be stored somewhere to not run auth on every call
                return $res_obj;
            }
            else{
                throw new \Exception('error in retrieving access token');
            }
        }
        else{
            // recreate token settings
            $this->token = $this->tokenObject->access_token;
            $this->token_invalid_stamp = $this->tokenObject->token_invalid_stamp;
            $this->token_lifetime = $this->tokenObject->expires_in;
            //this can be stored somewhere to not run auth on every call
            return $this->tokenObject;
        }
        return null;
    }

    /*
     * sets the token Object // recreates token if it is no more valid
     */
    public function setTokenObject($tokenObject){
        if($tokenObject->token_invalid_stamp < time()){
            //token has expired;
            $this->getTokenObject();
        }
        else{
            $this->tokenObject = $tokenObject;
        }

        $this->token = $this->tokenObject->access_token;
        $this->token_invalid_stamp = $this->tokenObject->token_invalid_stamp;
        $this->token_lifetime = $this->tokenObject->expires_in;
        return $this->tokenObject;
    }
    /*
     * subscribes the user to the list id
     */
    public function subscribeUser($email, $listId , $doiFormId , $source = 'cleverreachapi'){

        if($this->getTokenObject()){

            $new_user = array(
                "email"      => $email,
                "registered" => time(),  //current date
                "activated"  => 0,       //NOT active, will be set by DOI
                "source"     => $source,
                "attributes" => array(
                )
            );
            $header = array();
            $header['content'] = 'Content-Type: application/json';
            $header['token'] = 'Authorization: Bearer ' . $this->token;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            $curl_post_data = json_encode($new_user);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
            $url = $this->URL_SERVER.'groups/'.$listId.'/receivers';
            curl_setopt($curl, CURLOPT_URL, $url);
            $curl_response = curl_exec($curl);
            $resp_obj = json_decode($curl_response);
            if(is_object($resp_obj)){
                $url = $this->URL_SERVER.'forms/'.$doiFormId.'/send/activate';
                curl_setopt($curl, CURLOPT_URL, $url);
                $doi_user = array(
                    "email"   => $email,
                    "doidata" => array(
                        "user_ip"    => $_SERVER["REMOTE_ADDR"],
                        "referer"    => $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"],
                        "user_agent" => $_SERVER["HTTP_USER_AGENT"]
                    ));
                $curl_post_data = json_encode($doi_user);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
                $curl_response = curl_exec($curl);
                $resp_obj = json_decode($curl_response);
            }
            $headers = curl_getinfo($curl);
        }
        curl_close($curl);
    }
}
