<?php
/*
This is the EveryCook Recipe Database. It is a web application for creating (and storing) machine (and human) readable recipes.
These recipes are linked to foods and suppliers to allow meal planning and shopping list creation. It also guides the user step-by-step through the recipe with the CookAssistant
EveryCook is an open source platform for collecting all data about food and make it available to all kinds of cooking devices.

This program is copyright (C) by EveryCook. Written by Samuel Werder, Matthias Flierl and Alexis Wiasmitinow.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

See GPLv3.htm in the main folder for details.
*/
?>
<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'feedbacks_form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note"><?php echo $this->trans->CREATE_REQUIRED; ?></p>
	<?php
	echo $form->errorSummary($model);
	if ($this->errorText){
			echo '<div class="errorSummary">';
			echo $this->errorText;
			echo '</div>';
	}
	/*
	echo '<div class="row">'."\r\n";
		echo $form->labelEx($model,'FEE_DESC') ."\r\n";
		echo $form->textField($model,'FEE_DESC',array('size'=>60,'maxlength'=>100)) ."\r\n";
		echo $form->error($model,'FEE_DESC') ."\r\n";
	echo '</div>'."\r\n";
	*/
	/*
	//Example for select / checkboxlist
	$htmlOptions_type0 = array('empty'=>$this->trans->GENERAL_CHOOSE);
	$htmlOptions_type1 = array('template'=>'<li>{input} {label}</li>', 'separator'=>"\n", 'checkAll'=>$this->trans->INGREDIENTS_SEARCH_CHECK_ALL, 'checkAllLast'=>false);
	
	echo Functions::createInput(null, $model, 'GRP_ID', $groupNames, Functions::DROP_DOWN_LIST, 'groupNames', $htmlOptions_type0, $form);
	echo Functions::searchCriteriaInput($this->trans->INGREDIENTS_STORABILITY, $model, 'STB_ID', $storability, Functions::CHECK_BOX_LIST, 'storability', $htmlOptions_type1);
	*/
	?>
	
	<div class="row">
		<?php echo $form->labelEx($model,'FEE_LANG'); ?>
		<?php echo $form->textField($model,'FEE_LANG',array('size'=>8,'maxlength'=>8)); ?>
		<?php echo $form->error($model,'FEE_LANG'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'FEE_TITLE'); ?>
		<?php echo $form->textField($model,'FEE_TITLE',array('size'=>60,'maxlength'=>200)); ?>
		<?php echo $form->error($model,'FEE_TITLE'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'FEE_TEXT'); ?>
		<?php echo $form->textArea($model,'FEE_TEXT',array('rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'FEE_TEXT'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'FEE_EMAIL'); ?>
		<?php echo $form->textField($model,'FEE_EMAIL',array('size'=>60,'maxlength'=>200)); ?>
		<?php echo $form->error($model,'FEE_EMAIL'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'FEE_STATUS'); ?>
		<?php /*$datalist = CHtml::listData(MealTypes::model()->findAll(), 'mty_id', 'mty_desc_en_gb'); echo CHtml::dropDownList('state', '',array_merge(array('empty' => '(Select a state)'), CHtml::listData(MealTypes::model()->findAll(), 'MTY_ID', 'MTY_DESC_EN_GB'))); */echo $form->textField($model,'FEE_STATUS');?>
		<?php echo $form->error($model,'FEE_STATUS'); ?>
	</div>
        
	<div class="row">
		<?php echo $form->labelEx($model,'CREATED_BY'); ?>
		<?php echo $form->textField($model,'CREATED_BY'); ?>
		<?php echo $form->error($model,'CREATED_BY'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'CREATED_ON'); ?>
		<?php echo $form->textField($model,'CREATED_ON'); ?>
		<?php echo $form->error($model,'CREATED_ON'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'CHANGED_BY'); ?>
		<?php echo $form->textField($model,'CHANGED_BY'); ?>
		<?php echo $form->error($model,'CHANGED_BY'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'CHANGED_ON'); ?>
		<?php echo $form->textField($model,'CHANGED_ON'); ?>
		<?php echo $form->error($model,'CHANGED_ON'); ?>
	</div>

	<div class="buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? $this->trans->GENERAL_CREATE : $this->trans->GENERAL_SAVE); ?>
		<?php echo CHtml::link($this->trans->GENERAL_CANCEL, array('cancel'), array('class'=>'button', 'id'=>'cancel')); ?>
	</div>
	
<?php $this->endWidget(); ?>

</div><!-- form -->