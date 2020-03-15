<?php

/**
 * Description of lhTogglProject
 *
 * @author user
 */
class lhTogglProject extends lhTogglEntity {
    const API_FUNC = 'projects';
    const JSON_NAME = 'project';
    
    protected function _test_update() {
        $name = 'Project set 002 ' . uniqid();
        $this->data->name = $name;
        
        $this->update();
        $this->load();
        if ($this->data->name != $name) {
            throw new Exception($this->_t("Update was insuccessful. Current name is %s", $this->data->name));
        }
    }
    
    protected function _test_delete() {
        $my_id = $this->data->id;
        $this->delete();
        
        try {
            $this->load($my_id);
        } catch (Exception $e) {
            if ($e->getCode() != -10004 ) {
                throw new Exception("Awaiting code -10004. Got ", $e->getCode(), -907);
            }
        }
        
    }

    protected function _test_data() {
        $name = "Same name ".uniqid();
        return [
            'load' => '_test_skip_',                // Протестировано при создании экземпляра класса
            'create' => [                           // Потестим создание проектов с одинаковым именем
                [['name' => $name], NULL],
                [['name' => $name], new Exception("Name already taken", -10002)],
            ],
            'update' => '_test_update',
            'delete' => '_test_delete',
        ];
    }
}
