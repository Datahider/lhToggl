<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of lhTogglClass
 *
 * @author user
 */
abstract class lhTogglClass extends lhSelfTestingClass {
    const REGESC = "/([{}\[\]()^\$.|*+?\\<>\/])/";

    protected $api;
    
    function __construct() {
        $this->api = lhTogglApi::api();
    }

}
