<?php

interface ImageProcessor {

    public function resize($in, $out, $width, $height, $quality);

    public function rotate($in, $out, $degrees);

    public function normalize($in, $out);

    public function crop($in, $out, $size);

    public function addText($in, $out, $text, $color, $side);

}
