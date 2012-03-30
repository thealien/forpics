<?php

class FileValidator implements Validator {

    public $params = array(
        'max_size'  => false,
        'allowed_ext'   => false,
        'allowed_mime'  => false
    );

    public function __construct($params = array()){
        try{
            $this->initParams($params);
        }
        catch(FileValidatorException $e){
            throw $e;
        }
    }

    protected function initParams($params){
        if(!is_array($params) || empty($params)){
            return;
        }
        foreach($params as $k=>$p){
            if($this->issetParam($k)){
                $this->setParam($k, $p);
            }
            else {
                throw new FileValidatorException('Переданный параметр '.$k.' не допустим');
            }
        }
    }

    protected function setParam($name, $value){
        $this->params[$name] = $value;
        return $this;
    }

    protected function getParam($name){
        if(isset($this->params[$name]))
            return $this->params[$name];
        return null;
    }

    protected function issetParam($name){
        return isset($this->params[$name]);
    }

    public function validate($filename, $clientFilename = null){
        if($clientFilename===null) $clientFilename = $filename;
        if(!file_exists($filename))
            throw new FileValidatorException('Файл не существует.');
        if(!is_readable($filename))
            throw new FileValidatorException('Файл не доступен для чтения.');
        $this->validateFilesize($filename);
        if(!$this->validateExt($clientFilename))
            throw new FileValidatorException('Запрещенный тип файла.');
        if(!$this->validateMime($filename))
            throw new FileValidatorException('Запрещенный тип файла.');
        return true;
    }

    protected function validateFilesize($filename){
        $maxSize = $this->getParam('max_size');
        if($maxSize === false)
            return true;
        $fs = filesize($filename);
        if($fs <= $maxSize)
            return true;
        throw new FileValidatorException("Файл слишком большой. Максимум:  " . $this->formatFilesize($maxSize));
    }

    protected function validateExt($filename){
        $exts = $this->getParam('allowed_ext');
        if(!is_array($exts)) return true;
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array(strtolower($ext), $exts);
    }

    protected function validateMime($filename){
        if(!function_exists('mime_content_type'))
            return true;
        $mimes = $this->getParam('allowed_mime');
        if(!is_array($mimes)) return true;
        $mime = mime_content_type($filename);
        return in_array(strtolower($mime), $mimes);
    }

    protected function formatFilesize($filesize){
        $kb = 1024;
        if($filesize<$kb)
            return $filesize." B";
        elseif($filesize<($kb=$kb*1024))
            return sprintf("%01.1f",$filesize/1024)." KB";
        elseif($filesize<($kb=$kb*1024))
            return sprintf("%01.2f",$filesize/(1024*1024))." MB";
        elseif($filesize<($kb=$kb*1024))
            return sprintf("%01.2f",$filesize/(1024*1024*1024))." GB";
        elseif($filesize<($kb=$kb*1024))
            return sprintf("%01.2f",$filesize/(1024*1024*1024*1024))." TB";
        return "0 байт";
    }

}

