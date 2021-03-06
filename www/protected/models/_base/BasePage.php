<?php

/**
 * This is the model base class for the table "page".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "Page".
 *
 * Columns in table "page" available as properties of the model,
 * followed by relations of table "page" available as properties of the model.
 *
 * @property integer $id
 * @property string $title
 * @property string $text
 * @property string $url
 * @property integer $new_tab
 * @property integer $type
 * @property string $created
 * @property string $modified
 *
 * @property MenuItem[] $menuItems
 */
abstract class BasePage extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'page';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Page|Pages', $n);
	}

	public static function representingColumn() {
		return 'title';
	}

	public function rules() {
		return array(
			array('title, created, modified', 'required'),
			array('new_tab, type', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>45),
			array('url', 'length', 'max'=>254),
			array('text', 'safe'),
			array('text, url, new_tab, type', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, title, text, url, new_tab, type, created, modified', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'menuItems' => array(self::HAS_MANY, 'MenuItem', 'pages_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'title' => Yii::t('app', 'Title'),
			'text' => Yii::t('app', 'Text'),
			'url' => Yii::t('app', 'Url'),
			'new_tab' => Yii::t('app', 'New Tab'),
			'type' => Yii::t('app', 'Type'),
			'created' => Yii::t('app', 'Created'),
			'modified' => Yii::t('app', 'Modified'),
			'menuItems' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('title', $this->title, true);
		$criteria->compare('text', $this->text, true);
		$criteria->compare('url', $this->url, true);
		$criteria->compare('new_tab', $this->new_tab);
		$criteria->compare('type', $this->type);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('modified', $this->modified, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination'=>array(
        'pageSize'=>Yii::app()->fbvStorage->get("settings.pagination_size"),
      ),
		));
	}
}