<?php

return array(
    'basePath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
    'name' => 'forpics',
    'charset' => 'utf-8',
    'language' => 'ru',
    'defaultController' => 'main',
    'preload'=>array('log'),

    'import'=>array(
        'application.models.*',
        'application.components.*',
        'application.core.processors.*',
        'application.core.validators.*'
    ),

    'components'=>array(

        'user'=>array(
            'class' => 'EWebUser',
            'allowAutoLogin' => true,
            'identityCookie' => array(
                'domain' => $_SERVER['SERVER_NAME']
            ),
        ),

        'urlManager'=>array(
            'urlFormat'=>'path',
            'showScriptName'=>false,
            'rules'=>array(
                '' => 'main/index',
                'upload'=>'main/upload',
                'up'=>'main/webupload',
				'my/<page:\d+>'=>'main/my',
                'my'=>'main/my',
                'image/<path_date:\d+>/<guid:\w+>' => 'main/view',
                'images/<path_date:\d+>/<group:\w+>' => 'main/viewgroup',
				'delete/<path_date:\d+>/<guid:\w+>' => 'main/delete',
				'admin/<page:\d+>'=>'admin/index',
				
                '<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
                '<controller:\w+>/<action:\w+>/<id:.+>'=>'<controller>/<action>',
                '<controller:\w+>/<action:\w+>'=>'<controller>/<action>'
            ),
        ),
        
        'cache'=>array(
            'class'=>'system.caching.CMemCache',
            'servers'=>array(
                array('host'=>'127.0.0.1', 'port'=>11211)
            ),
        ),
        'db'=>array(
            'connectionString' => 'mysql:host=localhost;dbname=*****',
            'emulatePrepare' => false,
            'username' => '***',
            'password' => '****',
            'charset' => 'utf8',
            'enableProfiling' => true,
            'enableParamLogging' => true,
        ),
        
        'errorHandler'=>array(
            'errorAction'=>'main/error',
        ),

        'viewRenderer'=>array(
            'class'             =>'ext.yiiext.renderers.twig.ETwigViewRenderer',
            'options'           => array(
                'charset'           => 'utf-8',
                'trim_blocks'       => false,
                'strict_variables'  => false,
                'auto_reload'       => true,
                'autoescape'        => false,
                'minify'            => true,
                //'cache'             => false
            ),
            'extentions'        => array(
                'My_Twig_Extension' // file vendors/My_Twig_Extension.php must exists
            ),
        ),
		
		'widgetFactory' => array(
            'widgets' => array(
                'CLinkPager' => array(
                    'pageSize' => 15,
                    'nextPageLabel'=>'&rarr;',
                    'prevPageLabel'=>'&larr;',
                    'firstPageLabel'=> 'начало',
                    'lastPageLabel' => 'в конец',
                    'header' => '',
                    'cssFile' =>false
                ) 
            )
        ),

        'imager' => array(
            'class'     => 'application.components.EImager',
            'type'      => 'imagick',
            'params'    => array()
        ),

        'validator' => array(
            'class'     => 'application.core.validators.ImageValidator',
            'params' => array(
                'max_width'     => 10000,
                'max_height'    => 10000,
                'max_size'      => 3145728,
                'allowed_ext'   => array(
                    'jpg', 'jpeg', 'gif', 'png'
                ),
                'allowed_mime'  => array(
                    'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif'
                )
            )
        )

    ),
    

	
    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params'=>array(
        'adminEmail'=>'*****',
        'title' => 'Хостинг картинок - ForPics.ru',
        'resizeval' => array(),
        'rotateval' => array(),
        'previewval' => array(),
        'dirs' => array(),
        'maxtitlelen' => 50,
        'domain'=>$_SERVER['SERVER_NAME']
    ),
);
