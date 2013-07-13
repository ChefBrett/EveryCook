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

class Functions extends CHtml{
	
	const DROP_DOWN_LIST = 0;
	const CHECK_BOX_LIST = 1;
	const MULTI_LIST = 2;
	
	const IMG_HEIGHT = 400;
	const IMG_WIDTH = 400;
	
	
	protected static $memcached = null;
	
	public static  function getMemcached(){
		if ($this->memcached == null){
			$this->memcached = new Memcached();
			$this->memcached->addServer('localhost', 11211);
		}
		return $this->memcached;
	}
	
	public static  function getFromCache($name){
		if (Yii::app()->params['cacheMethode'] == 'session'){
			return Yii::app()->session[$name];
		} else if (Yii::app()->params['cacheMethode'] == 'apc'){
			return apc_fetch($name);
		} else /*if (Yii::app()->params['cacheMethode'] == 'memcached')*/{
			$memcached = $this->getMemcached();
			return $memcached->get($name);
		}
	}
	
	public static  function saveToCache($name, $value, $expirationTime=0){
		if (Yii::app()->params['cacheMethode'] == 'session'){
			Yii::app()->session[$name] = $value;
		} else if (Yii::app()->params['cacheMethode'] == 'apc'){
			if(apc_store($name, $value, $expirationTime)){
			/*
			echo "save successfull...";
			} else {
				echo "save failed...";
			*/
			}
		} else /*if (Yii::app()->params['cacheMethode'] == 'memcached')*/{
			$memcached = $this->getMemcached();
			//$memcached->set($name, $value, $expirationTime);
			//$value = Functions::objectToArray($value);
			//$value = Functions::arrayToObject($value);
			//$value = Functions::mapCActiveRecordToSimpleClass($value);
			//print_r($value);
			//if($memcached->set($name."_stdobj", $value, $expirationTime)){
			if($memcached->set($name, $value, $expirationTime)){
			/*
				echo "save successfull...";
			} else {
				echo "save failed...";
			*/
			}
		}
	}
	
	/*
	public static function preparedStatementToStatement($command, $params){
		$sql = $command->getText();
		foreach($params as $key => $value){
			$sql = str_replace($key, $value, $sql);
		}
		return Yii::app()->db->createCommand($sql);
	}
	*/

	public static function searchCriteriaInput($label, $model, $fieldName, $dataList, $type, $id, $htmlOptions) {
		$html = '<div class="row" id="'.$id.'">';
		$html .= self::activeLabel($model,$fieldName, array('label'=>$label));
		$html .= ' ';
		if ($type == 0){
			$html .= self::dropDownList(self::resolveName($model,$fieldName), $model->__get($fieldName), $dataList, $htmlOptions); 
		} else if ($type == 1){
			$html .= '<ul class="search_choose">';
			$html .= self::checkBoxList(self::resolveName($model,$fieldName), $model->__get($fieldName), $dataList, $htmlOptions); 
			$html .= '</ul>';
			$html .= '<div class="clearfix"></div>';
		}
		$html .= '</div>';
		
		return $html;
	}
	
	public static function createInput($label, $model, $fieldName, $dataList, $type, $id, $htmlOptions, $form) {
		$html = '<div class="row" id="'.$id.'">';
		$html .= self::activeLabelEx($model, $fieldName, array('label'=>$label));
		$html .= ' ';
		if ($type == self::DROP_DOWN_LIST){
			$html .= self::dropDownList(self::resolveName($model,$fieldName), $model->__get($fieldName), $dataList, $htmlOptions); 
		} else if ($type == self::CHECK_BOX_LIST){
			$html .= '<ul class="search_choose">';
			$html .= self::checkBoxList(self::resolveName($model,$fieldName), $model->__get($fieldName), $dataList, $htmlOptions); 
			$html .= '</ul>';
			$html .= '<div class="clearfix"></div>';
		} else if ($type == self::MULTI_LIST){
			$html .= self::listBox(self::resolveName($model,$fieldName), $model->__get($fieldName), $dataList, $htmlOptions); 
		}
		if ($form){
			$html .= $form->error($model, $fieldName);
		}
		$html .= '</div>';
		
		return $html;
	}
	
	/*
	Logic from self::resolveName
	*/
	public static function resolveArrayName($model,$attribute,$index){
		if(($pos=strpos($attribute,'['))!==false) {
			if($pos!==0)  // e.g. name[a][b]
					return get_class($model).'['.$index.']'.'['.substr($attribute,0,$pos).']'.substr($attribute,$pos);
			if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1)  // e.g. [a][b]name
			{
				$sub=substr($attribute,0,$pos+1);
				$attribute=substr($attribute,$pos+1);
				return get_class($model).'['.$index.']'.$sub.'['.$attribute.']';
			}
			if(preg_match('/\](\w+\[.*)$/',$attribute,$matches))
			{
				$name=get_class($model).'['.$index.']'.'['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
				$attribute=$matches[1];
				return $name;
			}
		}
		return get_class($model).'['.$index.']'.'['.$attribute.']';
	}
	
	public static function resolveMultiArrayName($model,$attributeArray){
		$name = get_class($model);
		
		foreach($attributeArray as $attribute){
			$name .= '['.$attribute.']';
		}
		return $name;
	}
	/*
	private static function changeUnitMultipliers($unit_values, $currentValue){
		$unit_values = array_flip($unit_values);
		if(isset($unit_values[$currentValue]) && $unit_values[$currentValue] != 1){
			$divisor = $unit_values[$currentValue];
			for($unitIndex=0; $unitIndex<count($unit_values);++$unitIndex){
				$unit_values[$unitIndex] = $unit_values[$unitIndex] / $divisor;
			}
		} else {
			$divisor = 1;
		}
		return array(array_flip($unit_values), $divisor);
	}
	*/
	private static function inputTableRow($class, $fieldOptions, $index, $value, $texts){
		$html = '<tr class="'.$class.'">';
		foreach($fieldOptions as $field){
			$options = $field[3];
			if (isset($options['hidden']) && $options['hidden'] != ''){
				$html .= self::hiddenField(self::resolveArrayName($value,$field[0],$index), $value->__get($field[0]));
			} else if (isset($options['fancy']) && $options['fancy']){
				$text = $options['empty'];
				$val = $value->__get($field[0]);
				if ($val != '' && is_array($field[2])){
					foreach($field[2] as $row_key=>$row_val){
						if($row_key == $val){
							$text = $row_val;
							break;
						}
					}
				}
				$htmlOptions = array_merge(array('id'=>self::getIdByName(self::resolveArrayName($value,$field[0].'_DESC',$index))),$options['htmlOptions']);
				$html .= '<td>' . self::hiddenField(self::resolveArrayName($value,$field[0],$index), $value->__get($field[0]), array('class'=>'fancyValue')) . self::link($text, $options['url'], $htmlOptions) . '</td>';
			} else if (isset($options['multiple_selects']) && $options['multiple_selects'] !== ''){
				$html .= '<td>';
				$valueIndex = 0;
				foreach($field[2] as $id=>$values){
					$field_name = self::resolveArrayName($value,$field[0],$index);
					$field_id = self::getIdByName($field_name).'_'.$id;
					$htmlparams = array_merge($field[3],array('id'=>$field_id));
					unset($htmlparams['multiple_selects']);
					if ($valueIndex != $options['multiple_selects']){
						$htmlparams = array_merge($htmlparams,array('style'=>'display: none;', 'disabled'=>'disabled'));
					}
					$html .= self::dropDownList($field_name, $value->__get($field[0]), $values, $htmlparams);
					++$valueIndex;
				}
				$html .= '</td>';
			} else if (is_array($field[2])){
				$html .= '<td>'.self::dropDownList(self::resolveArrayName($value,$field[0],$index), $value->__get($field[0]), $field[2], $field[3]).'</td>';
			} else if (isset($options['field_type']) && $options['field_type'] != ''){
				$htmlparams = array_merge($options, array());
				unset($htmlparams['field_type']);
				$html .= '<td>'.self::specialField(self::resolveArrayName($value,$field[0],$index), $value->__get($field[0]), $options['field_type'], $htmlparams).'</td>';
			} else if (isset($options['htmlTag']) && $options['htmlTag'] != ''){
				$html .= '<td><' . $options['htmlTag'];
				if (isset($field[0]) && $field[0] != ''){
					$html .= ' id="' . self::getIdByName(self::resolveArrayName($value,$field[0],$index)) . '"';
				}
				$html .= '>';
				if (isset($options['htmlContent']) && $options['htmlContent'] != ''){
					$html .= $options['htmlContent'];
				}
				$html .= '</' . $options['htmlTag'] .  '></td>';
			} else if (isset($options['type_weight']) && $options['type_weight'] != ''){
				$htmlparams = array_merge($options, array('style'=>'width:70%'));
				if(isset($htmlparams['class'])){
					$htmlparams['class'] = $htmlparams['class'] . ' viewWithUnit';
				} else {
					$htmlparams['class'] = 'viewWithUnit';
				}
				unset($htmlparams['type_weight']);
				$html .= '<td>';
				$fieldValue = $value->__get($field[0]);
				//list($unit_values, $multiplier) = self::changeUnitMultipliers(array('1'=>'g','1000'=>'kg', '453.59237'=>'lb', '28.349523125'=>'oz'), $options['type_weight']);
				$unit_values = array('1'=>'g','1000'=>'kg', '453.59237'=>'lb', '28.349523125'=>'oz');
				$fliped_units = array_flip($unit_values);
				$displayValue = $fieldValue / $fliped_units[$options['type_weight']];
				$html .= self::textField(self::resolveArrayName($value,$field[0].'_VIEW',$index), $fieldValue, $htmlparams);
				$html .= self::dropDownList(self::resolveArrayName($value,$field[0].'_UNIT',$index), $options['type_weight'], $unit_values, array('class'=>'unit','style'=>'width:20%'));
				$html .= self::hiddenField(self::resolveArrayName($value,$field[0],$index), $fieldValue, array('class'=>'withUnit'));
				$html .= '</td>';
			} else if (isset($options['type_time']) && $options['type_time'] != ''){
				$htmlparams = array_merge($options, array('style'=>'width:70%'));
				if(isset($htmlparams['class'])){
					$htmlparams['class'] = $htmlparams['class'] . ' viewWithUnit';
				} else {
					$htmlparams['class'] = 'viewWithUnit';
				}
				unset($htmlparams['type_time']);
				$html .= '<td>';
				$fieldValue = $value->__get($field[0]);
				//list($unit_values, $multiplier) = self::changeUnitMultipliers(, $options['type_time']);
				$unit_values = array('60'=>'m', '3600'=>'h', '1'=>'s');
				$fliped_units = array_flip($unit_values);
				$displayValue = $fieldValue / $fliped_units[$options['type_time']];
				$html .= self::textField(self::resolveArrayName($value,$field[0].'_VIEW',$index), $fieldValue, $htmlparams);
				$html .= self::dropDownList(self::resolveArrayName($value,$field[0].'_UNIT',$index), $options['type_time'], $unit_values, array('class'=>'unit','style'=>'width:20%'));
				$html .= self::hiddenField(self::resolveArrayName($value,$field[0],$index), $fieldValue, array('class'=>'withUnit'));
				$html .= '</td>';
			} else {
				$html .= '<td>'.self::textField(self::resolveArrayName($value,$field[0],$index), $value->__get($field[0]), $field[3]).'</td>';
			}
		}
		if (isset($texts['options'])){
			$html .= '<td class="options">';
			if (isset($texts['remove'])){
				$html .= '<div class="remove" title="' . $texts['remove'] . '"></div>';
			}
			if (isset($texts['move up'])){
				$html .= '<div class="up" title="' . $texts['move up'] . '"></div>';
			}
			if (isset($texts['move down'])){
				$html .= '<div class="down" title="' . $texts['move down'] . '"></div>';
			}
			if (isset($texts['add2'])){
				$html .= '<div class="add" title="' . $texts['add2'] . '"></div>';
			}
			$html .= '</td>';
		}
		$html .= '</tr>';
		return $html ;
	}
	
	public static function createInputTable($valueArray, $fieldOptions, $options, $form, $texts) {
		if (isset($options['new'])){
			$new = $options['new'];
			$new->unsetAttributes(); // clear any default values
			if (isset($options['newValues'])){ //add default values if needed
				foreach($options['newValues'] as $key=>$value){
					$new->$key = $value;
				}
			}
			unset($options['new']);
		}
		if (isset($options['newNotClean'])){
			$new = $options['newNotClean'];
			unset($options['newNotClean']);
		}
		
		$showTitles = true;
		if (isset($options['noTitle'])){
			$showTitles = !$options['noTitle'];
			unset($options['noTitle']);
		}
		
		$html = '<table class="addRowContainer">';
		if ($showTitles){
			$html .= '<thead><tr>';
			
			$visibleFields = 0;
			foreach($fieldOptions as $field){
				if (!isset($field[3]['hidden']) || !$field[3]['hidden']){
					$html .='<th>'.$field[1].'</th>';
					$visibleFields++;
				}
			}
			$html .='<th>'.$texts['options'].'</th>';
			$html .= '</tr></thead>';
		} else {
			$visibleFields = 0;
			foreach($fieldOptions as $field){
				if (!isset($field[3]['hidden']) || !$field[3]['hidden']){
					$visibleFields++;
				}
			}
		}
		
		$html .= '<tbody>';
		$i = 1;
		foreach($valueArray as $value){
			$html .= self::inputTableRow((($i % 2 == 1)?'odd':'even'), $fieldOptions, $i, $value, $texts);
			$i++;
		}
		
		if ($new){
			$newhtml = self::inputTableRow('%class%', $fieldOptions, '%index%', $new, $texts);
			$html .= '<tr id="newLine">';
			$html .= '<td colspan="'.$visibleFields.'"><div class="buttonSmall add">' . $texts['add'] . '</div>'. self::hiddenField('addContent', $newhtml, array('disabled'=>'disabled')).self::hiddenField('lastIndex', $i, array('disabled'=>'disabled')).'</td>';
			$html .= '</tr>';
		}
		
		$html .= '</tbody></table>';
		
		return $html;
	}
	
	public static function resizePicture($file, $file_new, $width, $height, $qualitaet, $destType){
		self::resizePictureWithFill($file, $file_new, $width, $height, $qualitaet, $destType, false);
	}
	
	public static function resizePictureWithFill($file, $file_new, $width, $height, $qualitaet, $destType, $fillWhite){
		self::resizePicturePart($file, $file_new, $width, $height, $qualitaet, $destType, 0, 0 ,-1 ,-1, $fillWhite);
	}
	
	public static function resizePicturePart($file, $file_new, $destWidth, $destHeight, $qualitaet, $destType, $src_x, $src_y, $src_w, $src_h, $fillWhite){
		if(!file_exists($file))
			return false;
		$info = getimagesize($file);
		
		if($info[2] == IMAGETYPE_GIF){
			$image = imagecreatefromgif($file);
		} elseif($info[2] == IMAGETYPE_JPEG) {
			$image = imagecreatefromjpeg($file);
		} elseif($info[2] == IMAGETYPE_PNG) {
			$image = imagecreatefrompng($file);
		} else  {
			return false;
		}
		if ($destType == -1){
			$destType = $info[2];
		}
		
	/*    echo $info[0]. " ".$info[1]; //Breite * H�he
		if ($info[0] < $info[1]){
			$temp=$height;
			$width=$height;
			$height=$width;
		}*/
		if (!$fillWhite){
			if ($destWidth > $src_w && $src_w != -1){
				$destWidth = $src_w;
			}
			if ($destHeight > $src_h && $src_h != -1){
				$destHeight = $src_h;
			}
		}
		
		$width = $destWidth;
		$height = $destHeight;
		
		if ($src_w==-1){$src_w=$info[0];}
		if ($src_h==-1){$src_h=$info[1];}
		if ($width && ($src_w < $src_h)){
			$width = ($height / $src_h) * $src_w;
		} else { 
			$height = ($width / $src_w) * $src_h; 
		}
		if ($fillWhite){
			$imagetc = imagecreatetruecolor($destWidth, $destHeight);
		} else {
			$imagetc = imagecreatetruecolor($width, $height);
		}
		
		imagealphablending($imagetc, false);
		imagesavealpha($imagetc, true);
		//if (($info[0] > $width) or ($info[1] > $height) or ($width != $src_w) or ($height != $src_h) or ($src_x != 0) or ($src_y != 0)){
		if (($width != $destWidth) or ($height != $destHeight)){
			if ($fillWhite){
				$xmove=0;
				$ymove=0;
				$whiteCol=imagecolorallocatealpha($imagetc, 255, 255, 255, 0);
				imagefill($imagetc, 0, 0, $whiteCol);
				if ($destWidth>$width){
					$xmove = ($destWidth-$width)/2;
				}
				if ($destHeight>$height){
					$ymove = ($destHeight-$height)/2;
				}
				imagecopyresampled($imagetc, $image, $xmove, $ymove, $src_x, $src_y, $width, $height, $src_w, $src_h);
			} else {
				imagecopyresampled($imagetc, $image, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h);
			}
		} else {
			if ($info[2] == $destType){
				copy($file,$file_new);
				return;
			} else {
				$imagetc = $image;
			}
		}
		/*
		$transparent=imagecolortransparent($image);
		imagecolortransparent($imagetc,$transparent);
		*/
		
		if($destType == IMAGETYPE_GIF){
			imagegif($imagetc, $file_new);  
			//imagejpeg($imagetc, $file_new, $qualitaet);  
		} elseif($destType == IMAGETYPE_JPEG) {
			imagejpeg($imagetc, $file_new, $qualitaet);  
		} elseif($destType == IMAGETYPE_PNG) {
			imagepng($imagetc, $file_new);  
		} else  {
			imagejpeg($imagetc, $file_new, $qualitaet);  
		}
	}
	
	
	public static function changePictureType($file, $file_new, $destType)
	{
		if(!file_exists($file))
			return false;
		
		$info = getimagesize($file);
		if ($destType == -1 || $destType == $info[2]){
			return $info;
		}
		
		if($info[2] == IMAGETYPE_GIF){
			$image = imagecreatefromgif($file);
		} elseif($info[2] == IMAGETYPE_JPEG) {
			$image = imagecreatefromjpeg($file);
		} elseif($info[2] == IMAGETYPE_PNG) {
			$image = imagecreatefrompng($file);
		} else  {
			return false;
		}
		
		$width = $info[0];
		$height = $info[1];
		$qualitaet = 0.8;
		
		//$imagetc = imagecreatetruecolor($width, $height
		//imagecopyresampled($imagetc, $image, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h);
		//$transparent=imagecolortransparent($image);
		//imagecolortransparent($imagetc,$transparent);
		
		$imagetc = $image;
		
		if($destType == IMAGETYPE_GIF){
			imagegif($imagetc, $file_new); 
		} elseif($destType == IMAGETYPE_JPEG) {
			imagejpeg($imagetc, $file_new, $qualitaet);
		} elseif($destType == IMAGETYPE_PNG) {
			imagepng($imagetc, $file_new);
		} else  {
			imagejpeg($imagetc, $file_new, $qualitaet);
		}
		return $info;
	}
	
	public static function getImage($modified, $etag, $pictureFilename, $id, $type, $size){
		//Not using default function to have posibility to set Cache control...
		if (!isset($etag) || $etag === '' || !isset($pictureFilename) || $pictureFilename === ''){
			if ($size > 0 && $size < self::IMG_HEIGHT){
				Yii::app()->controller->redirect(Yii::app()->request->baseUrl . '/pics/unknown.png?size='.$size, true, 307);
			} else {
				Yii::app()->controller->redirect(Yii::app()->request->baseUrl . '/pics/unknown.png', true, 307);
			}
		}
		if ($id != 'backup'){
			if ($modified){
				if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
					//remove information after the semicolon and form a timestamp                                                         
					$request_modified = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
					$request_modified = strtotime($request_modified[0]);
					
					// Compare the mtime on the request to the mtime of the image file                                                      
					if ($modified <= $request_modified) {
						header('HTTP/1.1 304 Not Modified');
						exit();
					}
				}
			}
			
			if ($etag != ''){
				if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
					$request_etag = $_SERVER['HTTP_IF_NONE_MATCH'];  //If-None-Match: �877f3628b738c76a54?
					if ($etag == $request_etag){
						header('HTTP/1.1 304 Not Modified');
						exit();
					}
				}
			}
			
			//header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			//header('Expires: ' . gmdate('D, d M Y H:i:s', (time() + 604800)) . ' GMT');  //604800 = 7 days in seconds
			header('Expires: ' . gmdate('D, d M Y H:i:s', (time() + 86400)) . ' GMT');  //86400 = 1 days in seconds
			if ($modified){
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');
			}
			header('Cache-Control: public');
			header('Etag: ' . $etag);
		}
		if ($size > 0 && $size < self::IMG_HEIGHT){
			$filepath = Yii::app()->getBasePath() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $type;
			$filepath = preg_replace('/\w+\/\.\.\//', '', $filepath);
			$filename = $filepath . DIRECTORY_SEPARATOR . $id . '-' . $size . '.png';
			
			/*
			echo $filename ."\r\n<br>";
			echo file_exists($filepath) ."-path\r\n<br>";
			echo file_exists($filename) ."-file\r\n<br>";
			if (file_exists($filename)){
				echo filectime($filename) ."-time\r\n<br>";
			}
			echo $modified ."\r\n<br>";
			*/
			
			if(file_exists($filename) && filectime($filename) >= $modified){
				$picture = file_get_contents($filename);
			} else {
				if (!file_exists($filepath)){
					$success = mkdir($filepath, 0777, true);
					if (!$success){
						$filename = tempnam(sys_get_temp_dir(), 'img');
					}
				}
				self::resizePicture($pictureFilename, $filename, $size, $size, 0.8, IMAGETYPE_PNG);
				$picture = file_get_contents($filename);
			}
		}
		
		if (!isset($picture) || $picture == ''){
			$picture = file_get_contents($pictureFilename);
		}
		
		header("Content-type: image/png");
		if(ini_get("output_handler")=='')
			header('Content-Length: '.(function_exists('mb_strlen') ? mb_strlen($picture,'8bit') : strlen($picture)));
		//header("Content-Disposition: attachment; filename=\"image_" . $id . ".png\"");
		header('Content-Transfer-Encoding: binary');
		header("Content-type: image/png");
		echo $picture;
	}
	
	public static function generatePictureName($model, $temp){
		$type = get_class($model);
		$primeryKeyField = $model->tableSchema->primaryKey;
		$id = $model[$primeryKeyField];
		if (!isset($id) || $temp){
			$type .= '_temp';
			$id = 'temp' . Yii::app()->user->id;
		}
		$filepath = Yii::app()->getBasePath() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $type;
		$filepath = preg_replace('/\w+\/\.\.\//', '', $filepath);
		$filename = $filepath . DIRECTORY_SEPARATOR . $id . '.png';
		
		if (!file_exists($filepath)){
			$success = mkdir($filepath, 0777, true);
			if (!$success){
				$filename = tempnam(sys_get_temp_dir(), $type . $id);
			}
		}
		return $filename;
	}
	
	public static function fixPicturePathAfterSave($model, $picFieldName, $tempPictureFilename){
		if (strpos($tempPictureFilename, '_temp') !== false){
			//Current img position is tempfile so copy it do used picture
			$filename = self::generatePictureName($model, false);
			copy($tempPictureFilename, $filename);
			$model->__set($picFieldName . '_FILENAME', $filename);
			return true;
		} else {
			return false;
		}
	}
	
	public static function updatePicture($model, $picFieldName, $oldPictureFilename){
		$file = CUploadedFile::getInstance($model,'filename');
		if (isset($file)){
			self::resizePicture($file->getTempName(), $file->getTempName(), self::IMG_WIDTH, self::IMG_HEIGHT, 0.8, IMAGETYPE_PNG);
			$img_md5 = md5(file_get_contents($file->getTempName()));
			$filename = self::generatePictureName($model, true);
			copy($file->getTempName(), $filename);
			$model->__set($picFieldName . '_FILENAME', $filename);
			$model->__set($picFieldName . '_ETAG', $img_md5);
			$model->setScenario('withPic');
		} else {
			if ($model->__get($picFieldName . '_FILENAME') == '' && $oldPictureFilename != ''){
				$model->__set($picFieldName . '_FILENAME', $oldPictureFilename);
				$img_md5 = md5(file_get_contents($oldPictureFilename));
				$model->__set($picFieldName . '_ETAG', $img_md5);
				$model->setScenario('withPic');
			} else {
				$cropInfosAvailable = isset($_POST['imagecrop_w']) && ($_POST['imagecrop_w'] > 0) && isset($_POST['imagecrop_h']) && ($_POST['imagecrop_h'] > 0);
				if (($model->imagechanged == true || $cropInfosAvailable) && $model->__get($picFieldName . '_FILENAME') != ''){
					$filename = $model->__get($picFieldName . '_FILENAME');
					if (strpos($filename, '_temp') === false){
						$tempfile = self::generatePictureName($model, true);
					} else {
						$tempfile = $filename;
					}
					if ($cropInfosAvailable){
						self::resizePicturePart($filename, $tempfile, self::IMG_WIDTH, self::IMG_HEIGHT, 0.8, IMAGETYPE_PNG, $_POST['imagecrop_x'], $_POST['imagecrop_y'], $_POST['imagecrop_w'], $_POST['imagecrop_h'], true);
					} else {
						self::resizePictureWithFill($filename, $tempfile, self::IMG_WIDTH, self::IMG_HEIGHT, 0.8, IMAGETYPE_PNG, true);
					}
					$model->__set($picFieldName . '_FILENAME', $tempfile);
					$img_md5 = md5(file_get_contents($oldPictureFilename));
					$model->__set($picFieldName . '_ETAG', $img_md5);
					$model->imagechanged = false;
				}
			}
		}
	}
	
	private static function uploadPicture($model, $picFieldName){
		$file = CUploadedFile::getInstance($model,'filename');
		if ($file){
			$filename = $file->getTempName();
			/*
			$maxHeight = $_POST['MaxHeight'] * 0.8;
			if ($maxHeight<self::IMG_HEIGHT*1.5){
				$maxHeight = self::IMG_HEIGHT*1.5;
			}
			$maxWidth = $_POST['MaxWidth'] * 0.8;
			if ($maxWidth<self::IMG_WIDTH*1.5){
				$maxWidth = self::IMG_WIDTH*1.5;
			}
			$imginfo = getimagesize($filename);
			if ($imginfo[0]>$maxWidth || $imginfo[1]>$maxHeight){
				self::resizePicture($filename, $filename, $maxWidth, $maxHeight, 0.8, IMAGETYPE_PNG);
			}
			*/
			
			$imginfo = self::changePictureType($filename,$filename, IMAGETYPE_PNG);
			if ($imginfo !== false){
				//if ($imginfo[0]>=self::IMG_WIDTH && $imginfo[1]>=self::IMG_HEIGHT){
				if ($imginfo[0]>=self::IMG_WIDTH || $imginfo[1]>=self::IMG_HEIGHT){
					$img_md5 = md5(file_get_contents($filename));
					$temp_filename = self::generatePictureName($model, true);
					copy($filename, $temp_filename);
					$model->__set($picFieldName . '_FILENAME', $temp_filename);
					$model->__set($picFieldName . '_ETAG', $img_md5);
					$model->imagechanged = true;
					$model->setScenario('withPic');
					return true;
				} else {
					return -3;
				}
			} else {
				return -2;
			}
		} else {
			return -1;
		}
	}
	
	private static function uploadFlickrPicture($model, $picFieldName, $link){
		//http://www.flickr.com/photos/bea_spoli/6931369423) "/sizes/o/" anf�gen
		if (strpos($link, '/sizes/o') === false){
			$parts = explode('/', $link);
			if ($parts[0] != 'http:' && $parts[0] != 'https:'){
				$parts = array_merge(array('http',''), $parts);
			}
			if ($parts[2] != 'www.flickr.com'){
				return -4;
			}
			if (count($params) < 6){
				return -5;
			}
			if ($params[3] != 'photos'){
				return -5;
			}
			$params[6]='sizes';
			$params[7]='o';
			$params = array_slice($params,0, 8);
			$link = implode('/', $params);
		}
		
		require_once('remotefileinfo');
		$content = remote_file($link);
		if (is_string($content) && strpos($content,"ERROR: ") === 0){
			return -6;
		}
		
		$autor = array();
		ereg('<div id="all-sizes-header">.*<a[^>]*>([^<]*)</a>">', $content, $autor);
		$image = array();
		ereg('<div id="allsizes-photo">[^<]*<img src="([^"]*)">', $content, $image);
		
		$imgData = remote_file($image);
		if (!is_string($imgData) || strpos($imgData,"ERROR: ") === 0 || strlen($imgData) == 0){
			return -6;
		}
		
		$model->__set($pictureFieldName . '_AUTH', $autor[0]);
		
		$temp_filename = self::generatePictureName($model, true);
		file_put_contents($temp_filename, $imgData);
		
		$imginfo = self::changePictureType($temp_filename,$temp_filename, IMAGETYPE_PNG);
		if ($imginfo !== false){
			//if ($imginfo[0]>=self::IMG_WIDTH && $imginfo[1]>=self::IMG_HEIGHT){
			if ($imginfo[0]>=self::IMG_WIDTH || $imginfo[1]>=self::IMG_HEIGHT){
				$img_md5 = md5(file_get_contents($temp_filename));
				$model->__set($picFieldName . '_FILENAME', $temp_filename);
				$model->__set($picFieldName . '_ETAG', $img_md5);
				$model->imagechanged = true;
				$model->setScenario('withPic');
				return true;
			} else {
				return -3;
			}
		} else {
			return -2;
		}
	}
	
	public static function uploadImage($modelName, $model, $sessionBackupName, $pictureFieldName){
		if (isset($_POST[$modelName]) || isset($_POST['flickr_link'])){
			$model->attributes=$_POST[$modelName];
			if (isset($_POST['flickr_link']) && $_POST['flickr_link'] != ''){
				$sucessfull = Functions::uploadFlickrPicture($model, $pictureFieldName, $_POST['flickr_link']);
			} else {
				$sucessfull = Functions::uploadPicture($model, $pictureFieldName);
			}
			Yii::app()->session[$sessionBackupName] = $model;
			Yii::app()->session[$sessionBackupName.'_Time'] = time();
			
			if ($sucessfull === true){
				echo '{imageId:"backup", author:"' . $model->__get($pictureFieldName . '_AUTH') . '"}';
				exit;
			} else if ($sucessfull == -1){
				//TODO: Yii::app()->controller->trans->
				echo '{error:"Uploaded File not accessible."}';
				exit;
			} else if ($sucessfull == -2){
				echo '{error:"Unknown Filetype, you can only use GIF, JPG and PNG."}';
				exit;
			} else if ($sucessfull == -3){
				echo '{error:"Image must have minimal a width of ' . self::IMG_WIDTH . ' or a height of ' . self::IMG_HEIGHT . '."}';
				exit;
			} else if ($sucessfull == -4){
				echo '{error:"This is not a Flickr link."}';
				exit;
			} else if ($sucessfull == -5){
				echo '{error:"Invalide Flickr link."}';
				exit;
			} else if ($sucessfull == -6){
				echo '{error:"Error while loading Image from Flickr."}';
				exit;
			}
		} else {
			echo '{error:"invalide Request, no file information submitted"}';
			exit;
		}
	}
	
	/**
	 * Generates a special (HTML5 types) field input for a model attribute.
	 * If the attribute has input error, the input field's CSS class will
	 * be appended with {@link errorCss}.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
	 * attributes are also recognized (see {@link clientChange} and {@link tag} for more details.)
	 * @return string the generated input field
	 * @see clientChange
	 * @see activeInputField
	 */
	public static function activeSpecialField($model,$attribute,$type,$htmlOptions=array()){
		self::resolveNameID($model,$attribute,$htmlOptions);
		if ($type != 'hidden'){
			self::clientChange('change',$htmlOptions);
		}
		//if ($type != 'hidden' && $type != 'text' && $type != 'password'){
			if(isset($htmlOptions['class'])){
				$htmlOptions['class'] = $htmlOptions['class'] . ' input_' . $type;
			} else {
				$htmlOptions['class'] = 'input_' . $type;
			}
		//}
		return self::activeInputField($type,$model,$attribute,$htmlOptions);
	}
	
	public static function specialField($name,$value,$type,$htmlOptions=array()){
		if ($type != 'hidden'){
			self::clientChange('change',$htmlOptions);
		}
		//if ($type != 'hidden' && $type != 'text' && $type != 'password'){
			if(isset($htmlOptions['class'])){
				$htmlOptions['class'] = $htmlOptions['class'] . ' input_' . $type;
			} else {
				$htmlOptions['class'] = 'input_' . $type;
			}
		//}
		return self::inputField($type,$name,$value,$htmlOptions);
	}
	
	
	public static function addLikeInfo($id, $type, $like){
		if(Yii::app()->user->demo){
			Yii::app()->controller->errorText = sprintf(Yii::app()->controller->trans->DEMO_USER_CANNOT_CHANGE_DATA, Yii::app()->createUrl("profiles/register"));
			return false;
		}
		
		$model=Profiles::model()->findByPk(Yii::app()->user->id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		
		$fieldname = 'PRF_' . ((!$like)?'NOT':'') . 'LIKES_' . $type;
		$fieldnameRemove = 'PRF_' . (($like)?'NOT':'') . 'LIKES_' . $type;
		$value = $model->$fieldname;
		if ($value == ''){
			$model->$fieldname = $id;
		} else {
			$values = explode(',', $value);
			$values[] = $id;
			$values = array_unique($values);
			sort($values, SORT_NUMERIC);
			$value = implode(',', $values);
			$model->$fieldname = $value;
		}
		if (isset($model->$fieldnameRemove)){
			$value = $model->$fieldnameRemove;
			if ($value == '' || $value == $id){
				$model->$fieldnameRemove = '';
			} else {
				$values = explode(',', $value);
				for($i=0; $i<count($values); $i++){
					if ($values[$i] == $id){
						unset($values[$i]);
						break;
					}
				}
				$value = implode(',', $values);
				$model->$fieldnameRemove = $value;
			}
		}
		return $model->save();
	}
	
	
	public static function arrayToRelatedObjects($model, $data){
		$relations = $model->getMetaData()->relations;
		foreach($relations as $relation){
			if(isset($data[$relation->name])){
				$relationName = $relation->name;
				if (($relation instanceof CBelongsToRelation) || ($relation instanceof CHasOneRelation)){
					if (isset($model->$relationName)){
						$newModel = $model->$relationName;
					} else {
						$newModel = new $relation->className;
					}
					$newModel->unsetAttributes();
					$newModel->attributes = $data[$relation->name];
					$model->$relationName = self::arrayToRelatedObjects($newModel, $data[$relation->name]);
				} else if(($relation instanceof CManyManyRelation) || ($relation instanceof CHasManyRelation)){
					if (isset($model->$relationName)){
						$newArray = $model->$relationName;
					} else {
						$newArray = array();
					}
					$dataArray = $data[$relation->name];
					$i=0;
					foreach($dataArray as $entry){
						if (isset($newArray[$i])){
							$newModel = $newArray[$i];
						} else {
							$newModel = new $relation->className;
						}
						$newModel->unsetAttributes();
						$newModel->attributes = $entry;
						$newArray[$i] = self::arrayToRelatedObjects($newModel, $entry);
						++$i;
					}
					//remove others
					if (count($newArray)>$i){
						for($j=count($newArray)-1;$j>=$i;--$j){
							unset($newArray[$j]);
						}
					}
					$model->$relationName = $newArray;
				}
			} else if(($relation instanceof CManyManyRelation) || ($relation instanceof CHasManyRelation)){
				$relationName = $relation->name;
				$model->$relationName = array();
			}
		}
		return $model;
	}
	
	public static function browserCheck(){
		if (!isset(Yii::app()->session['browserErrorClosed']) || !Yii::app()->session['browserErrorClosed'] && isset($_SERVER['HTTP_USER_AGENT'])){
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
			$type = explode('|', stat_func::browser_detection($userAgent, 'unknown'));
			if (count($type)>1){
				$type[1] = $type[1]+0;
			} else {
				$type[1] = 0;
			}
			$browserOK = false;
			if (strtolower($type[0]) == 'firefox'){
				if ($type[1]>=12){
					$browserOK = true;
				}
			} else if (strtolower($type[0]) == 'chrome'){
				if ($type[1]>=20){
					$browserOK = true;
				}
			} else if (strtolower($type[0]) == 'internetexplorer'){
				if ($type[1]>=9){
					$browserOK = true;
				}
			}
			if (!$browserOK){
				//echo 'browser type:' . $type[0] . ' version:'  . $type[1] ;
				echo '<div class="browserError">';
				echo '<div class="closeButton"></div>';
				
				//$os = stat_func::os_detection($userAgent, 'unknown');
				//  ' on ' . $os .
				echo 'Sorry but we have not tested our platform with <span class="browserName">' . $type[0] . ' '  . $type[1] . '</span> yet. you can proceed but there may be some functions not working properly. please give us a <a class="actionlink" href="mailto:alexis@everycook.org"> feedback </a> when you try it. we strongly recommend using chrome>20 or firefox>12 for best compatibility.';
				echo '<input type="hidden" id="browserErrorCloseLink" value="' . Yii::app()->createUrl("site/closeBrowserError") . '"/>';
				echo '</div>';
				echo '<div id="modal" style="display:block;"></div>';
			}
		}
	}
	
	public static function objectToArray($d) {
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			if (is_subclass_of($d, 'CActiveRecord')){
				$rels = $d->relations();
				$newd = $d->getAttributes();
				foreach($rels as $key=>$val){
					/*
					try {
						$newd[$key] = $d->$key;
					} catch (exception $e){
						$newd[$key] = 'NULL'; //TODO add any special?
					}*/
					
					//$newd[$key] = $d->getRelated($key);
					if ($d->hasRelated($key)){
						$newd[$key] = $d->$key;
					} else {
						$newd[$key] = NULL; //TODO add any special?
					}
					
				}
				$newd['classname'] = get_class($d);
				$d = $newd;
			} else {
				$newd = get_object_vars($d);
				if (is_array($newd)) {
					$newd['classname'] = get_class($d);
				}
				$d=$newd;
			}
			if (is_array($d)) {
				return array('isObject'=>array_map(__METHOD__, $d));
			} else {
				return $d;
			}
		} else if (is_array($d)) {
			if (count($d) == 0){
				return $d;
			} else {
				return array('isArray'=>array_map(__METHOD__, $d));
			}
		} else {
			return $d;
		}
	}
	
	public static function arrayToObject($d) {
		if (is_array($d)) {
			if (array_key_exists('isArray', $d)){
				$d = $d['isArray'];
				if (!is_array($d) || count($d) == 0) {
					return $d;
				}
				return array_map(__METHOD__, $d);
			} else if (array_key_exists('isObject', $d)){
				$d = $d['isObject'];
				//return (object) array_map(__METHOD__, $d);
				//$d = (object) array_map(__METHOD__, $d);
				//return (simpleClass) $d;
				$classname = $d['classname'];
				unset($d['classname']);
				return new simpleClass(array_map(__METHOD__, $d), $classname);
			} else {
				return $d;
			}
		} else {
			// Return object
			return $d;
		}
	}
	
	
	
	
	public static function mapCActiveRecordToSimpleClass($d) {
		if (is_object($d)) {
			if (is_subclass_of($d, 'CActiveRecord')){
				$newd = $d->getAttributes();
				
				//remove not needed values
				unset($newd['CREATED_BY']);
				unset($newd['CREATED_ON']);
				unset($newd['CHANGED_BY']);
				unset($newd['CHANGED_ON']);
				
				//add related values
				$rels = $d->relations();
				foreach($rels as $key=>$val){
					/*
					try {
						$newd[$key] = $d->$key;
					} catch (exception $e){
						$newd[$key] = 'NULL'; //TODO add any special?
					}
					*/
					if ($d->hasRelated($key)){
						$newd[$key] = $d->$key;
					} else {
						$newd[$key] = 'NULL'; //TODO add any special?
					}
				}
				//echo "is CActiveRecord, exact it is: " . get_class($d) ."<br>\r\n";
				$d = new simpleClass(array_map(__METHOD__, $newd), get_class($d));
				return $d;
			} else {
				$newd = get_object_vars($d);
				$d = new simpleClass(array_map(__METHOD__, $newd), get_class($d));
				return $d;
			}
		} else if (is_array($d)) {
			if (count($d) == 0){
				return $d;
			} else {
				return array_map(__METHOD__, $d);
			}
		} else {
			return $d;
		}
	}
}
?>