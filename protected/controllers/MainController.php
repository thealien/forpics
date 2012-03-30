<?php

class MainController extends Controller
{
    /**
     * Главная страница
     * @return
     */
    public function actionIndex()
    {
        $this->render('index', array(
            'messages' => Yii::app()->session->remove('messages')
        ));
    }


    /**
     * Обработка аплоада с web-морды
     * @return
     */
    public function actionWebUpload(){
        if(!Yii::app()->request->isPostRequest)
            throw new CHttpException(403);
        $options = FPConfig::getUploadOptions();
        $uploader = new Uploader( 
            Yii::app()->user->isGuest ? 0 : Yii::app()->user->id,
            $options
        );
        $uploader
            ->setProcessor(Yii::app()->imager)
            ->setValidator(Yii::app()->validator);

        $res = array();
        /*if(!isset($_FILES['uploadfile']) || !$_FILES['uploadfile']['tmp_name']){
            if(isset($_POST['remoteuploadfile']) && ($urls = trim($_POST['remoteuploadfile']))){
                $urls = explode("\n",$urls);
                if($urls){
                    $urls = array_slice($urls,0,10);
                    if(!empty($urls)){
                        $res = $uploader->tryUpload($urls, true);
                    }
                }
            }
        }
        else{*/
        $res = $uploader->tryUpload('uploadfile');

        /*}*/
        if(!$res || $uploader->uploaded==0) $this->redirect('/');
        $image = $res[0];
        if($uploader->uploaded > 1)
            // Группа картинок
            $this->redirect(array('main/viewgroup', 'path_date'=>$uploader->params['path_date'], 'group'=>$uploader->last_group));
        else
            // Одна картинка
            $this->redirect(array('main/view', 'path_date'=>$uploader->params['path_date'], 'guid'=>$uploader->last_guid));
        exit();
    }


    /**
     * Обработка аплоада через FPUploader
     * @return
     */
    public function actionUpload(){
        if(!Yii::app()->request->isPostRequest)
            throw new CHttpException(403);
		if(isset($_POST['username'], $_POST['password']) && Yii::app()->user->isGuest){
			$identity = new UserIdentity($_POST['username'], $_POST['password']);
            $identity->authenticate();
			if($identity->errorCode ===UserIdentity::ERROR_NONE){
				Yii::app()->user->login($identity);
			}
		}
        $options = FPConfig::getUploadOptions();
        $uploader = new Uploader(
            Yii::app()->user->isGuest ? 0 : Yii::app()->user->id,
            $options
        );
        $uploader
            ->setProcessor(Yii::app()->imager)
            ->setValidator(Yii::app()->validator);
        
        $res = $uploader->tryUpload('uploadfile');

        $this->render('xml_result', array(
            'files' => $res
        ));
        exit();
    }
    /**
     * Просмотр картинки
     * @param string $path_date дата
     * @param string $guid guid картинки
     * @return
     */
    public function actionView($path_date, $guid){
    	$image = Image::model()->findByAttributes(array(
            'path_date' => $path_date,
			'guid' => $guid
		));
        if(empty($image))
            throw new CHttpException(404);
        $images = array($image);
        $this->render('view', array(
            'images' => $images,
            'messages' => Yii::app()->session->remove('messages')
        ));
    }
    /**
     * Просмотр группы картинок
     * @param string $path_date дата
     * @param object $group guid группы
     * @return
     */
    public function actionViewGroup($path_date, $group){
    	$images = Image::model()->findAllByAttributes(array(
            'path_date' => $path_date,
			'group' => $group
		));
        if(empty($images))
            throw new CHttpException(404);

        $this->render('view', array(
            'images' => $images,
            'urls'  => '',
            'messages' => Yii::app()->session->remove('messages')
        ));
    }
    
    public function actionMy($page = 1){
    	if(Yii::app()->user->isGuest){
			$this->redirect('user/login', true, 302);
    	}
    	$page = intval($page);
        $page = ($page > 0) ? $page : 1;
        
		$criteria = new CDbCriteria();
		$criteria->addCondition('uploaduserid = '. intval(Yii::app()->user->id));
        $count = Image::model()->count($criteria);

        $pages = new CPagination($count);
        $pages->pageSize = 20;
		$pages->applyLimit($criteria);
		$criteria->order = 'id DESC';
        
        $images = Image::model()->findAll($criteria);
		
        $this->render('my', array(
            'images' => $images,
            'pages' => $pages
        ));
    }

	/**
     * Удаление картинки
     * @param string $path_date дата
     * @param string $guid guid картинки
     * @return
     */
    public function actionDelete($path_date, $guid){
        $image = Image::model()->findByAttributes(array(
            'path_date' => $path_date,
			'deleteGuid' => $guid 
		));
		
		if(!$image)
            throw new CHttpException(404);
			
		if(!empty($_POST)){
			$image->delete();
			if(Yii::app()->user->isGuest)
                $this->redirect(array('main/index'));
			else{
				$this->redirect(array('main/my'));
			}
		}

        $this->render('delete', array(
            'image' => $image
        ));
    }
    
    public function actionGallery(){
        $this->render('gallery', array(
        ));
    }

    public function actionError(){
        $error = Yii::app()->errorHandler->error;
        if(!$error) $this->redirect('/', true, 302);
        $tpl = '404';
        switch($error['code']){
            case 404:
                $tpl = '404';
                break;
            case 500:
                $tpl = '500';
                break;
            case 501:
                $tpl = '501';
                break;
            case 502:
                $tpl = '502';
                break;
            case 503:
                $tpl = '503';
                break;
            case 504:
                $tpl = '504';
                break;
            default: $this->redirect('/', true, 302);
        };
        exit($tpl);
        // TODO доделать страницы
        //$this->render('error', $error);
    }

    public function actionTest(){
        $filename = 'trololo..';
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        var_dump($ext);
    }

}
