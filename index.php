<?php
date_default_timezone_set('Europe/Moscow');

require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/autoloader.php';
require_once __DIR__ . '/lhToggl/classes/lhTogglApi.php';

try {
    $tapi = new lhTogglApi("$correct_token");
    $tapi->_test();

    $ws_tested = FALSE;
    foreach ($tapi->workspaces() as $ws) {
        if ($ws->data->name == "Тестовый воркспейс") { 
            $ws->_test();
            $ws_tested = TRUE;
            break;
        }
    }
    echo "Workspace id=".$ws->data->id."\n";
    
    if (!$ws_tested) {
        throw new Exception("Не удалось найти тестовый воркспейс", -10007);
    }
    
    $cli = new lhTogglClient(['name' => 'Новый клиент '.  uniqid()]);
    echo $cli->data->id;
    $cli->_test();
    
    $prj = new lhTogglProject(['name' => 'Новый проект '. uniqid()]);
    echo $prj->data->id;
    $prj->_test();
    
    
    // Простой пример работы (предполагается что воркспейс по умолчанию уже выбран.
    $client = $ws->findClients("/^Very Big Company$/");
    if (!$client) {
        throw new Exception("Клиент не найден");
    }
    $project = new lhTogglProject(['name' => 'Тестовый проект (набор 002) '. uniqid(), 'cid' => $client->data->id]);
    $te = new lhTogglTimeEntry();
    $te->start($project);
    sleep(30);
    $te->stop();
    echo "Проверьте новую запись для проекта ".$project->data->name."\n";
    
} catch (Exception $e) {
    echo "\n\n";
    echo 'Ошибка '.$e->getCode();
    echo ' - '.$e->getMessage();
    echo "\n\nСтрока ".$e->getLine().' в файле '.$e->getFile();
    echo "\n\nТрассировка:\n".$e->getTraceAsString();
    echo "\n\nТЕСТИРОВАНИЕ ЗАВЕРШЕНО С ОШИБКОЙ";
}
