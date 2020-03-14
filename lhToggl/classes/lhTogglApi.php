<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once __DIR__.'/../interface/lhTogglApiInterface.php';
require_once __DIR__.'/../abstract/lhTogglClass.php';
/**
 * Description of lhTogglApi
 *
 * @author user
 */
class lhTogglApi extends lhTogglClass implements lhTogglApiInterface {
    const TOGGL_API_PATH = 'https://www.toggl.com/api/v8/';
    
    private static $static_api;
    
    private $auth;
    private $workspaces;
    
    public function __construct($token) {
        if ($this->hasInstance()) {
            throw new Exception("There can be only one instance of lhTogglApi", -10005);
        }
        lhTogglApi::$static_api = $this;
        $this->auth = $token.':api_token';
        $this->workspaces = $this->loadWorkspaces();
    }
    
    public static function api() {
        return lhTogglApi::$static_api;
    }
    
    public function hasInstance() {
        return is_a(lhTogglApi::$static_api, 'lhTogglApi');
    }
    
    public function loadWorkspaces() {
        $r = $this->apiCall('workspaces');
        print_r($r);
        throw new Exception("Stop");
    }

    public function getWorkspaces() {
        if (!$this->workspaces) {
            
        }
    }

    



    public function apiCall($func, $more=false, $data=null) {
        if ($more) {
            $path = lhTogglApi::TOGGL_API_PATH . $func . '/' . $more;
        } else {
            $path = lhTogglApi::TOGGL_API_PATH . $func;
        }
        
        if ($data === null) {
            return $this->apiGet($path);
        } else {
            return $this->apiPut($path, $data);
        }
    }

    public function apiGet($path) {
        $ch = curl_init($path);
        if ( $ch ) {
            if (curl_setopt_array( $ch, array(
                CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $this->auth
            ))) {    
                $content=curl_exec($ch);
                if ($content === FALSE) {
                    throw new Exception("Error getting data: ". curl_errno($ch), -10004);
                } else {
                    print_r([ 'path' => $path, 'content' => $content]);
                }
                curl_close($ch);
                return json_decode($content);
            }
        }
        throw new Exception("curl_init error", -10006);
    }

    public function apiPut($path, $data) {
        $ch = curl_init($path);
        if ( $ch ) {
            if (curl_setopt_array( $ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PUT => true,
                CURLOPT_POSTFIELDS => array( 'payload' => json_encode($data)),
                CURLOPT_USERPWD => $this->auth
            ))) {    
                $content=curl_exec($ch);
                curl_close($ch);
                return json_decode($content);
            }
        }
        throw new Exception("curl_init error", -10006);
    }









    // Тесты
    private function _testHasInstance() {
        // Проверим что возвращает hasInstance когда объект не создан
        lhTogglApi::$static_api = NULL;
        $r = $this->hasInstance();
        if ($r) {
            throw new Exception("Must return false if there is no instances of this class", -10002);
        }
        echo '.';
        
        lhTogglApi::$static_api = $this;
        $r = $this->hasInstance();
        if (!$r) {
            throw new Exception("Must return true as there is an instance of the class", -10002);
        }
        echo '.';
    }






    // lhTestingSuite
    protected function _test_data() {
        return [
            'api' => [[$this]],
            'hasInstance' => '_testHasInstance',
            'loadWorkspaces' => '__test_skip'           // Used in __construct
        ];
    }
    
    protected function _test_call($func, ...$args) {
        return $this->$func(...$args);
    }
}
