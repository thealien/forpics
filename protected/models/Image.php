<?php

/**
 * This is the model class for table "images".
 *
 * The followings are the available columns in table 'images':
 * @property integer $id
 * @property string $date
 * @property string $filename
 * @property string $ip
 * @property string $deleteGuid
 * @property integer $status
 * @property integer $width
 * @property integer $height
 * @property string $uploaduserid
 * @property string $filesize
 * @property string $preview
 * @property string $originalfilename
 * @property integer $fromurl
 * @property integer $useragent
 * @property string $guid
 * @property string $path_date
 * @property string $group
 * @property integer $public
 */
class Image extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Image the static model class
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
		return 'images';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('filename, originalfilename, guid', 'required'),
			array('status, width, height, fromurl, useragent, public', 'numerical', 'integerOnly'=>true),
			array('ip', 'length', 'max'=>15),
			array('deleteGuid, guid, group', 'length', 'max'=>32),
			array('uploaduserid, filesize, preview', 'length', 'max'=>10),
			array('originalfilename', 'length', 'max'=>255),
			array('path_date', 'length', 'max'=>8),
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
			'date' => 'Date',
			'filename' => 'Filename',
			'ip' => 'Ip',
			'deleteGuid' => 'Delete Guid',
			'status' => 'Status',
			'width' => 'Width',
			'height' => 'Height',
			'uploaduserid' => 'Uploaduserid',
			'filesize' => 'Filesize',
			'preview' => 'Preview',
			'originalfilename' => 'Originalfilename',
			'fromurl' => 'Fromurl',
			'useragent' => 'Useragent',
			'guid' => 'Guid',
			'path_date' => 'Path Date',
			'group' => 'Group',
			'public' => 'Public',
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
		$criteria->compare('date',$this->date,true);
		$criteria->compare('filename',$this->filename,true);
		$criteria->compare('ip',$this->ip,true);
		$criteria->compare('deleteGuid',$this->deleteGuid,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('width',$this->width);
		$criteria->compare('height',$this->height);
		$criteria->compare('uploaduserid',$this->uploaduserid,true);
		$criteria->compare('filesize',$this->filesize,true);
		$criteria->compare('preview',$this->preview,true);
		$criteria->compare('originalfilename',$this->originalfilename,true);
		$criteria->compare('fromurl',$this->fromurl);
		$criteria->compare('useragent',$this->useragent);
		$criteria->compare('guid',$this->guid,true);
		$criteria->compare('path_date',$this->path_date,true);
		$criteria->compare('group',$this->group,true);
		$criteria->compare('public',$this->public);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	protected function beforeDelete(){
		FPConfig::load();
		@unlink(Yii::app()->params['dirs']['path_images'].'/'.$this->path_date.'/'.$this->filename);
		if($this->preview){
			@unlink(Yii::app()->params['dirs']['path_preview'].'/'.$this->path_date.'/'.$this->filename);
		}
		return true;
	}
}