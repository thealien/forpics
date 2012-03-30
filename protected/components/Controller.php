<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/column1';
	
	protected function beforeAction(CAction $action){
		if(Yii::app()->request->getParam('theme') === 'm')
            Yii::app()->theme = 'mobile';
		FPConfig::load();
		return parent::beforeAction($action);
	}
	
}