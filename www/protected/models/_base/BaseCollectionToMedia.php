<?php

/**
 * This is the model base class for the table "collection_to_media".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "CollectionToMedia".
 *
 * Columns in table "collection_to_media" available as properties of the model,
 * and there are no model relations.
 *
 * @property integer $collection_id
 * @property integer $media_id
 *
 */
abstract class BaseCollectionToMedia extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'collection_to_media';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'CollectionToMedia|CollectionToMedias', $n);
	}

	public static function representingColumn() {
		return array(
			'collection_id',
			'media_id',
		);
	}

	public function rules() {
		return array(
			array('collection_id, media_id', 'required'),
			array('collection_id, media_id', 'numerical', 'integerOnly'=>true),
			array('collection_id, media_id', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'collection_id' => null,
			'media_id' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('collection_id', $this->collection_id);
		$criteria->compare('media_id', $this->media_id);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination'=>array(
        'pageSize'=>Yii::app()->fbvStorage->get("settings.pagination_size"),
      ),
		));
	}
}