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

$this->breadcrumbs=array(
	'Shoppinglists'=>array('index'),
	$SHO_ID,
);

$this->menu=array(
	array('label'=>'List Shoppinglists', 'url'=>array('index')),
	array('label'=>'Create Shoppinglists', 'url'=>array('create')),
	array('label'=>'Update Shoppinglists', 'url'=>array('update', 'id'=>$SHO_ID)),
	array('label'=>'Delete Shoppinglists', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$SHO_ID),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Shoppinglists', 'url'=>array('admin')),
);

if(Yii::app()->user->hasFlash('shoppinglistsView')){
	$flash = Yii::app()->user->getFlash('shoppinglistsView');
	if (isset($flash['error'])){
		echo '<div class="flash-error">';
		echo $flash['success'];
		echo '</div>';
	} else if (isset($flash['success'])){
		echo '<div class="flash-success">';
		echo $flash['success'];
		echo '</div>';
	}
}?>

<h1><?php printf($this->trans->TITLE_SHOPPINGLISTS_VIEW, $SHO_ID); ?></h1>

<?php
	if (isset($MEA_ID) && $MEA_ID != 0){
		if (isset($mealChanged) && $mealChanged){
			echo '<div class="">' . $this->trans->SHOPPINGLISTS_MEAL_CHANGED_SHOULD_RECREATE . '</div>';
		}
		echo CHtml::link($this->trans->SHOPPINGLISTS_RECREATE_FROM_MEAL, array('meals/createShoppingList', 'id'=>$MEA_ID), array('class'=>'button f-center'));
	} else if (isset($recipes_id) && $recipes_id != ''){
		if (isset($mealChanged) && $mealChanged){
			echo '<div class="">' . $this->trans->SHOPPINGLISTS_RECIPE_CHANGED_SHOULD_RECREATE . '</div>';
		}
		echo CHtml::link($this->trans->SHOPPINGLISTS_RECREATE_FROM_RECIPE, array('recipes/viewShoppingList', 'id'=>$recipes_id), array('class'=>'button f-center'));
	}
?>

<?php $this->widget('AjaxPagingListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_item_view',
	'id'=>'shoppinglistView',
)); ?>

<div class="buttons">
	<?php
	echo CHtml::link($this->trans->SHOPPINGLISTS_PRINT, array('shoppinglists/print', 'id'=>$SHO_ID), array('class'=>'button fancy_iframe'));
	echo CHtml::link($this->trans->SHOPPINGLISTS_MAIL, array('shoppinglists/mail', 'id'=>$SHO_ID), array('class'=>'button fancybutton'));
	echo CHtml::link($this->trans->SHOPPINGLISTS_SAVE, array('shoppinglists/save', 'id'=>$SHO_ID), array('class'=>'button'));
	?>
</div>

