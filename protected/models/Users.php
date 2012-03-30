<?php

/**
 * This is the model class for table "users".
 *
 * The followings are the available columns in table 'users':
 * @property integer $userID
 * @property string $username
 * @property string $password
 * @property string $email
 */
class Users extends CActiveRecord
{
    public $captcha = null;
    public $password2 = null;
    /**
     * Returns the static model of the specified AR class.
     * @return Users the static model class
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
        return 'users';
    }
    
    protected function beforeSave(){
        if(parent::beforeSave()){
           if(!empty($this->password)){
                $this->password = md5($this->password);
            }
            return true;    
        }
        return false;
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // Правила валидации
        return array(
            // Общие
            array('username, password', 'required', 'message' => '{attribute} не может быть пустым'),
            array('username', 'match', 'pattern' => '/^[A-Za-z0-9А-Яа-я\s]+$/i','message' => 'Логин содержит недопустимые символы'),
            array('email', 'email', 'message' => '{attribute} имеет недопустимый формат'),
            array('username', 'length', 'max'=>32, 'min'=>3, 'message' => 'Длина логина - от 3 до 32 символов'),
            array('password', 'length', 'max'=>32, 'min'=>3, 'message' => 'Длина пароля - от 3 до 32 символов'),
            array('email', 'length', 'max'=>150, 'min'=>3),
            // Авторизация
            array('password', 'authenticate', 'on' => 'login'),
            // Регистрация
            array('username, email', 'unique', 'message' => 'Указанный {attribute} уже используется в системе','on' => 'register'),
            array('email', 'required','on' => 'register', 'message' => ' Не указан {attribute}'),
            array('password', 'compare', 'compareAttribute'=>'password2', 'on'=>'register', 'message' => 'Пароли не совпадают'),
            array('captcha', 'captcha', 'allowEmpty'=>!extension_loaded('gd'), 'on' => 'register', 'message' => 'Неверный код подтверждения'),
            array('password2', 'safe')
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // TODO заполнить отношения
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'userID' => 'User',
            'username' => 'Логин',
            'password' => 'Пароль',
            'email' => 'Email',
            'display_name' => 'Display Name',
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

        $criteria->compare('userID',$this->userID);

        $criteria->compare('username',$this->username,true);

        $criteria->compare('password',$this->password,true);

        $criteria->compare('email',$this->email,true);

        return new CActiveDataProvider(get_class($this), array(
            'criteria'=>$criteria,
        ));
    }
    
    public function authenticate($attribute, $params) 
    {
        if(!$this->hasErrors()){
            $identity= new UserIdentity($this->username, $this->password);
            $identity->authenticate();
            switch($identity->errorCode){
                case UserIdentity::ERROR_NONE: {
                    Yii::app()->user->login($identity, 60*60*24*365);
                    break;
                }
                case UserIdentity::ERROR_USERNAME_INVALID: {
                    $this->addError('username','Пользователь не существует!');
                    break;
                }
                case UserIdentity::ERROR_PASSWORD_INVALID: {
                    $this->addError('password','Вы указали неверный пароль!');
                    break;
                }
            }
        }
    }
}