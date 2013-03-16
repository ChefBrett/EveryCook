<?php
$this->breadcrumbs=array(
	'Actions Ins'=>array('index'),
	$model->AIN_ID,
);

$this->menu=array(
	array('label'=>'List ActionsIn', 'url'=>array('index')),
	array('label'=>'Create ActionsIn', 'url'=>array('create')),
	array('label'=>'Update ActionsIn', 'url'=>array('update', 'id'=>$model->AIN_ID)),
	array('label'=>'Delete ActionsIn', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->AIN_ID),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage ActionsIn', 'url'=>array('admin')),
);

$this->mainButtons = array(
	array('label'=>$this->trans->GENERAL_EDIT, 'link_id'=>'middle_single', 'url'=>array('update',$this->getActionParams())),
);
?>

<h1><?php printf($this->trans->TITLE_ACTIONSIN_VIEW, $model->AIN_ID); ?></h1>
<div class="f-center">
	<?php  echo CHtml::link($this->trans->GENERAL_BACK_TO_SEARCH, array('search'), array('class'=>'button')); ?><br>
</div>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'AIN_ID',
		'AIN_DEFAULT',
		'AIN_PREP',
		'AIN_DESC_DE_CH',
		'AIN_DESC_EN_GB',
	),
)); ?>
