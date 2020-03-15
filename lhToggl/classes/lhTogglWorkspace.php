<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of lhTogglWorkspace
 *
 * @author user
 */
class lhTogglWorkspace extends lhTogglEntity {
    const API_FUNC = 'workspaces';
    const JSON_NAME = 'workspace';
    
    private $clients;
    private $projects;
    
    public function clients() {
        if (!isset($this->clients)) {
            return $this->loadClients();
        }
        return $this->clients;
    }
    
    public function loadClients() {
        $r = $this->api->apiCall(lhTogglWorkspace::API_FUNC, $this->_t("%s/%s", $this->data->id, lhTogglClient::API_FUNC));
        if (!is_array($r)) {
            throw new Exception("Awaiting \$r to be an array. Got: ".print_r($r, TRUE));
        }
        $this->clients = [];
        foreach ($r as $client_data) {
            $this->clients[] = new lhTogglClient($client_data);
        }
        return $this->clients;
    }

    public function findClients($_regex, $_return_array=false) {
        $found = [];
        foreach ($this->clients() as $client) {
            if (preg_match($_regex, $client->data->name)) {
                $found[] = $client;
                if (!$_return_array) {
                    return $client;
                }
            }
        }
        if ($_return_array) {
            return $found;
        } else {
            return NULL;
        }
    }

    public function projects() {
        if (!isset($this->projects)) {
            return $this->loadProjects();
        }
        return $this->projects;
    }
    
    public function loadProjects() {
        $r = $this->api->apiCall(lhTogglWorkspace::API_FUNC, $this->_t("%s/%s", $this->data->id, lhTogglProject::API_FUNC));
        if (!is_array($r)) {
            throw new Exception("Awaiting \$r to be an array. Got: ".print_r($r, TRUE));
        }
        $this->projects = [];
        foreach ($r as $project_data) {
            $this->projects[] = new lhTogglProject($project_data);
        }
        return $this->projects;
    }

    public function findProjects($_regex, $_return_array=false) {
        $found = [];
        foreach ($this->projects() as $project) {
            if (preg_match($_regex, $project->data->name)) {
                $found[] = $project;
                if (!$_return_array) {
                    return $project;
                }
            }
        }
        if ($_return_array) {
            return $found;
        } else {
            return NULL;
        }
    }

    
    protected function _testUpdate() {
        //echo ".upstream handgs up."; return; // Закоментировать это, чтобы попробовать еще раз
        $old_name = $this->data->name;
        $this->data->name = "Моя красивая жизнь";
        $this->update();
        
        $this->data->name = '';
        $this->load($this->data->id);
        if ("Моя красивая жизнь" != $this->data->name) {
            throw new Exception("Ожидалось \"Моя красивая жизнь\", получено ".$this->data->name, -10002);
        }
        // Вернем как было
        $this->data->name = $old_name;
        $this->update();
        $this->data->name = '';
        $this->load();
        if ($old_name != $this->data->name) {
            throw new Exception("Ожидалось \"$old_name\", получено ".$this->data->name, -10002);
        }
    }
    
    
    protected function _test_data() {
        return [
            'update' => '_testUpdate',
            'load' => '_test_skip_',               // тестируется в update
            'create' => '_test_skip_',             // Наследуемый метод. Потестим на клиентах
            'delete' => '_test_skip_',             // Наследуемый метод. Потестим на клиентах
            'clients' => [
                [new lhTest(lhTest::IS_ARRAY)],
                [new lhTest(lhTest::ELEM_IS_A, 0, 'lhTogglClient')]
            ],
            'loadClients' => '_test_skip_',         // Протестировано в clients
            'findClients' => [
                ["/^Test set 001/", new lhTest(lhTest::IS_A, 'lhTogglClient')],
                ["/^Test set 001/", true, new lhTest(lhTest::IS_ARRAY, 'lhTogglClient')],
                ["/^Test set 001/", true, new lhTest(lhTest::ELEM_IS_A, 2, 'lhTogglClient')],
                ["/^Test set 000/", NULL],
                ["/^Test set 000/", true, []],
            ],
            'projects' => [
                [new lhTest(lhTest::IS_ARRAY)],
                [new lhTest(lhTest::ELEM_IS_A, 0, 'lhTogglProject')]
            ],
            'loadProjects' => '_test_skip_',         // Протестировано в projects
            'findProjects' => [
                ["/5e6e29a9bea2e/", new lhTest(lhTest::IS_A, 'lhTogglProject')],
                ["/^Тестовый проект \(набор 001\)/u", true, new lhTest(lhTest::IS_ARRAY, 'lhTogglProject')],
                ["/^Тестовый проект \(набор 001\)/u", true, new lhTest(lhTest::ELEM_IS_A, 0, 'lhTogglProject')],
                ["/^Test set 000/", NULL],
                ["/^Test set 000/", true, []],
            ],
        ];
    }
    
    protected function _test_call($func, ...$args) {
        return $this->$func(...$args);
    }

}
