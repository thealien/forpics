<?php
// Клас расширение для Twig


class My_Twig_Extension extends Twig_Extension
{
    public function getName(){
        return __CLASS__;
    }
    
    public function getFilters()
    {
        return array(
            'nl2br'         => new Twig_Filter_Function( __class__ . '::nlbr'),
			'nl2div'         => new Twig_Filter_Function( __class__ . '::nldiv'),
            'cut'           => new Twig_Filter_Function( __class__ . '::cut'),
            'url_translit'  => new Twig_Filter_Function( __class__ . '::url_translit'),
            'auto_link'     => new Twig_Filter_Function( __class__ . '::auto_link'),
            'world_ending'  => new Twig_Filter_Function( __class__ . '::world_ending'),
			'date_format' => new Twig_Filter_Function( __class__ . '::dateFormat'),
			'format_filesize'  => new Twig_Filter_Function( __class__ . '::format_filesize'),
        );
    }
    
    static private function postparse_build_link($url, $before=''){
            $after  = '';
            if( preg_match('/(javascript|vbscript)/', $url) ) {
                return $before.$url.$after;
            }
            if( preg_match('/([\.,\?]|&#33;)$/', $url, $matches) ) {
                $after  .= $matches[1];
                $url    = preg_replace('/([\.,\?]|&#33;)$/', '', $url);
            }
            $txt    = $url;
            if( strlen($txt) > 60 ) {
                $txt    = substr($txt, 0, 45).'...'.substr($txt, -10);
            }
            return $before.'<a href="'.$url.'" title="'.$url.'" target="_blank" rel="nofollow">'.$txt.'</a>'.$after;
        }
        
    static function auto_link($message){
        if( FALSE!==strpos($message,'http://') || FALSE!==strpos($message,'https://') || FALSE!==strpos($message,'ftp://') ) {
                $message    = preg_replace('#(^|\s)((http|https|ftp)://[\wа-я]+[^\s\[\]]+)#ie', 'self::postparse_build_link("\\2", "\\1")', $message);
        }
        return $message;
    }
    
    static function url_translit($s, $lim = false){
        if($lim && $lim > 0){
            $lim = intval($lim);
            $s = substr($s, 0, $lim);
        }
        $s = urlencode(self::translitIt($s));
        $s = preg_replace('/([^a-zA-Zа-яА-Я._-])/i', '',$s);
        return $s;
}

    static function translitIt($str){
        $tr = array(
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
        "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
        "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
        "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
        "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
        "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
        "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
        "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
        "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
        "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
        "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"," "=>"."
        );
        return strtr($str,$tr);
    }
    
    static function nlbr($s){
        return str_replace("\r", "<br>", $s);
    }
	static function nldiv($s){
		return str_replace("\n", '<br class="br">', $s);
    }
    
    static function cut($s, $l = 80){
    	return (mb_strlen($s)>$l) ? mb_substr($s, 0,$l, Yii::app()->charset) . '...' : $s;
        return (strlen($s)>$l) ? substr($s, 0,$l) . '...' : $s;
    }
    
    static function world_ending($digit,$expr,$onlyword=false)
    {
        if(!is_array($expr)) $expr = array_filter(explode(' ', $expr));
        if(empty($expr[2])) $expr[2]=$expr[1];
        $i=preg_replace('/[^0-9]+/s','',$digit)%100;
        if($onlyword) $digit='';
        if($i>=5 && $i<=20) $res=$digit.' '.$expr[2];
        else
        {
            $i%=10;
            if($i==1) $res=$digit.' '.$expr[0];
            elseif($i>=2 && $i<=4) $res=$digit.' '.$expr[1];
            else $res=$digit.' '.$expr[2];
        }
        return trim($res);
    }
	
	/**
     * Функция форматирования даты
     * @return string 
     */
    static function dateFormat($date = false, $format = false, $without_time = false) {
        $months = array(
            'Jan' => 'января',
            'Feb' => 'февраля',
            'Mar' => 'марта',
            'Apr' => 'апреля',
            'May' => 'мая',
            'Jun' => 'июня',
            'Jul' => 'июля',
            'Augt' => 'августа',
            'Sep' => 'сентября',
            'Oct' => 'октября',
            'Nov' => 'ноября',
            'Dec' => 'декабря'
        );
        $def_format = 'H:i, d.m.y';
        $format = ($format===false) ? $def_format : strval($format);
        if($date === false) $date = time();
        $date = is_numeric($date) ? $date : strtotime($date);
        $resdate = '';
        switch(date('Y-m-d',$date)) {
            case date('Y-m-d'):
                $resdate = 'Сегодня' . ($without_time?'':' в ' . date("H:i", $date));
                break;
            case date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")) ):
                $resdate = 'Вчера'  . ($without_time?'':' в ' . date("H:i", $date));
                break;
            case date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")) ):
                $resdate = 'Завтра'  . ($without_time?'':' в ' . date("H:i", $date));
                break;
            default:
                $resdate = str_ireplace(array_keys($months), $months,  date($format, $date));
        }
        return $resdate;
    }
	
	static function format_filesize($filesize){
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
    
}
