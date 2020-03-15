<?php

/**
 * Description of lhTogglClient
 *
 * @author user
 */
class lhTogglClient extends lhTogglEntity {
    const API_FUNC = 'clients';
    const JSON_NAME = 'client';
    
    protected function _test_update() {
        $name = 'Test set 002 ' . uniqid();
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
        return [
            'load' => '_test_skip_',              // Протестировано при создании экземпляра класса
            'create' => '_test_skip_',            // Протестировано при создании экземпляра класса
            'update' => '_test_update',
            'delete' => '_test_delete',
        ];
    }
}
