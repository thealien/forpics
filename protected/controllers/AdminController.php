<?php

class AdminController extends Controller
{
    protected function beforeAction($action){
        if(Yii::app()->user->isGuest){
            throw new CHttpException(404);
        }
        if(!Yii::app()->user->isAdmin){
            throw new CHttpException(404);
        }
        return parent::beforeAction($action);
	}
	
	/**
	 * Просмотр новых картинок
	 * @return 
	 */
	public function actionIndex($page = 1){
		
		if(isset($_POST['delete'])){
			$id = intval(key($_POST['delete']));
			if($id){
				$image = Image::model()->findByPk($id);
				if($image && $image->delete()){
					$this->refresh();
				}
			}
		}
		
		if(isset($_POST['approve'], $_POST['images'])){
			$ids = (array)$_POST['images'];
			if($ids){
				$images = Image::model()->updateByPk(
				    $ids,
					array('status' => 1)
				);
				$this->refresh();
			}
		}
		
		$page = intval($page);
        $page = ($page > 0) ? $page : 1;
        
        $criteria = new CDbCriteria();
        $criteria->addCondition('status = 0');
        $count = Image::model()->count($criteria);

        $pages = new CPagination($count);
        $pages->pageSize = 20;
        $pages->applyLimit($criteria);
        $criteria->order = 'id ASC';
        
        $images = Image::model()->findAll($criteria);
        
        $this->render('index', array(
            'images' => $images,
            'pages' => $pages
        ));
    }

}
	

	