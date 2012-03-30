<?php

class ImageValidator implements Validator {

    protected $fileValidator = null;

    public $params = array(
        'max_width'     => false,
        'max_height'    => false,
        'max_size'      => false,
        'allowed_ext'   => false,
        'allowed_mime'  => false
    );

    public function init($params = array()){
        $this->fileValidator = new FileValidator(array(
            'max_size'      => $this->getParam('max_size'),
            'allowed_ext'   => $this->getParam('allowed_ext'),
            'allowed_mime'  => $this->getParam('allowed_mime')
        ));
    }

    public function validate($filename, $clientFilename = null){
        try{
            $this->fileValidator->validate($filename, $clientFilename);
        }
        catch(FileValidatorException $e){
            throw new ImageValidatorException($e->getMessage());
        }
        if(!$this->isImage($filename))
            throw new ImageValidatorException('Файл не является изображением.');

        $this->validateSize($filename);
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
                throw new ImageValidatorException('Переданный параметр '.$k.' не допустим');
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

    protected function isImage($file){
        if (is_file($file) and $r = @getimagesize($file)) {
            if (@$r[2] && in_array($r[2], array(1,2,3,6))) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function validateSize($filename){
        $this->validateWidth($filename);
        $this->validateHeight($filename);
        return true;
    }

    protected function validateWidth($filename){
        $maxWidth = $this->getParam('max_width');
        if($maxWidth === false) return true;
        $maxWidth = intval($maxWidth);
        $imagesize = @getimagesize($filename);
        if($imagesize && isset($imagesize[0])){
            if($imagesize[0] <= $maxWidth)
                return true;
        }
        throw new ImageValidatorException('Ширина изображения превышает '.$maxWidth.'px');
        return false;
    }

    protected function validateHeight($filename){
        $maxHeight = $this->getParam('max_height');
        if($maxHeight === false) return true;
        $maxHeight = intval($maxHeight);
        $imagesize = @getimagesize($filename);
        if($imagesize && isset($imagesize[1])){
           if($imagesize[1] <= $maxHeight)
               return true;
        }
        throw new ImageValidatorException('Высота изображения превышает '.$maxHeight.'px');
        return false;
    }
}

