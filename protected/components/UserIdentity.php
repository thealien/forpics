<?php

class UserIdentity extends CUserIdentity
{
	private $_id;
	private $_admin = false;
	
    public function authenticate()
    {
        $record = Users::model()->findByAttributes(array('username'=>$this->username));
        if($record===null)
            $this->errorCode=self::ERROR_USERNAME_INVALID;
        else if($record->password!==md5($this->password))
            $this->errorCode=self::ERROR_PASSWORD_INVALID;
        else
        {
            $this->setState('admin', $this->_admin = ($record->role==='admin'));
            $this->_id = $record->userID;
            $this->errorCode=self::ERROR_NONE;
        }
        return !$this->errorCode;
    }
 
    public function getId()
    {
        return $this->_id;
    }
}