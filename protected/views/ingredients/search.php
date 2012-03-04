<?php
$this->breadcrumbs=array(
	'Ingredients',
);

$this->menu=array(
	array('label'=>'Create Ingredients', 'url'=>array('create')),
	array('label'=>'Manage Ingredients', 'url'=>array('admin')),
);

//if ($this->validSearchPerformed){
	$this->mainButtons = array(
		array('label'=>$this->trans->INGREDIENTS_CREATE, 'link_id'=>'middle_single', 'url'=>array('ingredients/create',array())),
	);
//}

?>

<div>
<?php $form=$this->beginWidget('CActiveForm', array(
		'action'=>Yii::app()->createUrl($this->route),
		'method'=>'post',
	)); ?>
	<div class="f-left search">
		<?php echo Functions::activeSpecialField($model2, 'query', 'search', array('class'=>'search_query')); ?>
		<?php echo CHtml::imageButton(Yii::app()->request->baseUrl . '/pics/lupe.jpg', array('class'=>'search_button', 'title'=>$this->trans->INGREDIENTS_SEARCH)); ?>
	</div>
	
	<div class="f-right">
		<?php echo CHtml::link($this->trans->INGREDIENTS_ADVANCE_SEARCH, array('ingredients/advanceSearch','newSearch'=>true), array('class'=>'button')); ?><br>
	</div>
	
	<div class="clearfix"></div>
	
<?php $this->endWidget(); ?>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view_array',
	'ajaxUpdate'=>false,
)); ?>
</div>