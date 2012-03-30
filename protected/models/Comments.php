<?php

/**
 * This is the model class for table "comments".
 *
 * The followings are the available columns in table 'comments':
 * @property integer $id
 * @property integer $linkid
 * @property string $date
 * @property string $text
 * @property string $user
 * @property integer $userid
 * @property string $ip
 */
class Comments extends CActiveRecord
{
    public $title = '';
    public $foto = false;
    public $captcha = null;
    
    /**
     * Returns the static model of the specified AR class.
     * @return Comments the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'comments';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            // Общие
            array('text, user', 'required','message' => '{attribute} не может быть пустым'),
            array('text', 'length', 'min' => 3, 'max' => 512, 'message' => 'Длина комментария должна быть от 3 до 512 символов'),
            array('user', 'length', 'min' => 3, 'max' => 32, 'message' => 'Длина ника должна быть от 3 до 32 символов'),
            array('user', 'match', 'pattern' => '/^[A-Za-z0-9А-Яа-я\s]+$/i','message' => 'Ник содержит недопустимые символы'),
            array('linkid','exist','allowEmpty' => false, 'attributeName' => 'id', 'className' => 'Links', 'message' => '{attribute} не существует'),
            array('captcha', 'captcha', 'allowEmpty'=>(!extension_loaded('gd') || (!Yii::app()->user->isGuest)), 'on' => 'add', 'message' => 'Неверный код подтверждения'),
           
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, linkid, date, text, user, userid, ip', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
          
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'linkid' => 'Сайт',
            'date' => 'Дата добавления',
            'text' => 'Сообщение',
            'user' => 'Ник',
            'userid' => 'Userid',
            'ip' => 'Ip',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id);

        $criteria->compare('linkid',$this->linkid);

        $criteria->compare('date',$this->date,true);

        $criteria->compare('text',$this->text,true);

        $criteria->compare('user',$this->user,true);

        $criteria->compare('userid',$this->userid);

        $criteria->compare('ip',$this->ip,true);

        return new CActiveDataProvider(get_class($this), array(
            'criteria'=>$criteria,
        ));
    }
    
    public static function getLastComments($limit=3){
        $limit = intval($limit);
        if(!$limit || $limit>10){
            $limit = 3;
        }
       // TODO переписать на AR
       return self::model()->findAllBySql(
             'SELECT c.*,
                     l.`title`, 
                     l.`foto`
              FROM  comments c, 
                    links l 
              WHERE c.linkid=l.id AND
                    l.visible = 1
              ORDER BY c.id DESC
              LIMIT '.$limit
        );
    }
    
    public static  function getLinkComments($link){
        $link = intval($link);
        if(!$link){
            return false;
        }
        return Comments::model()->findAll(array(
            'condition'=>'linkid = :l',
            'params'=>array(':l'=>$link),
            'order'=>'id'
        ));
    }
}