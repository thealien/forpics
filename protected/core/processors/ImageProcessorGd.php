<?php

class ImageProcessorGd implements ImageProcessor {

    public function resize($in, $out, $width, $height, $quality){

    }

    public function rotate($in, $out, $degrees){

    }

    public function normalize($in, $out){

    }

    public function crop($in, $out, $size){

    }

    public function addText($in, $out, $text, $color, $side){
        if(!is_file($infile) || !is_readable($infile)){
            return false;
        }
        $text = trim($text);
        if(!$text){ return false;}
        $ext=$this->getExt($infile);
        switch($ext):
            case('.gif'):
                $im=imagecreatefromgif($infile);
                break;
            case('.png'):
                $im=imagecreatefrompng($infile);
                break;
            case('.jpeg'):
            case('.jpg'):
                $im=imagecreatefromjpeg($infile);
                break;
            default: return false;
        endswitch;
        if(!is_resource($im)){
            return false;
        }
        $text=iconv('windows-1251','UTF-8',$text);
        $w = imagesx($im); $h = imagesy($im);
        if($h>49 && $w>99){
            $black = imagecolorallocate($im, 0 , 0, 0);
            $white  = imagecolorallocatealpha($im, 255, 255, 255, 70);
            // Закрашенный прямоугольник
            imagefilledrectangle($im,1,$h-26,$w-2,$h-2,$white);
            //текст
            imagettftext($im, $fontsize, 0, 5, $h-8, $black, 'verdanab.ttf', $text);
            switch($ext):
                case('.gif'):
                    $result = imagegif($im,$outfile);
                    break;
                case('.png'):
                    $result = imagepng($im,$outfile);
                    break;
                case('.jpeg'):
                case('.jpg'):
                    $result = imagejpeg($im,$outfile,100);
                    break;
                default: return false;
            endswitch;
            return $result;
        }
        return false;
    }

}
