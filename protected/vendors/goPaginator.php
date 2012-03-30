<?php
/**
 * Класс отрисовки пагинатора
 nn
 *
 * Основная задача: отрисовка пагинатора и помощь в составлении SQL-запроса
 * Документация: <link>http://blgo.ru/go/golibs/gopaginator/</link>
 *
 * @package   golibs (http://blgo.ru/go/golibs)
 * @author    Григорьев Олег aka vasa_c (http://blgo.ru/)
 * @version   1.0
 * @copyright Григорьев Олег aka vasa_c
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL
 * @docs      http://blgo.ru/go/golibs/gopaginator/
 */

class goPaginator
{

    /**
     * Конструктор
     *
     * @example $pager = new goPaginator(count($elements));
     * @exception RuntimeException нет элементов
     * @exception RuntimeException задан несуществующий параметр
     * @param int   $countElements общее количество разбиваемых элементов
     * @param array $params        [optional] параметры отрисовки
     */
    final public function __construct($countElements, $params = null)
    {
        $this->setCountElements($countElements);
        $this->loadParams(is_array($params) ? $params : array());
    }

    /**
     * Отрисовка пагинатора
     *
     * @example echo $pager->draw();
     * @staticvar string $cash кэш вёрстки
     * @return string html-вёрстка пагинатора
     */
    final public function draw()
    {
        $this->calcProperties();
        static $cash;
        if (TRUE || $cash === null) {
            $cash = $this->makeHtml();
        }        
        return $cash;
    }

    /**
     * Строковое представление - вёрстка пагинатора
     *
     * @example echo $pager;
     * @return string
     */
    final public function __toString()
    {
        return $this->draw();
    }

    /**
     * Установить значение параметра
     *
     * @example $pager->setParam('pageSize', 10);
     * @exception RuntimeException неверный параметр
     * @param string $name  
     * @param mixed  $value 
     */
    final public function setParam($name, $value)
    {
        if (!isset($this->params[$name])) {
            return $this->paramException($name);
        }
        $this->params[$name] = $value;
        return true;
    }

    /**
     * Получить значение параметра
     *
     * @exception RuntimeException неверный параметр
     * @param string $name
     * @return mixed 
     */
    final public function getParam($name)
    {
        if (!isset($this->params[$name])) {
            return $this->paramException($name);
        }
        return $this->params[$name];
    }

    /**
     * Сброс параметра к значению по умолчанию
     *
     * @param string $name
     */
    final public function resetParam($name)
    {
        if (!isset($this->params[$name])) {
            return $this->paramException($name);
        }
        $this->params[$name] = $this->paramsDefault[$name];
        return true;
    }

    /**
     * Указать количество элементов, разбиваемых на страницы
     *
     * @exception RuntimeException нет элементов
     * @param int $count
     */
    final public function setCountElements($count)
    {
        if ($count <= 0) {
            throw new RuntimeException('goPaginator: count='.$count);
        }
        $this->countElements = $count;
        return true;
    }

    /**
     * Получить количество элементов
     *
     * @return int
     */
    final public function getCountElements()
    {
        return $this->countElements;
    }

    /**
     * Получить номер текущей страницы
     *
     * @return int
     */
    final public function getCurrentPage()
    {
        $this->calcProperties();
        return $this->currentPage;
    }

    /**
     * Получить общее количество страниц
     *
     * @return int
     */
    final public function getCountPages()
    {
        $this->calcProperties();
        return $this->countPages;
    }

    /**
     * Получить размер текущей страницы
     * (У последней страницы размер может быть меньше, чем у остальных)
     *
     * @return int
     */
    final public function getSizeCurrentPage()
    {
        $this->calcProperties();
        return $this->sizeCurrentPage;
    }

    /**
     * Получить номер первого элемента на странице
     *
     * @return int
     */
    final public function getFirstElement()
    {
        $this->calcProperties();
        return $this->firstElement;
    }

    /**
     * Получить строку для вставки в секцию LIMIT SQL-запроса
     *
     * @example $sql = 'SELECT * FROM `table` LIMI '.$pager->getSqlLimits();
     * @return string
     */
    final public function getSqlLimits()
    {
        $this->calcProperties();
        return ($this->firstElement - 1).','.$this->sizeCurrentPage;
    }

  /*** PROTECTED: ***/

    /**
     * Формирование html-вёрстки пагинатора
     * @return string
     */
    protected function makeHtml()
    {
        $count = $this->countPages;
        $page  = $this->currentPage;
        if (($count == 1) && (!$this->params['drawOne'])) {
            return '';
        }
        $result = '';

        $solid = $this->params['solid'];
        $line  = $this->params['line'];
        $litag = $this->params['litag'];
        $m0    = intval(($line - 1) / 2);
        $m1    = $page - $m0;
        $m2    = $page + $m0;
        $ms    = $count - $line + 1;
        $p     = false;
        $sep   = $this->params['asep'];
        for ($i = 1; $i <= $count; $i++) {
            if (!$solid) {
                if (($i > $line) && ($i < $ms) && (($i < $m1) || ($i > $m2))) {
                    if (!$p) {
                        $p = true;
                        if ($this->params['space']) {
                            $result .= '<'.$litag.' class="space">'.$this->params['space'].'</'.$litag.'>';
                        }
                    }
                    continue;
                }
                $p = false;
            }
            $a = '<a href="'.$this->makePageLink($i).'">'.$i.'</a>';
            $result .= '<'.$litag.(($i == $page) ? ' class="current"' : '').'>'.$a.'</'.$litag.'>'.$sep;
        }
        $listtag = $this->params['listtag'];
        if ($listtag) {
            $result = '<'.$listtag.' class="list">'.$result.'</'.$listtag.'>';
        }

        if (($this->params['arr']) && ($count > 1)) {
            $larr = '&larr;';
            if ($page > 1) {
                $larr = '<a href="'.$this->makePageLink($page - 1).'">'.$larr.'</a>';
            }
            $rarr = '&rarr;';
            if ($page < $count) {
                $rarr = '<a href="'.$this->makePageLink($page + 1).'">'.$rarr.'</a>';
            }
            $result =
                '<span class="arr">'.$larr.'</span>'.
                $result.
                '<span class="arr">'.$rarr.'</span>';
        } 
        if ($this->params['title']) {
            $result = '<span class="title">'.$this->params['title'].'</span>'.$result;
        }
        if ($this->params['tag']) {
            $tag = '<'.$this->params['tag'].($this->params['css'] ? ' class="'.$this->params['css'].'"' : '').'>';
            $result = $tag.$result.'</'.$this->params['tag'].'>';
        }
        return $result;
    }

    /**
     * Определить текущую страницу
     * @return int должен возвращать целый номер страницы не меньше 1
     */
    protected function defineCurrentPage()
    {
        if ($this->params['currentPage']) {
            return $this->params['currentPage'];
        }
        $varname = $this->params['pageVar'];
        if (!isset($_GET[$varname])) {
            return 1;
        }
        $page = $_GET[$varname];
        if (!is_scalar($page)) {
            return 1;
        }
        $page = intval($page);
        if ($page < 1) {
            return 1;
        }
        return $page;
    }

    /**
     * Сформировать ссылку на страницу
     * @param int $pageNumber номер страницы
     * @return strin ссылка на неё
     */
    protected function makePageLink($pageNumber)
    {
        return $this->urlPrefix.$pageNumber.$this->urlPostfix;
    }

  /*** PRIVATE: ***/

    /**
     * Сформировать некоторые параметры
     */
    private function calcProperties()
    {
        if ($this->currentPage) {
            return true;
        }
        $size  = $this->params['pageSize'];
        $count = intval(($this->countElements - 1) / $size) + 1;
        if (($this->params['maxPage']) && ($count > $this->params['maxPage'])) {
            $count = $this->params['maxPage'];
        }
        $page  =  $this->defineCurrentPage();

        if ($page < 1) {
            $page = 1;
        } elseif ($page > $count) {
            $page = $count;
        }

        if ($this->params['urlPrefix'] !== false) {
            $urlPrefix  = $this->params['urlPrefix'];
            $urlPostfix = $this->params['urlPostfix'];
        } else {
            $g = $_GET;
            $varname = $this->params['pageVar'];
            unset($g[$varname]);
            if (count($g) > 0) {
                $urlPrefix = '?'.http_build_query($g).'&'.$varname.'=';
            } else {
                $urlPrefix = '?'.$varname.'=';
            }
            $urlPostfix = '';
        }
        $this->currentPage  = $page;
        $this->countPages   = $count;
        $this->firstElement = ($page - 1) * $size + 1;
        if ($page < $count) {
            $this->sizeCurrentPage = $size;
        } else {
            $this->sizeCurrentPage = $this->countElements - ($page - 1) * $size;
        }
        $this->urlPrefix  = $urlPrefix;
        $this->urlPostfix = $urlPostfix;
        return true;
    }

    /**
     * Загрузка параметров
     *
     * @exception RuntimeException неверное имя параметра
     * @param array $params
     */
    private function loadParams($params)
    {
        $result = $this->paramsDefault;
        foreach ($params as $name => $value) {
            if (!isset($result[$name])) {
                print_r($result);
                return $this->paramException($name);
            }
            $result[$name] = $value;
        }
        $this->params = $result;
        return true;
    }

    /**
     * Выбросить исключение о неверном параметре
     *
     * @exception RuntimeException
     * @param string $name имя неверного параметра
     */
    private function paramException($name)
    {
        throw new RuntimeException('Error goPaginator param "'.$name.'"');
    }

  /*** VARS: ***/

    /**
     * Параметры отрисовки
     * @var array
     */
    private $params = array();

    /**
     * Значение параметров отрисовки по умолчанию
     * @var array
     */
    private $paramsDefault = array(
        'pageSize'    => 20,
        'pageVar'     => 'page',
        'maxPage'     => 100,
        'currentPage' => false,
        'drawOne'     => false,
        'title'       => 'Страницы:',
        'arr'         => true,
        'solid'       => false,
        'line'        => 3,
        'space'       => '...',
        'listtag'     => false,
        'litag'       => 'span',
        'asep'        => '',
        'tag'         => 'div',
        'css'         => 'paginator',
        'urlPrefix'   => false,
        'urlPostfix'  => false,
    );

    /**
     * Количество элементов
     * @var int 
     */
    private $countElements;

    /**
     * Номер текущей страницы
     * @var int
     */
    private $currentPage;

    /**
     * Общее количество страниц
     * @var int
     */
    private $countPages;

    /**
     * Первый элемент на странице
     * @var int
     */
    private $firstElement;

    /**
     * Размер текущей страницы
     * @var int
     */
    private $sizeCurrentPage;

    /**
     * Префикс URL для дописывания page
     * @var string
     */
    private $urlPrefix;

    /**
     * Постфикс URL для дописываения page
     * @var string
     */
    private $urlPostfix;
}

?>
