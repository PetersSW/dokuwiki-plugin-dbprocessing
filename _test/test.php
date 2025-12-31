<?php
    $data = array('form'=>'FORM');
    if(!array_key_exists('form', $data)) $data['form'] = 'NULL';
    var_dump($data['form']);
?>