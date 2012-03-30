<?php

class EWebUser extends CWebUser {
    
	protected $isAdmin = null;
	
	public function getIsAdmin(){
		if(!is_null($this->isAdmin))
            return $this->isAdmin;
		$this->isAdmin = Yii::app()->user->getState('admin');
		return $this->isAdmin;
	}
	
}