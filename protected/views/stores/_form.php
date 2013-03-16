<input type="hidden" id="uploadImageLink" value="<?php echo $this->createUrl('stores/uploadImage',array('id'=>$model->STO_ID)); ?>"/>
<input type="hidden" id="imageLink" value="<?php echo $this->createUrl('stores/displaySavedImage', array('id'=>'backup', 'ext'=>'.png')); ?>"/>
<div class="form">


<div class="mapDetails">
	<?php $form=$this->beginWidget('CActiveForm', array(
		'id'=>'stores-form',
		'enableAjaxValidation'=>false,
		'action'=>Yii::app()->createUrl($this->route, array_merge($this->getActionParams(), array('ajaxform'=>true))),
		'htmlOptions'=>array('enctype' => 'multipart/form-data', 'class'=>'ajaxupload'),
	)); ?>
		<p class="note"><?php echo $this->trans->CREATE_REQUIRED; ?></p>
		
		<?php
		echo $form->errorSummary($model);
		if ($this->errorText){
			echo '<div class="errorSummary">';
			echo $this->errorText;
			echo '</div>';
		}
		
		$htmlOptions_type0 = array('empty'=>$this->trans->GENERAL_CHOOSE);
		?>

		<div class="row">
			<?php echo $form->labelEx($model,'STO_NAME'); ?>
			<?php echo $form->textField($model,'STO_NAME',array('size'=>60,'maxlength'=>100)); ?>
			<?php echo $form->error($model,'STO_NAME'); ?>
		</div>

		<div class="row">
			<?php echo $form->labelEx($model,'STO_STREET'); ?>
			<?php echo $form->textField($model,'STO_STREET',array('size'=>60,'maxlength'=>100)); ?>
			<?php echo $form->error($model,'STO_STREET'); ?>
		</div>

		<div class="row">
			<?php echo $form->labelEx($model,'STO_HOUSE_NO'); ?>
			<?php echo $form->textField($model,'STO_HOUSE_NO',array('size'=>20,'maxlength'=>20)); ?>
			<?php echo $form->error($model,'STO_HOUSE_NO'); ?>
		</div>

		<div class="row">
			<?php echo $form->labelEx($model,'STO_ZIP'); ?>
			<?php echo $form->textField($model,'STO_ZIP'); ?>
			<?php echo $form->error($model,'STO_ZIP'); ?>
		</div>

		<div class="row">
			<?php echo $form->labelEx($model,'STO_CITY'); ?>
			<?php echo $form->textField($model,'STO_CITY',array('size'=>60,'maxlength'=>100)); ?>
			<?php echo $form->error($model,'STO_CITY'); ?>
		</div>

		<?php
		/*
		<div class="row">
			<?php echo $form->labelEx($model,'STO_COUNTRY'); ?>
			<?php echo $form->textField($model,'STO_COUNTRY'); ?>
			<?php echo $form->error($model,'STO_COUNTRY'); ?>
		</div>
		*/
		echo Functions::createInput($this->trans->STORES_COUNTRY, $model, 'STO_COUNTRY', $countrys, Functions::DROP_DOWN_LIST, 'countrys', $htmlOptions_type0, $form);
		?>

		<div class="row">
			<?php echo $form->labelEx($model,'STO_STATE'); ?>
			<?php echo $form->textField($model,'STO_STATE',array('size'=>60,'maxlength'=>100)); ?>
			<?php echo $form->error($model,'STO_STATE'); ?>
		</div>

		<div class="row">
			<?php echo $form->labelEx($model,'STO_PHONE'); ?>
			<?php echo $form->textField($model,'STO_PHONE',array('size'=>20,'maxlength'=>20)); ?>
			<?php echo $form->error($model,'STO_PHONE'); ?>
		</div>
		
		<?php
		echo Functions::createInput($this->trans->STORES_SUPPLIER, $model, 'SUP_ID', $supplier, Functions::DROP_DOWN_LIST, 'supplier', $htmlOptions_type0, $form);
		echo Functions::createInput($this->trans->STORES_STORE_TYPE, $model, 'STY_ID', $storeType, Functions::DROP_DOWN_LIST, 'storeTypes', $htmlOptions_type0, $form);
		
		/*
		<div class="row">
			<?php echo $form->labelEx($model,'SUP_ID'); ?>
			<?php echo $form->textField($model,'SUP_ID'); ?>
			<?php echo $form->error($model,'SUP_ID'); ?>
		</div>
		
		<div class="row">
			<?php echo $form->labelEx($model,'STY_ID'); ?>
			<?php echo $form->textField($model,'STY_ID'); ?>
			<?php echo $form->error($model,'STY_ID'); ?>
		</div>
		*/
		?>
		
		<div class="row">
			<?php echo $form->labelEx($model,'STO_GPS_LAT'); ?>
			<?php echo $form->textField($model,'STO_GPS_LAT', array('class'=>'cord_lat')); ?>
			<?php echo $form->error($model,'STO_GPS_LAT'); ?>
		</div>

		<div class="row">
			<?php echo $form->labelEx($model,'STO_GPS_LNG'); ?>
			<?php echo $form->textField($model,'STO_GPS_LNG', array('class'=>'cord_lng')); ?>
			<?php echo $form->error($model,'STO_GPS_LNG'); ?>
		</div>
		
		<?php
			if (Yii::app()->session['Stores_Backup'] && Yii::app()->session['Stores_Backup']->STO_IMG_ETAG){
				echo CHtml::image($this->createUrl('stores/displaySavedImage', array('id'=>'backup', 'ext'=>'.png', 'rand'=>rand())), '', array('class'=>'store cropable', 'alt'=>$model->STO_NAME, 'title'=>$model->STO_NAME));
			} else if ($model->STO_ID) {
				echo CHtml::image($this->createUrl('stores/displaySavedImage', array('id'=>$model->STO_ID, 'ext'=>'.png')), '', array('class'=>'store', 'alt'=>$model->STO_NAME, 'title'=>$model->STO_NAME));
			}
		?>
		
		<div class="row">
			<?php echo $form->labelEx($model,'filename'); ?>
			<?php echo $form->FileField($model,'filename'); ?>
			<?php echo $form->error($model,'filename'); ?>
		</div>
		
		<div class="row">
			<?php echo $form->labelEx($model,'STO_IMG_AUTH'); ?>
			<?php echo $form->textField($model,'STO_IMG_AUTH',array('size'=>30,'maxlength'=>30)); ?>
			<?php echo $form->error($model,'STO_IMG_AUTH'); ?>
		</div>
		
		
	<?php /*
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
		*/ ?> 
		<div class="buttons">
			<?php echo CHtml::button($this->trans->GENERAL_GPS_ADDRESS_TO_GPS, array('id'=>'Address_to_GPS', 'class'=>'button')); ?>
			<?php echo CHtml::button($this->trans->GENERAL_GPS_GPS_TO_ADDRESS, array('id'=>'GPS_to_Address', 'class'=>'button')); ?>
		</div>
		<div class="buttons">
			<?php echo CHtml::submitButton($model->isNewRecord ? $this->trans->GENERAL_CREATE : $this->trans->GENERAL_SAVE, array('name'=>'save', 'class'=>'button')); ?>
			<?php echo CHtml::link($this->trans->GENERAL_CANCEL, array('cancel'), array('class'=>'button', 'id'=>'cancel')); ?>
		</div>
	<?php $this->endWidget(); ?>
</div>
<div id="map_container">
	<div id="places_search">
		<input type="text" name="placesQuery" id="placesQuery"/><br>
		<div class="button" id="placesByQuery"><?php echo $this->trans->STORES_SEARCH_BY_QUERY; ?></div>
		<div class="button" id="placesByRange"><?php echo $this->trans->STORES_SEARCH_BY_RANGE; ?></div>
	</div>
	<div id="map_canvas" style="height:30em; width:30em;"></div>
	<div id="places_results"></div>
</div>
<div class="clearfix"></div>

<script type="text/javascript">
	loadScript(false, "CH", false, false, true, true, true);
</script>

</div><!-- form -->