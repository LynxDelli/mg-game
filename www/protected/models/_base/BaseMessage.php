<?php

/**
 * This is the model base class for the table "message".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "Message".
 *
 * Columns in table "message" available as properties of the model,
 * followed by relations of table "message" available as properties of the model.
 *
 * @property integer $session_id
 * @property integer $played_game_id
 * @property string $message
 *
 * @property PlayedGame $playedGame
 * @property Session $session
 */
abstract class BaseMessage extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'message';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Message|Messages', $n);
	}

	public static function representingColumn() {
		return 'message';
	}

	public function rules() {
		return array(
			array('session_id, played_game_id', 'required'),
			array('session_id, played_game_id', 'numerical', 'integerOnly'=>true),
			array('message', 'length', 'max'=>1000),
			array('message', 'default', 'setOnEmpty' => true, 'value' => null),
			array('session_id, played_game_id, message', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'playedGame' => array(self::BELONGS_TO, 'PlayedGame', 'played_game_id'),
			'session' => array(self::BELONGS_TO, 'Session', 'session_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'session_id' => null,
			'played_game_id' => null,
			'message' => Yii::t('app', 'Message'),
			'playedGame' => null,
			'session' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('session_id', $this->session_id);
		$criteria->compare('played_game_id', $this->played_game_id);
		$criteria->compare('message', $this->message, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination'=>array(
        'pageSize'=>Yii::app()->fbvStorage->get("settings.pagination_size"),
      ),
		));
	}
}