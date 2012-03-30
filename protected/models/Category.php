<?php

/**
 * This is the model class for table "category".
 *
 * The followings are the available columns in table 'category':
 * @property integer $id
 * @property integer $parentid
 * @property string $catname
 * @property string $ip
 * @property string $icon
 */
class Category extends CActiveRecord
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'category';
    }

    public function rules()
    {
        // Правила валидации
        return array(
            // Общие
            array('parentid, catname', 'required'),
            array('catname', 'match', 'pattern' => '/^[A-Za-z0-9А-Яа-я\s-.,]+$/i','message' => 'Название категории содержит недопустимые символы'),
            array('parentid', 'numerical', 'integerOnly'=>true),
            
            array('id, parentid, catname, ip', 'safe', 'on'=>'search'),
        );
    }

    public function relations()
    {
        return array(
            'parent'         =>array(self::BELONGS_TO, 'Category', 'parentid'),
            'linksCount'     =>array(self::STAT, 'Links', 'catid', 'condition' => 'visible=1'),
            'newLinksCount'  =>array(self::STAT, 'Links', 'catid', 'condition' => 'visible=1 AND (DATE_SUB(NOW(), INTERVAL 3 DAY)<date)'),
            /*'subcatsCount'   =>array(self::STAT, 'Category', 'parentid'),
            'subcats'        =>array(self::HAS_MANY, 'Category', 'parentid', 'order' => 'subcats.id'),*/
            'links'          =>array(self::HAS_MANY, 'Links', 'catid', 'condition' => 'visible=1', 'order' => 'links.id DESC'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id'         => 'ID категории',
            'parentid'   => 'ID родительской категории',
            'catname'    => 'Название категории',
            'ip'         => 'IP',
        );
    }

    public function search()
    {
        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id);

        $criteria->compare('parentid',$this->parentid);

        $criteria->compare('catname',$this->catname,true);

        $criteria->compare('ip',$this->ip,true);

        return new CActiveDataProvider(get_class($this), array(
            'criteria'=>$criteria,
        ));
    }
    
    public static function getRootCats($full = true){
        $cats = self::model()->cache(60, null, 3)->findAll(array(
            'condition' =>'t.parentid=:id', 'params'    =>array(':id'=>0),
            'order'      =>'t.parentid, t.catname ASC',
            'with' => $full ? array('linksCount', 'newLinksCount'/*, 'subcatsCount', 'subcats', 'subcats.linksCount'*/) : array()
        ));
        return $cats;
    }

}