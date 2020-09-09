<?php

/**
 * Description of lhTogglTimeEntry
 *
 * @author user
 */
class lhTogglTimeEntry extends lhTogglEntity {
    const API_FUNC = 'time_entries';
    const JSON_NAME = 'time_entry';
    const CREATED_WITH = 'lhToggl API Library (info@losthost.online)';
    
    public function start(lhTogglProject $_project, $name='') {
        $r = $this->api->apiCall(lhTogglTimeEntry::API_FUNC, 'start', [
            lhTogglTimeEntry::JSON_NAME => [
                'pid' => $_project->data->id,
                'created_with' => 'lhToggl API Library (info@losthost.online)',
                'start' => (new DateTime)->format(DATE_ATOM),
                'description' => $name
            ]
        ]);
        if (!isset($r->data)) {
            throw new Exception("Did not get data for started time entry");
        }
        $this->data = $r->data;
    }
    
    public function stop() {
        $r = $this->api->apiCall(
                lhTogglTimeEntry::API_FUNC, 
                $this->_t("%s/%s", $this->data->id, 'stop'),
                ''
            );
        if (!isset($r->data)) {
            throw new Exception("Did not get data for the time entry");
        }
        if ($r->data->id != $this->data->id) {
            throw new Exception($this->_t("Got id=%s but awaiting %s", $r->data->id, $this->data->id));
        }
        $this->data = $r->data;
    }  
    
    public function mycreate(lhTogglProject $_project, $start, $duration, $name='', $tags=[]) {
        $r = $this->api->apiCall(lhTogglTimeEntry::API_FUNC, '', [
            lhTogglTimeEntry::JSON_NAME => [
                'pid' => $_project->data->id,
                'created_with' => self::CREATED_WITH,
                'start' => (new DateTime)->format(DATE_ATOM),
                'duration' => $duration,
                'tags' => $tags,
                'description' => $name
            ]
        ]);
    }
    
    public function create($data) {
        if (!isset($data['created_with'])) {
            $data['created_with'] = self::CREATED_WITH;
        }
        parent::create($data);
    }
}
