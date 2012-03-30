<?php

class FPConfig {
    
    static function checkOrCreate($dir){
        $cache_key = 'dir_exists_' . $dir;
        $res = Yii::app()->cache->get($cache_key);
        if($res===true){
            return true;
        } 
        if(!file_exists($dir)){
            $res = mkdir($dir, 0755);
            Yii::app()->cache->set($cache_key, $res);
            return $res;
        }
        else{
            $res = true;
        }
        Yii::app()->cache->set($cache_key, $res);
        return true;
    }
    /**
     * 
     * @return 
     */
    static function load(){
        Yii::app()->params['url'] = 'http://' . $_SERVER['SERVER_NAME'] . '/'; 
        
        $dirs = array();
        $dirs['path_ih'] = realpath(Yii::app()->basePath.'/..');
        
        $date_foldername = date('Ymd');
        $dirs['path_date'] = date('Ymd');
        $dirs['path_images'] = 'i';
        $dirs['path_preview'] = 'p';
        $dirs['path_fakes'] = 'z';
        $dirs['path_tmp'] = sys_get_temp_dir();
        Yii::app()->params['dirs'] = $dirs;

        //разрешения для масштабирования
        $resizeval=array();
        $resizeval[]=array('w'=>'нет','h'=>'');// <=не удалять
        $resizeval[]=array('w'=>'320','h'=>'240');
        $resizeval[]=array('w'=>'640','h'=>'480');
        $resizeval[]=array('w'=>'800','h'=>'600');
        $resizeval[]=array('w'=>'1024','h'=>'768');
        $resizeval[]=array('w'=>'1280','h'=>'1024');
        $resizeval[]=array('w'=>'1600','h'=>'1200');
        Yii::app()->params['resizeval'] = $resizeval;
        //градусы для поворота
        $rotateval=array();
        $rotateval[]=array('desc'=>'нет','val'=>'0');// <=не удалять
        $rotateval[]=array('desc'=>'вправо','val'=>'+90');
        $rotateval[]=array('desc'=>'влево','val'=>'-90');
        $rotateval[]=array('desc'=>'вверх ногами','val'=>'180');
        Yii::app()->params['rotateval'] = $rotateval;
        //размеры превьюшек
        $previewval = array();
        $previewval[]=array('desc'=>'нет','val'=>'0');// <=не удалять
        $previewval[]=array('desc'=>'100','val'=>'100');
        $previewval[]=array('desc'=>'150','val'=>'150');
        $previewval[]=array('desc'=>'200','val'=>'200');
        $previewval[]=array('desc'=>'250','val'=>'250');
        $previewval[]=array('desc'=>'300','val'=>'300');
        $previewval[]=array('desc'=>'400','val'=>'400');
        $previewval[]=array('desc'=>'500','val'=>'500');
        Yii::app()->params['previewval'] = $previewval;
        
        //максимальная длина накладываемого на картинку текста
        Yii::app()->params['maxtitlelen'] = $maxtitlelen = 50;
        if( isset($_POST['passport_login']) && 
            isset($_POST['passport_pass']))
        {
            
        }
    }
    /**
     * Получение опций заливки
     * @return 
     */
    static function getUploadOptions(){
        $options = array();
        //нормализация
        if(isset($_POST['normalize'])){
            $options['image_normalize'] = true; 
        }
        //масштаб
        if(isset($_POST['resize'])){
            $resize=intval($_POST['resize']);
            if($resize && isset(Yii::app()->params['resizeval'][$resize])){
                $options['image_resize'] = Yii::app()->params['resizeval'][$resize];  
            }
        }
        //поворот
        if(isset($_POST['rotate'])){
            $rotate=intval($_POST['rotate']);
            if($rotate && isset(Yii::app()->params['rotateval'][$rotate])){
                $options['image_rotate'] = Yii::app()->params['rotateval'][$rotate]['val'];   
            }
        }
        //наложение текста
        if(isset($_POST['title'])){
            $title = trim($_POST['title']);
            if(!empty($title) && !(strlen($title)>Yii::app()->params['maxtitlelen'])){
                if(get_magic_quotes_gpc()){
                    $title =stripslashes($title);
                }
                $options['image_title'] = $title;   
            };
        }
        //создание эскиза
		if(!isset($_POST['preview'])) $_POST['preview'] = 2;
        if(isset($_POST['preview'])){
            $preview=intval($_POST['preview']);
            if($preview && isset(Yii::app()->params['previewval'][$preview])){
                $options['image_preview'] = Yii::app()->params['previewval'][$preview]['val'];    
            }
        }
        self::checkOrCreate(Yii::app()->params['dirs']['path_ih'].'/'.Yii::app()->params['dirs']['path_images'].'/'.Yii::app()->params['dirs']['path_date']);
        self::checkOrCreate(Yii::app()->params['dirs']['path_ih'].'/'.Yii::app()->params['dirs']['path_preview'].'/'.Yii::app()->params['dirs']['path_date']);
        $options['path_date'] = Yii::app()->params['dirs']['path_date'];
        $options['path_tmp'] =Yii::app()->params['dirs']['path_tmp'];
        $options['service_url'] = Yii::app()->params['url'];
        return $options;
    }
        
}