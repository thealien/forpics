<?php

class EImager extends CApplicationComponent {

    public $type = null;
    public $params = array();

    protected $processor = null;

    public function init(){
        switch($this->type){
            case 'gd':
                $this->processor = new ImageProcessorGd($this->params);
                break;
            case 'imagick':
                $this->processor = new ImageProcessorImagick($this->params);
                break;
            default:
                throw new Exception('Unknown Imager type "'.$this->type.'"');
        }
    }

    public function __call($name, $params=array()){
        if(method_exists($this->processor, $name))
            return call_user_func_array(array($this->processor, $name), $params);
    }


}
