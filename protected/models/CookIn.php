<?php

/**
 * This is the model class for table "cook_in".
 *
 * The followings are the available columns in table 'cook_in':
 * @property integer $COI_ID
 * @property integer $TOO_ID
 * @property string $COI_DESC_DE_CH
 * @property string $COI_DESC_EN_GB
 * @property integer $CREATED_BY
 * @property integer $CREATED_ON
 * @property integer $CHANGED_BY
 * @property integer $CHANGED_ON
 */
class CookIn extends ActiveRecordEC
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return CookIn the static model class
	 */
	public static function model($className=__CLASS__){
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName(){
		return 'cook_in';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules(){
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('TOO_ID, COI_DESC_DE_CH, COI_DESC_EN_GB, CREATED_BY, CREATED_ON', 'required'),
			array('TOO_ID, CREATED_BY, CREATED_ON, CHANGED_BY, CHANGED_ON', 'numerical', 'integerOnly'=>true),
			array('COI_DESC_DE_CH, COI_DESC_EN_GB', 'length', 'max'=>100),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('COI_ID, TOO_ID, COI_DESC_DE_CH, COI_DESC_EN_GB, CREATED_BY, CREATED_ON, CHANGED_BY, CHANGED_ON', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations(){
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'tool' => array(self::BELONGS_TO, 'Tools', 'TOO_ID'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels(){
		return array(
			'COI_ID' => 'Coi',
			'TOO_ID' => 'Too',
			'COI_DESC_DE_CH' => 'Coi Desc De Ch',
			'COI_DESC_EN_GB' => 'Coi Desc En Gb',
			'CREATED_BY' => 'Created By',
			'CREATED_ON' => 'Created On',
			'CHANGED_BY' => 'Changed By',
			'CHANGED_ON' => 'Changed On',
		);
	}
	
	public function getSearchFields(){
		return array('COI_ID', 'COI_DESC_' . Yii::app()->session['lang']);
	}
	
	
	public function getCriteriaString(){
		$criteria=new CDbCriteria;
		
		$criteria->compare($this->tableName().'.COI_ID',$this->COI_ID);
		$criteria->compare($this->tableName().'.TOO_ID',$this->TOO_ID);
		$criteria->compare($this->tableName().'.COI_DESC_DE_CH',$this->COI_DESC_DE_CH,true);
		$criteria->compare($this->tableName().'.COI_DESC_EN_GB',$this->COI_DESC_EN_GB,true);
		$criteria->compare($this->tableName().'.CREATED_BY',$this->CREATED_BY);
		$criteria->compare($this->tableName().'.CREATED_ON',$this->CREATED_ON);
		$criteria->compare($this->tableName().'.CHANGED_BY',$this->CHANGED_BY);
		$criteria->compare($this->tableName().'.CHANGED_ON',$this->CHANGED_ON);
		
		return $criteria;
	}
	
	public function getCriteria(){
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('COI_ID',$this->COI_ID);
		$criteria->compare('TOO_ID',$this->TOO_ID);
		$criteria->compare('COI_DESC_DE_CH',$this->COI_DESC_DE_CH,true);
		$criteria->compare('COI_DESC_EN_GB',$this->COI_DESC_EN_GB,true);
		$criteria->compare('CREATED_BY',$this->CREATED_BY);
		$criteria->compare('CREATED_ON',$this->CREATED_ON);
		$criteria->compare('CHANGED_BY',$this->CHANGED_BY);
		$criteria->compare('CHANGED_ON',$this->CHANGED_ON);
		//Add with conditions for relations
		//$criteria->with = array('???relationName???' => array());
		
		return $criteria;
	}
	
	public function getSort(){
		$sort = new CSort;
		$sort->attributes = array(
		/*
			'sortId' => array(
				'asc' => 'COI_ID',
				'desc' => 'COI_ID DESC',
			),
		*/
			'*',
		);
		return $sort;
	}
	
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search(){
		return new CActiveDataProvider($this, array(
			'criteria'=>$this->getCriteria(),
			'sort'=>$this->getSort(),
		));
	}
}