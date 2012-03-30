<?php

class ImageProcessorImagick implements ImageProcessor {

    protected $convert = null;

    public function __construct($convert_path = null){
        $this->convert = $convert_path;
        $this->init();
    }

    public function resize($in, $out, $width, $height, $quality){
        $cmd = sprintf('-depth 8 -resize "%dx%d>" -quality %d %s %s',
            $width, $height,
            $quality,
            escapeshellarg($in),
            escapeshellarg($out)
        );
        return $this->run($in, $out, $cmd);
    }

    public function rotate($in, $out, $degrees){
        $cmd=sprintf('-depth 8 -rotate %d %s %s',
            $degrees,
            escapeshellarg($in),
            escapeshellarg($out));
        return $this->run($in, $out, $cmd);
    }

    public function normalize($in, $out){
        $cmd = sprintf('-depth 8 %s -normalize -antialias %s',
            escapeshellarg($in),
            escapeshellarg($out)
        );
        return $this->run($in, $out, $cmd);
    }

    public function crop($in, $out, $size){
        $imginfo = getimagesize($in);
        if(!$imginfo){
            return false;
        }
        $w = $imginfo[0];
        $h = $imginfo[1];
        $x='x'.$size;
        if($h>$w){
            $x=$size.'x';
        }
        $cmd = sprintf('-depth 8 %s -resize %s -gravity Center -crop %dx%d+0+0 +repage %s',
            escapeshellarg($in),
            escapeshellarg($x),
            $size, $size,
            escapeshellarg($out)
        );
        return $this->run($in, $out, $cmd);
    }

    public function addText($in, $out, $text, $color, $side){
        $cmd = sprintf('-depth 8 %s -font verdanab.ttf -pointsize 20 -draw "gravity SouthWest fill gray text 0,2 %s fill white text 1,5 %s " %s',
            escapeshellarg($in),
            escapeshellarg($text),
            escapeshellarg($text),
            escapeshellarg($out)
        );
        return $this->run($in, $out, $cmd);
    }

    public function cmykToRgb($in, $out){
        $cmd = sprintf('-depth 8 %s -colorspace rgb %s',
            escapeshellarg($in),
            escapeshellarg($out)
        );
        return $this->run($in, $out, $cmd);
    }

    public function fromTo($in, $out){
        $cmd = sprintf('%s %s',
            escapeshellarg($in),
            escapeshellarg($out)
        );
        return $this->run($in, $out, $cmd);
    }

    protected function init(){
        if(!$this->convert){
            $this->convert = $this->findConvertUtil();
        }
        if(!file_exists($this->convert))
            throw new ImageProcessorImagickException('Convert not found');
    }

    protected  function findConvertUtil(){
        $paths = array(
            '/usr/bin/convert',
            '/usr/local/bin/convert',
            'c:\Program Files\ImageMagick\convert.exe'
        );

        foreach ($paths as $path){
            if (file_exists($path)){
                return $path;
            }
        }
        return false;
    }

    protected function run($in, $out, $cmd){
        $cmd = sprintf('%s ',escapeshellarg($this->convert)) . $cmd;
        $inMd5 = md5_file($in);
        $o = null;
        passthru($cmd, $o);
        $filesize = filesize($out);
        $outMd5 = md5_file($out);
        return !(($filesize==0) || ($inMd5 == $outMd5));
    }

}

class ImageProcessorImagickException extends Exception {

}
