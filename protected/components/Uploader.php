<?PHP

class UploaderException extends Exception{}

class Uploader {

    private $uid = 0;

    protected $processor = null;

    protected $validator = null;

    public $params = array(
        'service_url'       =>'',
        'db_images_table'   =>'images',     // Имя таблицы картинок
        
        // Пути
        'path_date'     => '',                 // папка для сегодняшних картинок
        'path_images'   => 'i',          // имя папки с картинками
        'path_preview'  => 'p',             // имя папки с эскизами
        'path_fakes'    => 'z',             // папка с подозрительными файлами (не совпадение расширения и контента)(слеш в конце обязателен)
        'path_tmp'      => '/tmp',          // папка для временного хранение картинок (Распаковка zip и удаленная загрузка по URL)(слеш в конце обязателен)
        
        'max_image_filesize'    =>3145728,  // 3 мБ
        'max_urls_upload'       =>5,        // ограничение одновременное кол-во заливаемых картинок по URL
        // Опции модификации изображения
        'image_normalize'       =>false,    // Нормализовать картинку
        'image_resize'          =>false,    // array('w'=>xxx, 'h'=>xxx)
        'image_rotate'          =>false,    // Поворачивать картинку
        'image_title'           =>false,    // Накладывать текст на картинку
        'image_preview'         =>false,    // Создавать эскиз
        'image_quality'         =>90,       // Качество получаемых картинок
        
        'group' => '',

        'ex_convert'            => false    //  Полный путь до утилиты Convert 
    );

    /*
    public $allow_images_ext = array(
        '.jpg',
        '.jpeg',
        '.gif',
        '.png'
    );
   */

    private $uploading_by_url = 0;

    private $useragent = 0;
    protected $useragents = array(
        'ForPicsUploader' => 1
    );

    public $result = array();

    public $uploaded = 0;

    public $last_guid = '';

    public $last_group = '';


    
    public function __construct($uid, $params = array()){
        $this->uid = intval($uid);
        $this->initParams($params);
        $this->initUserAgent();
    }

    public function setProcessor($processor){
        $this->processor = $processor;
        return $this;
    }

    public function setValidator(ImageValidator $validator){
        $this->validator = $validator;
        return $this;
    }

    /**
     * @return bool
     */
    protected function initUserAgent(){
        if(!isset($_SERVER['HTTP_USER_AGENT'])) return false;
        $ua = $_SERVER['HTTP_USER_AGENT'];
        foreach($this->useragents as $name=>$id){
            if (!(strpos($ua, $name)===FALSE)){
                $this->useragent = $id;
                return true;
            }
        }
        return true;
    }
    
    public function tryUpload($files = 'uploadfile'){
        if(is_array($files)){
            //$this->uploadImagesFromURL($files);
        }
        elseif(is_string($files)){
            $this->uploadImagesFromPOST($files);
        }
        else{
            return false;
        }
        return $this->result;
    }

    protected function uploadImagesFromPOST($field){
        if(!isset($_FILES[$field]) || empty($_FILES[$field]))
            return false;
        $files = array();
        $post = $this->flipFilesArray($_FILES[$field]);
        foreach($post as $p){
            if($p['error']!==0){
                 $this->userMessage('Ошибка получения файла. Попробуйте снова.');
                 continue;
            }
            try {
                $this->validator->validate($p['tmp_name'], $p['name']);
            }
            catch(ImageValidatorException $e){
                $this->userMessage("Файл '".$p['name']."' пропущен: ".$e->getMessage());
                continue;
            }
            $files[] = $p;
        }
        if(empty($files))
            return false;
        $this->addImages($files);
        return true;
    }
    
    /**
     * Загрузка картинок по URL-адресам
     * @param array or string $url URL или список URL
     * @return bool
     */
    private function uploadImagesFromURL($url){
        $this->uploading_by_url = 0;
        $urls = array();
        if(is_array($url)){
            $urls = $url;           // передан список URL
        }
        elseif(is_string($url)){
            $url = trim($url);
            if(!$url){
                return false;       // передан один URL
            }
            $urls[] = $url;
        }
        else{
            $this->userMessage("Ошибочный URL картинки.");

            return false;
        }
        $filenames = array();

        // получим первые {$this->params['max_urls_upload']} адресов
        $urls = array_slice($urls, 0, intval($this->params['max_urls_upload']));
        if($urls){
            foreach($urls as $u){
                $u =  trim($u);
                $f = $this->uploadFileByURL($u);
                // Пробуем скачать удаленный файла
                if($f){
                    $filenames[]=array( 'tmp_name'=>$this->params['path_tmp'].'/'.$f,
                                        'name'=>$f
                                       );
                }
                else{
                    $this->userMessage("Ошибка загрузки по URL: {$u} .");
                }
            }
            if(!empty($filenames)){
                $this->addImages($filenames);
                return true;
            }
        }
        return false;   
    }
    
    /**
     * Применение переданных параметров
     * @param array $params массив пар ('параметр' => 'значение')
     * @return null
     */
    private function initParams($params){
        if(!is_array($params) || empty($params)){
            return;
        }
        foreach($params as $k=>$p){
            if(isset($this->params[$k])){
                $this->params[$k] = $params[$k]; 
            }
            else{
                throw new Exception('Переданный параметр '.$k.' не допустим');
            }
        }
    }

    /**
     * Определение размера удаленного файла
     * @param string $url url удаленного файла
     * @return int or FALSE
     */
    private function getFileSizeByURL($url){
        
        $fp = @fopen($url,"r");
        if(!is_resource($fp)){
            return false;   
        }
        $inf = stream_get_meta_data($fp);
        fclose($fp);
        if(!isset($inf["wrapper_data"]) || !is_array($inf["wrapper_data"])){
            return false;
        }

        foreach($inf["wrapper_data"] as $v){
            if (stristr($v,"content-length")){
                $v = explode(":",$v);
                return trim($v[1]);
            }
        }
        return false;
    }
    
    /**
     * Скачивание удаленного файла
     * @param string $url url удаленного файла
     * @param string $outfile имя файла для сохранения
     * @return bool
     */
    private function getFileByURL_socket($url,$outfile){
        // Если файл уже есть, выходим
        if(file_exists($outfile)){
            return false;
        }
        // Открываем на запись
        $destination = @fopen($outfile,"w"); 
        if(!is_resource($destination)){
            return false;
        }
        // Открываем на чтение
        $source = @fopen($url,"r");
        if(!is_resource($source)){
            return false;
        }
        // Лимит скачиваемого файла
        $maxsize = $this->params['max_image_filesize']; 
        $length = 0;
        while (($a=fread($source,1024))&&($length<$maxsize)){ 
            $length=$length+1024;
            fwrite($destination,$a); 
        } 
        // Если удачно закрываем хендлы
        if(fclose($source) && fclose($destination)){
            return true;
        };
        return false;
    }
    

    /*
    private function uploadFileByURL($url){
        $url = trim($url);
        // если нет протокала - добавить
        if (strstr($url,"http://")===FALSE) {
            $url="http://".$url;
        }
        if (strlen($url)>10){//если ссылка нормальная по длине
            //получаем расширение из url
            $ext = $this->getExt($url);
            if(in_array($ext,$this->allow_images_ext)){//расширение файла нас устраивает
                $filesize=$this->getFileSizeByURL($url);
              
                //проверка размера картинки
                if ($this->isValidSize($filesize)){
                    //размер файла нас устраивает   
                    $filename=$this->guid().$ext;
                    if($this->getFileByURL_socket($url,$this->params['path_tmp'].'/'.$filename)){
                        //удачная скачка удаленного файла
                        return $filename;
                    }
                    else{
                        $this->userMessage("Ошибка при получении файла по URL ({$url}).");
                    }
                }
                else{
                    $this->userMessage("Размер удаленно файла превысил ".($this->params['max_image_filesize']/1024/1024)." мБайт. Загрузка прервана.");
                }
            }
            else{
                $this->userMessage("Недопустимое расширение файла. Разрешены: ". join(',', $this->allow_images_ext));
            }
        }
        return false;
    }
    */
    private function addImages($files){
        //$files - это:
        //$files[index]['tmp_name']
        //$files[index]['name']
        if(!count($files))
            return false;
        if(count($files)>1)
            $this->params['group'] = $this->guid();
        foreach($files as $f){
            $this->addImage($f);
            unlink($f['tmp_name']);
        }
        return true;
    }
    
    /**
     * Обработка картинки и сохранение данных в БД
     * @param string $file имя файла картинки
     * @return bool
     */
    private function addImage($file){

        $imagesDir = $this->getImagesDir();
        $previewDir = $this->getPreviewDir();
        $guid = $this->guid();
        $filename = $guid.'.'. str_replace('jpeg','jpg', $this->getExt($file['name']));

        $imageFilePath = $imagesDir . $filename;
        $previewFilePath = $previewDir . $filename;

        if( copy($file['tmp_name'], $imageFilePath)         // Попытка скопировать
            && file_exists($imageFilePath)                  // Файл скопировался?
            && ($filesize = filesize($imageFilePath)) > 0)  // Конечный файл не пустой?
        {
            $originalImageInfo = @getimagesize($imageFilePath);

            $r = $this->processImage($imageFilePath, $previewFilePath);
            $changed = $r['changed'];
            $preview = $r['preview'];

            $deleteGuid = $this->guid();

            //инфа об исходной картинке
            $width      = $originalImageInfo[0];
            $height     = $originalImageInfo[1];
            $fsize      = $filesize;
            $filesize   = $this->formatFileSize($fsize);

            //инфа масштабированной/повернутой/.т.е. измененной картинке
            $newImageInfo = $changed ? $originalImageInfo : getimagesize($imageFilePath);
            $nwidth     = $newImageInfo[0];
            $nheight    = $newImageInfo[1];
            $nfsize     = filesize($imageFilePath);
            $nfilesize  = $this->formatFileSize($nfsize);
            //инфа об эскизе
            if($preview==1){
                $previewImageInfo = GetImageSize($previewFilePath);
                $pwidth     = $previewImageInfo[0];
                $pheight    = $previewImageInfo[1];
                $pfsize     = filesize($previewFilePath);
                $pfilesize  = $this->formatFileSize($pfsize);
            }

            $saved = $this->saveToDb(array(
                'filename'     => $filename,
                'ip'           => $_SERVER['REMOTE_ADDR'],
                'deleteGuid'   => $deleteGuid,
                'width'        => $width,
                'height'       => $height,
                'filesize'     => $nfsize,
                'preview'      => $preview,
                'originalfilename' => $file['name'],
                'guid'         => $guid
            ));
            if(!$saved)
                return false;

            $result = array();
            $result['url']          = $this->params['service_url'] . $imageFilePath;
            $result['delurl']       =   $this->params['service_url'].'delete/'.$this->params['path_date'].'/'.$deleteGuid;
            $result['width']        = $width;
            $result['height']       = $height;
            $result['filesize']     = $filesize;
            $result['filename']     = $filename;
            $result['deleteGuid']   = $deleteGuid;

            $result['changed'] = $changed;
            if($changed){
                $result['nwidth']       = $nwidth;
                $result['nheight']      = $nheight;
                $result['nfilesize']    = $nfilesize;
            }

            $result['preview'] = $preview;
            if($preview){
                $result['previewurl']   = $this->params['service_url'] . $previewFilePath;
                $result['pwidth']       = $pwidth;
                $result['pheight']      = $pheight;
                $result['pfilesize']    = $pfilesize;
            }

            $result['origfilename'] = $file['name'];
            $result['date']         = date("d.m.Y H:i:s");
            $result['path_date']    = $this->params['path_date'];
            $result['guid']         = $guid;
            $result['group']        = $this->last_group = $this->params['group'];

            $this->result[] = $result;
        }
        else{
            // Неудача при копировании в конечную папку
            return false;
        }
        $this->uploaded++;
        $this->last_guid = $guid;
        return true;
    }

    protected function processImage($imageFilePath, $previewFilePath){
        $changed = 0;
        $originalImageInfo = @getimagesize($imageFilePath);
        if(isset($originalImageInfo['channels']) && $originalImageInfo['channels']==4){
            $this->processor->cmykToRgb($imageFilePath, $imageFilePath);
        }

        if($this->params['image_resize']){
            if($this->processor->resize($imageFilePath, $imageFilePath,
                $this->params['image_resize']['w'],
                $this->params['image_resize']['h'],
                $this->params['image_quality']) == TRUE)
            {
                $changed = 1;
            }
        }

        if($this->params['image_rotate']){
            if($this->processor->rotate($imageFilePath, $imageFilePath, $this->params['image_rotate'])==TRUE) {
                $changed=1;
            }
        }

        if($this->params['image_normalize']){
            if($this->processor->normalize($imageFilePath, $imageFilePath)==TRUE){
                $changed=1;
            }
        }

        $preview=0;
        if($this->params['image_preview']){
            if($this->processor->resize($imageFilePath,  $previewFilePath,
                $this->params['image_preview'],
                $this->params['image_preview'],
                $this->params['image_quality']) == TRUE)
            {
                $preview=1;
            }
        }

        if($this->params['image_title']){
            if($this->processor->addText($imageFilePath, $imageFilePath, $this->params['image_title'], 10) == TRUE){
                $changed=1;
            }
            if($preview==1){
                if($this->processor->addText($previewFilePath, $previewFilePath, $this->params['image_title'], 10) == TRUE) {
                    $changed=1;
                }
            }
        }
        return array(
            'changed' => $changed,
            'preview' => $preview
        );
    }

    public function guid(){
        return substr(md5(uniqid(rand(), true)), 0, rand(7, 13));
    }
    
    public function formatFileSize($filesize){
        $kb = 1024;
        if($filesize<$kb){
            return $filesize." Б";
        }
        elseif($filesize<($kb=$kb*1024)){
            return sprintf("%01.1f",$filesize/1024)." КБ";
        }
        elseif($filesize<($kb=$kb*1024)){
            return sprintf("%01.2f",$filesize/(1024*1024))." МБ";
        }
        elseif($filesize<($kb=$kb*1024)){
            return sprintf("%01.2f",$filesize/(1024*1024*1024))." ГБ";
        }
        elseif($filesize<($kb=$kb*1024)){
            return sprintf("%01.2f",$filesize/(1024*1024*1024*1024))." ТБ";
        }
        return "0 байт";
    }

    private function getExt($filename){
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    private function flipFilesArray($arr){
        if(!is_array($arr) || empty($arr)){
            return false;
        }
        $arr2 = array();
        foreach($arr as $k=>$v){
            if(is_array($v)){
                foreach($v as $i=>$j){
                    $arr2[$i][$k] = $j;
                }   
            }
            else{
                $arr2[1][$k] = $v;
            }
            
        }
        return $arr2;
    }
    
    private function userMessage($m){
        $this->result[] = array('error' => $m);
        $_SESSION['messages'][] = $m;
    }

    protected function saveToDb(array $data){
        $image = new Image();
        $image->setAttributes(array(
            'filename'     => $data['filename'],
            'ip'           => $data['ip'],
            'deleteGuid'   => $data['deleteGuid'],
            'status'       => '0',
            'width'        => $data['width'],
            'height'       => $data['height'],
            'uploaduserid' => $this->uid,
            'filesize'     => $data['filesize'],
            'preview'      => $data['preview'],
            'originalfilename' => $data['originalfilename'],
            'fromurl'      => $this->uploading_by_url,
            'useragent'    => $this->useragent,
            'guid'         => $data['guid'],
            'path_date'    => $this->params['path_date'],
            'group'        => $this->params['group']
        ));
        if(!$image->save()){
            $this->userMessage("Ошибка. Попробуйте позже.");
            return false;
        }
        return true;
    }

    protected function getImagesDir(){
        return $this->params['path_images'] . '/' . $this->params['path_date'] . '/';
    }

    protected function getPreviewDir(){
        return $this->params['path_preview'] . '/' . $this->params['path_date'] . '/';
    }
}
?>