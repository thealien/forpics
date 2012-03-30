<?php

$dev_ip = array('192.168.1.103', '10.64.80.246', '192.168.1.100');

if(!in_array($_SERVER['REMOTE_ADDR'], $dev_ip)){
	header('Location: http://forpics.ru');
	exit();
}

if(strpos($_SERVER['SERVER_NAME'], 'dev.')===0){
    ini_set('display_errors', 1);
    // Dev
    defined('YII_DEBUG') or define('YII_DEBUG',true);
    defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
    $config=dirname(__FILE__).'/protected/config/dev.php';
}
else{
    // Prod
    $config=dirname(__FILE__).'/protected/config/prod.php';
}

$yii=dirname(__FILE__).'/../yii.framework/yii.php';
require_once($yii);
Yii::createWebApplication($config)->run();
