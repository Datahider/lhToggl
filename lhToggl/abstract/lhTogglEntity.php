<?php

/**
 * Абстрактный класс определяющий все сущности toggl и методы работы с ними
 *
 * @author user
 */
class lhTogglEntity extends lhTogglClass {
    const API_FUNC = '';
    const JSON_NAME = '';
    
    public $data;
    
    public function __construct($data=NULL) {
        parent::__construct();

        if ($data === NULL) {
            // создание пустого объекта для загрузки
            // Do nothing
        } elseif (is_scalar($data) && ((int)$data == $data)) {
            // Передан ID для загрузки клиента из toggl
            $this->load($data);
        } elseif (is_array($data) || is_a($data, 'stdClass')) {
            // Передан массив или stdClass для создания или загрузки
            $data = json_decode(json_encode($data));
            if (isset($data->id)) {
                $this->data = $data;
            } else {
                $this->create($data);
            }
        } else {
            throw new Exception($this->_t("Invalid parameter data: ", $data));
        }
    }
            
    public function load($id=FALSE) { // Загрузка сущности из toggl 
        $class = get_class($this);
        $func = $class::API_FUNC;
        
        if (!$id && !isset($this->data->id)) {
            throw new Exception("Have no ID for load", -10007);
        }
        if (!$id) {
            $id = $this->data->id;
        }
        $r = $this->api->apiCall($func, $id);
        $this->data = $r->data;
    }

    public function update() {
        $class = get_class($this);
        $func = $class::API_FUNC;
        $name = $class::JSON_NAME;
      
        $r = $this->api->apiCall($func, $this->data->id, [ $name => $this->data ]);
        
        if ( $r->data->id != $this->data->id) {
            throw new Exception($this->_t("Got an object with wrong id from upstream: %s", $r));
        }
    }
    
    public function create($data) {
        $class = get_class($this);
        $func = $class::API_FUNC;
        $name = $class::JSON_NAME;
        
        $r = $this->api->apiCall($func, false, [ $name => $data ]);

        $this->load($r->data->id);
    }
    
    public function delete() {
        $class = get_class($this);
        $func = $class::API_FUNC;
        $name = $class::JSON_NAME;
        
        $r = $this->api->apiCall($func, $this->data->id, lhTogglApi::TOGGL_API_DELETE);

        unset($this->data);
    }
}
