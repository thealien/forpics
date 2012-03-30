<?php

class UserController extends Controller
{
    public function actions(){
        return array(
            'captcha'=>array(
                'class'=>'CCaptchaAction',
            ),
        );
    }
    /**
     * Разлогин
     * @return null
     */
    public function actionLogout(){
        if( isset($_POST['logout']) &&
            !Yii::app()->user->isGuest)
        {
            Yii::app()->user->logout(); 
        }
        $this->redirect(Yii::app()->user->returnUrl);
    }
    /**
     * Авторизация
     * @return null
     */
    public function actionLogin(){
        if(!Yii::app()->user->isGuest){
            // Уже авторизованы
            $this->redirect('/', true, 302);
        }
        $form = new Users('login');
        $errors = array();
        if(isset($_POST['loginbtn'])){
            $form->attributes = $_POST['User'];
            if($form->validate()){
                $this->redirect('/', true, 302);
            }
            else{
                $errors[] = 'Неверные логин или пароль';
            }
        }
        Yii::app()->params['title'] =  'Вход на сайт' . ' — ' . Yii::app()->params['title'];
        $this->render('login', array(
            'form'      => $form->attributes,
            'errors'    => $errors
        ));
    }
    /**
     * Регистрация
     * @return null
     */
    public function actionRegister(){
        if(!Yii::app()->user->isGuest){
            // Уже авторизованы
            $this->redirect('/', true, 302);
        }
        $form = new Users('register');
        if(isset($_POST['regbtn'])){
            $form->attributes = $_POST['User'];
            if($form->save()){
                Yii::app()->session['register'] = true;
                $this->refresh();
            }
        }
        
        $captcha = $this->widget('CCaptcha', array(
            'showRefreshButton'=>false,
            'clickableImage'=>true,
            'imageOptions'=>array(
                'alt'       => 'проверочный код',
                'title'     => 'Кликни по картинке, чтобы сменить код',
                'border'    => 1,
                'width'     => '100',
                'height'    => '37'
          )
        ), true);
        
        $register = isset(Yii::app()->session['register']) ? true : false;
        unset(Yii::app()->session['register']);
        Yii::app()->params['title'] =  'Регистрация' . ' — ' . Yii::app()->params['title'];
        $this->render('register', array(
            'captcha'   => $captcha,
            'register'  => $register,
            'form'      => $form
        ));
    }
    
    public function actionIndex($user = FALSE){
        if(!$user && Yii::app()->user->isGuest){
            throw new CHttpException(404);
        }
        $username = $user ? $user : Yii::app()->user->name;
        $user = Users::model()->findByAttributes(array('username'=>$username));
        if($user===NULL){
            throw new CHttpException(404);
        }
        $owner = $user->userID===Yii::app()->user->id;
        
        Yii::app()->params['title'] =  'Профиль пользователя "' . $user->username . '" — ' . Yii::app()->params['title'];
        $this->render('profile', array(
            'user'      => $user,
            'owner'     => $owner
        ));
    }
    
    public function actionFav(){
        if(Yii::app()->user->isGuest){
            // Гостей кидаем на авторизацию
            $this->redirect(array('user/login'));
        }
        echo 'fav';
        
    }
    // TODO почистить код
    public function actionEmail($hash = false){
        if(Yii::app()->user->isGuest){
            // Гостей кидаем на авторизацию
            $this->redirect(array('user/login'));
        }
        $errors = array();
        $user = Users::model()->findByPk(Yii::app()->user->id);
        $email = '';
        if($hash){
            $hash = trim($hash);
            $row = Yii::app()->db->createCommand(array(
                'from' => 'email_change',
                'where' => 'hash=:hash AND userid=:userid',
                'params' => array(':hash'=>$hash, ":userid" => Yii::app()->user->id),
            ))->queryRow();
            if($row){
                $user->email = $row['email'];
                if($user->save(true, array('email'))){
                    Yii::app()->db->createCommand()->delete('email_change', 'userid=:userid', array(':userid' => Yii::app()->user->id));
                    $this->redirect('/user/email/', true, 302);
                }
            }
            else
                $this->redirect('/user/email/', true, 302);
        }

        if(isset($_POST['email'])){
            $email = trim($_POST['email']);
            $v = new CEmailValidator();
            if($v->validateValue($email)){
                $hash = md5(md5(time()).md5($email). md5(Yii::app()->user->name));
                $command = Yii::app()->db->createCommand();
                $res = $command->insert('email_change', array(
                    'userid'    => Yii::app()->user->id,
                    'email'     => $email,
                    'ip'        => @$_SERVER['REMOTE_ADDR'],
                    'hash'      => $hash                    
                ));
                if($res){
                    $mail_body = $this->render(
                        'email_chacnge_tpl', 
                        array(
                            'email' => $email,
                            'user'  => $user,
                            'hash'  => $hash
                        ), 
                        true
                    );
                    $headers  = "Content-type: text/html; charset=windows-1251 \r\n"; 
                    $headers .= "From: mail@sitelist.in\r\n"; 
                    mail($user->email, 'Каталог сайтов SiteList - смена email', $mail_body, $headers);
                    Yii::app()->session['changed'] = true;
                    $this->redirect('/user/email/', true, 302);
                }
                else{
                    $errors[] = 'В данный момент смена email невозможна. Попробуйте позже.';    
                }
            }
            else
                $errors[] = 'Указанный email имеет неверный формат';
        }
        $changed = isset(Yii::app()->session['changed']) ? true : false;
        unset(Yii::app()->session['changed']);
        Yii::app()->params['title'] =  'Смена email' . ' — ' . Yii::app()->params['title'];
        
        $this->render('email', array(
            'user'      => $user,
            'email'     => $email,
            'errors'    => $errors,
            'changed'   => $changed
        ));
    }
    
    public function actionPass(){
        if(Yii::app()->user->isGuest){
            // Гостей кидаем на авторизацию
            $this->redirect(array('user/login'));
        }
        $errors = array();
        $user = Users::model()->findByPk(Yii::app()->user->id);
        $oldpass = $pass = $pass2 = '';
        if(isset($_POST['oldpass']) && isset($_POST['pass']) && isset($_POST['pass2'])){
            do{
                $oldpass = strval($_POST['oldpass']);
                $pass = trim(strval($_POST['pass']));
                $pass2 = trim(strval($_POST['pass2']));
                if(md5($oldpass)!==$user->password){
                    $errors[] = 'Текущий пароль указан неверно';
                    break;
                }
                if($pass!==$pass2){
                    $errors[] = 'Новый пароль и его подтверждение не совпадают';
                    break;
                }
                $user->password = $pass;
                if($user->validate(array('password')) && $user->save(false, array('password'))){
                    Yii::app()->session['changed'] = true;
                    $this->redirect('/user/pass/', true, 302);
                }
                else{
                    // Ошибка валидации
                    if($user->hasErrors())
                    foreach($user->getErrors() as $er)
                        foreach($er as $error){
                            $errors[] = $error;
                        }
                }
                
            }
            while(false);
        }
    
        
        
        $changed = isset(Yii::app()->session['changed']) ? true : false;
        unset(Yii::app()->session['changed']);
        Yii::app()->params['title'] =  'Смена пароля' . ' — ' . Yii::app()->params['title'];
        
        $this->render('pass', array(
            'user'      => $user,
            'errors'    => $errors,
            'changed'   => $changed
        ));
    }
}