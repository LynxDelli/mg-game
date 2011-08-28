<?php

$this->breadcrumbs = array(
	Yii::t('app', 'Admin')=>array('/admin'),
	$model->label(2),
);

$this->menu = array(
		array('label'=>Yii::t('app', 'Create') . ' ' . $model->label(), 'url'=>array('create')),
	);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('image-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1><?php echo Yii::t('app', 'Manage') . ' ' . GxHtml::encode($model->label(2)); ?></h1>

<p>
You may optionally enter a comparison operator (&lt;, &lt;=, &gt;, &gt;=, &lt;&gt; or =) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo GxHtml::link(Yii::t('app', 'Advanced Search'), '#', array('class' => 'search-button')); ?>
<div class="search-form">
<?php $this->renderPartial('_search', array(
	'model' => $model,
)); ?>
</div><!-- search-form -->

<?php echo CHtml::beginForm('','post',array('id'=>'image-form'));
$this->widget('zii.widgets.grid.CGridView', array(
	'id' => 'image-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'selectableRows'=>2,
	'columns' => array(
	  array(
      'class'=>'CCheckBoxColumn',
      'id'=>'image-ids',
    ),
		 array(
          'name' => 'name',
          'type' => 'image',
          'value' => 'Yii::app()->getBaseUrl() . Yii::app()->params[\'upload_url\'] . \'/thumbs/\'. $data->name',
        ),
		'size',
		'mime_type',
		'last_access',
		 array(
        'name' => 'locked',
        'type' => 'raw',
        'value' => 'MGHelper::itemAlias(\'locked\',$data->locked)',
        'filter'=> MGHelper::itemAlias('locked'),
      ),
		/*
		'created',
		'modified',
		*/
    array (
  'class' => 'CButtonColumn',
  'buttons' => 
  array (
    'delete' => 
    array (
      'visible' => '$data->locked == 0',
    ),
  ),
)  ),
)); 
echo CHtml::endForm();

$this->widget('ext.gridbatchaction.GridBatchAction', array(
      'formId'=>'image-form',
      'checkBoxId'=>'image-ids',
      'ajaxGridId'=>'image-grid', 
      'items'=>array(
          array('label'=>Yii::t('ui','Delete selected items'),'url'=>array('batch', 'op' => 'delete'))
      ),
      'htmlOptions'=>array('class'=>'batchActions'),
  ));

?>