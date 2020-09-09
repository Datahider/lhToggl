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
    const TOGGL_API_DELETE = 'DELETE';
    
    private static $static_api;
    
    private $auth;
    private $workspaces;
    private $default_workspace;
    private $is_pro;


    public function __construct($_token, $_pro=false) {
        if ($this->hasInstance()) {
            throw new Exception("There can be only one instance of lhTogglApi", -10005);
        }
        $this->is_pro = $_pro;
        lhTogglApi::$static_api = $this;
        $this->auth = $_token.':api_token';
        $this->workspaces = $this->loadWorkspaces();
    }
    
    public function reconstruct($_token, $_pro=false) {
        $this->is_pro = $_pro;
        $this->auth = $_token.':api_token';
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
        if (!is_array($r)) {
            throw new Exception("Awaiting \$r to be an array. Got: ".print_r($r, TRUE));
        }
        $this->workspaces = [];
        foreach ($r as $ws) {
            $this->workspaces[] = new lhTogglWorkspace($ws->id);
        }
    }

    public function workspaces() {
        if (!$this->workspaces) {
            $this->loadWorkspaces();
        }
        return $this->workspaces;
    }

    public function defaultWorkspace($ws=FALSE) {
        if (!$ws) {
            return $this->default_workspace;
        } elseif (!is_a($ws, 'lhTogglWorkspace')) {
            throw new Exception("Argument must be of class lhTogglWorkspace or it's descendants");
        } else {
            $old_ws = $this->default_workspace;
            foreach ($this->workspaces as $oneof) {
                if ($oneof->data->id == $ws->data->id) {
                    $this->default_workspace = $ws;
                    return $old_ws;
                }
            }
            throw new Exception("Given workspace id=".$ws->data->id." is not accessible with current credinitials");
        }
    }

    public function findWorkspaces($_regex, $_return_array=false) {
        $found = [];
        foreach ($this->workspaces() as $workspace) {
            if (preg_match($_regex, $workspace->data->name)) {
                $found[] = $workspace;
                if (!$_return_array) {
                    return $workspace;
                }
            }
        }
        if ($_return_array) {
            return $found;
        } else {
            return NULL;
        }
    }
    
    public function getCurrentTimeEntry() {
        $r = $this->apiCall(lhTogglTimeEntry::API_FUNC, 'current');
        if (!isset($r->data)) {
            return NULL;
        }
        return new lhTogglTimeEntry($r->data);
    }

    protected function setWorkspaceIfAbsent($data) {
        $data = json_decode(json_encode($data));
        if (isset($data->client)) {
            if (!isset($data->client->wid)) {
                $data->client->wid = $this->defaultWorkspace()->data->id;
            }
        }
        return $data;
    }
    
    protected function filterProFeatures($data) {
        if ($this->is_pro) {
            return;
        }
        $data = json_decode(json_encode($data));
        if (isset($data->workspace)) {
            if (isset($data->workspace->default_hourly_rate)) {
                unset($data->workspace->default_hourly_rate);
            }
            if (isset($data->workspace->default_currency)) {
                unset($data->workspace->default_currency);
            }
            if (isset($data->workspace->rounding)) {
                unset($data->workspace->rounding);
            }
            if (isset($data->workspace->rounding_minutes)) {
                unset($data->workspace->rounding_minutes);
            }
            if (isset($data->workspace->only_admins_see_billable_rates)) {
                unset($data->workspace->only_admins_see_billable_rates);
            }
            if (isset($data->workspace->projects_billable_by_default)) {
                unset($data->workspace->projects_billable_by_default);
            }
        }
        return $data;
    }

    public function apiCall($func, $more=false, $data=null) {
        if ($more) {
            $path = lhTogglApi::TOGGL_API_PATH . $func . '/' . $more;
        } else {
            $path = lhTogglApi::TOGGL_API_PATH . $func;
        }
        
        if ($data === null) {
            $r = $this->apiGet($path);
        } elseif (!$more || ($more == 'start')) {
            $r = $this->apiPost($path, $data);
        } else {
            if ($data == lhTogglApi::TOGGL_API_DELETE) {
                $r = $this->apiDelete($path);
                return;
            } else {
                $r = $this->apiPut($path, $data);
            }
        }
        if (!is_a($r, 'stdClass') && !is_array($r)) {
            throw new Exception(print_r($r, TRUE), -10004);
        }
        return $r;
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
                if (!$content) {
                    throw new Exception("Error getting data: " . curl_errno($ch) . " - " . curl_error($ch), -10004);
                }
                curl_close($ch);
                $json = json_decode($content);
                if (!$json) {
                    throw new Exception("We expect json encoded data from upstream server. Got: ". $content, -10002);
                }
                return $json;
            }
        }
        throw new Exception("curl_init error", -10006);
    }

    public function apiPut($path, $data) {
        $data = $this->filterProFeatures($data);
        $fh = tmpfile();
        fwrite($fh, json_encode($data));
        fseek($fh, 0);
        $json = json_encode($data);

        $ch = curl_init($path);
        if ( $ch ) {
            if (curl_setopt_array( $ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: */*'
                ),
                CURLOPT_PUT => true,
                CURLOPT_INFILE => $fh,
                CURLOPT_USERPWD => $this->auth
            ))) {    
                $content=curl_exec($ch);
                if (!$content) {
                    throw new Exception("Error getting data: " . curl_errno($ch) . " - " . curl_error($ch), -10004);
                }
                curl_close($ch);
                $json = json_decode($content);
                if (!$json) {
                    throw new Exception("We expect json encoded data from upstream server. Got: ". $content, -10002);
                }
                return $json;
            }
        }
        throw new Exception("curl_init error", -10006);
    }

    public function apiPost($path, $data) {
        $data = $this->filterProFeatures($data);
        $data = $this->setWorkspaceIfAbsent($data);
        $ch = curl_init($path);
        if ( $ch ) {
            if (curl_setopt_array( $ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_USERPWD => $this->auth
            ))) {    
                $content=curl_exec($ch);
                if (!$content) {
                    throw new Exception("Error getting data: " . curl_errno($ch) . " - " . curl_error($ch), -10004);
                }
                curl_close($ch);
                $json = json_decode($content);
                if (!$json) { 
                    throw new Exception("We expect json encoded data from upstream server. Got: ". $content, -10002);
                }
                return $json;
            }
        }
        throw new Exception("curl_init error. \$path=$path", -10006);
    }

    public function apiDelete($path) {
        $ch = curl_init($path);
        if ( $ch ) {
            if (curl_setopt_array( $ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_USERPWD => $this->auth
            ))) {    
                $content=curl_exec($ch);
                if (!$content) {
                    throw new Exception("Error getting data: " . curl_errno($ch) . " - " . curl_error($ch), -10004);
                }
                curl_close($ch);
                return;
            }
        }
        throw new Exception("curl_init error. \$path=$path", -10006);
    }







    // Тесты
    protected function _testHasInstance() {
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

    protected function _testWorkspaces() {
        $r = $this->workspaces();
        $nows = TRUE;
        
        foreach ($r as $ws) {
            $nows = FALSE;
            if (!is_a($ws, 'lhTogglWorkspace')) {
                throw new Exception("Awaiting the workspace to be an lhTogglWorkspace instance. Got: ", print_r($ws, TRUE), -10002);
            }
        }
    }

    protected function _testSetWorkSpaceIfAbsent() {
        $data1 = (object)[
            "client" => (object)[
                'name' => 'Some name',
                'wid' => '13'
            ]
        ];
        $data2 = (object)[
            "client" => (object)[
                'name' => 'Some name',
            ]
        ];
        
        $data1 = $this->setWorkspaceIfAbsent($data1);
        if ($data1->client->wid != 13) {
            throw new Exception("Workspace Id unexpectedly changed to ".$data1->client->wid);
        }
        $data2 = $this->setWorkspaceIfAbsent($data2);
        if ($data2->client->wid != $this->defaultWorkspace()->data->id) {
            throw new Exception("Default workspace still ".$data2->client->wid);
        }
    }
    
    protected function _testFilterProFeatures() {
        $data1 = (object)[
            "workspace" => (object)[
                'name' => 'Some name',
                'wid' => '13',
                'only_admins_see_billable_rates' => TRUE
            ]
        ];
        $data1 = $this->filterProFeatures($data1);
        if (isset($data1->workspace->only_admins_see_billable_rates)) {
            throw new Exception("only_admins_see_billable_rates is still ".$data1->workspace->only_admins_see_billable_rates);
        }
    }
    
    protected function _testGetCurrentTimeEntry() {
        $project = new lhTogglProject(['name' => "Test getting current time entry ".uniqid()]);
        $te = new lhTogglTimeEntry();
        $te->start($project);
        if ($te->data->id != $this->getCurrentTimeEntry()->data->id) {
            throw new Exception("Current time etntry is not the same as started");
        }
        $te->stop();
        if ($this->getCurrentTimeEntry() !== NULL) {
            throw new Exception("Timer is still running");
        }
    }

    // lhTestingSuite
    protected function _test_data() {
        global $correct_token;
        return [
            'reconstruct' => [[$correct_token, NULL]],
            'api' => [[$this]],
            'hasInstance' => '_testHasInstance',
            'loadWorkspaces' => '_test_skip_',          // Используется в конструкторе
            'workspaces' => '_testWorkspaces',
            'apiCall' => '_test_skip_',                 // Используется в конструкторе и множестве других классов
            'apiGet' => '_test_skip_',                  // Используется в конструкторе и множестве других классов
            'apiPut' => '_test_skip_',                  // Используется в множестве других классов
            'apiPost' => '_test_skip_',                 // Используется в множестве других классов
            'apiDelete' => '_test_skip_',               // Будет протестировано на клиентах
            'defaultWorkspace' => [
                [NULL],
                [new lhTogglWorkspace(4066148), NULL],
                [new lhTogglWorkspace(4066148), new lhTogglWorkspace(4066148)],
            ],
            'setWorkspaceIfAbsent' => '_testSetWorkSpaceIfAbsent',
            'filterProFeatures' => '_testFilterProFeatures',
            'findWorkspaces' => [
                ['/^LOST/u', new lhTest(lhTest::IS_A, 'lhTogglWorkspace')],
                ['/^Тестовый воркспейс$/u', new lhTest(lhTest::IS_A, 'lhTogglWorkspace')],
                ['/^Ghjsdfasdfa/u', NULL],
            ],
            'getCurrentTimeEntry' => '_testGetCurrentTimeEntry'
        ];
    }
    
}
