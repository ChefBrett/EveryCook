<?php

class CookAssistantController extends Controller {
	const STANDBY=0;
	const CUT=1;
	const MOTOR=1;
	const SCALE=2;
	const HEADUP=10;
	const COOK=11;
	const COOLDOWN=12;
	const PRESSUP=20;
	const PRESSHOLD=21;
	const PRESSDOWN=22;
	const PRESSVENT=23;
	
	const HOT=30;
	const PRESSURIZED=31;
	const COLD=32;
	const PRESSURELESS=33;
	const WEIGHT_REACHED=34;
	const COOK_TIMEEND=35;
	const RECIPE_END=39;
	
	const INPUT_ERROR=40;
	const EMERGANCY_SHUTDOWN=41;
	const MOTOR_OVERLOAD=42;
	
	const COMMUNICATION_ERROR=53;
	
	
	const COOK_WITH_PAN = 0;
	const COOK_WITH_LOCAL = 1;
	const COOK_WITH_IP = 2;
	
	const DEVICE_PATH = '/dev/ttyACM0';   //"/dev/ttyUSB0" => arduino  //"/dev/ttyACM0" =>leaflabs maple board
	const GET_STATUS_PATH = '/var/www/db/hw/status';
	const SEND_COMMAND_URL = '/EveryCook/sendcommand.php?command=';
	const GET_STATUS_URL = '/db/hw/status';
	
	public function actionStart(){
		if (isset($_GET['id'])){
			$meal = $this->loadModel($_GET['id'], true);
			
			$info = $this->loadInfoForCourse($meal, 0);
			
			Yii::app()->session['cookingInfo'] = $info;
			
			//$this->checkRenderAjax('index', array('info'=>$info));
			$this->checkRenderAjax('overview', array('info'=>$info));
			//$this->redirect('cookassistant/index');
		} else {
			echo "Error: Please select meal to cook.";
		}
	}

	public function actionGotoCourse($number){
		$info = Yii::app()->session['cookingInfo'];
		$meal = $info->meal;
		if (isset($meal->meaToCous[$number])){
			$info = $this->loadInfoForCourse($meal, $number);
			
			Yii::app()->session['cookingInfo'] = $info;
		} else {
			//TODO error course not exist for meal
		}
		
		$this->checkRenderAjax('index', array('info'=>$info));
	}

	private function loadInfoForCourse($meal, $courseNumber){
		$course = $meal->meaToCous[$courseNumber]->course;
		
		$info = new CookAsisstantInfo();
		$info->meal = $meal;
		$info->courseNr = $courseNumber;
		$info->course = $course;
		$stepNumbers = array();
		$stepStartTime = array();
		$recipeStartTime = array();
		$cookWithEveryCook = array();
		$totalTimes = array();
		$usedTimes = array();
		$timeDiff = array();
		$recipeUsedTime = array();
		$maxTime = 0;
		for ($recipeNr=0; $recipeNr<count($course->couToRecs); ++$recipeNr){
			$stepNumbers[] = -1;
			$stepStartTime[] = time();
			//TODO @Alexis: hier �ndern!
			$cookRecipeWithEveryCook = array(self::COOK_WITH_PAN);
			//$cookRecipeWithEveryCook = ($recipeNr==0)?array(self::COOK_WITH_IP,'10.0.0.1'):array(self::COOK_WITH_PAN);
			//$cookRecipeWithEveryCook = ($recipeNr==0)?array(self::COOK_WITH_LOCAL,self::DEVICE_PATH):array(self::COOK_WITH_PAN);
			$cookWithEveryCook[] = $cookRecipeWithEveryCook;
			$totalTime = 0;
			$recipe = $course->couToRecs[$recipeNr]->recipe;
			$recipe->REC_IMG = NULL;
			
			//Calculate Weight
			$meaToCou = $meal->meaToCous[$courseNumber];
			$couToRec = $course->couToRecs[$recipeNr];
			$meal_gda = $meaToCou['MTC_KCAL_DAY_TOTAL'] * $meal['MEA_PERC_GDA'] / 100;
			$cou_gda = $meal_gda * $meaToCou['MTC_PERC_MEAL'] / 100;
			$rec_gda = $cou_gda * $couToRec['CTR_REC_PROC'] / 100;
			$rec_kcal = $recipe->REC_KCAL;
			if ($rec_kcal != 0){
				$rec_proz = $rec_gda / $rec_kcal;
			} else {
				//TODO: this is a data error!, or a recipe without ingredients .... ?
				$rec_proz = 1;
			}
			$stepsToRemove = array();
			foreach($recipe->steps as $step){
				if (($cookRecipeWithEveryCook[0]!=self::COOK_WITH_LOCAL && $step->action->ACT_SKIP != 'A') || ($cookRecipeWithEveryCook[0]==self::COOK_WITH_LOCAL && $step->action->ACT_SKIP != 'M')){
					$totalTime += $step->STE_STEP_DURATION;
					if (isset($step->ingredient)){
						$step->ingredient->ING_IMG = null;
					}
					if ($step->STE_GRAMS > 0){
						//$step->STE_GRAMS = round($step->STE_GRAMS * $rec_proz,2);
						$step->STE_GRAMS = round($step->STE_GRAMS * $rec_proz);
					}
				} else {
					//remove step
					$stepsToRemove[]=$step->STE_STEP_NO;
				}
			}
			for($i=count($stepsToRemove)-1; $i>=0;--$i){
				$indexToDelete = $stepsToRemove[$i];
				$recipe->steps = array_merge(array_slice($recipe->steps, 0, $indexToDelete), array_slice($recipe->steps, $indexToDelete+1, count($recipe->steps)-$indexToDelete));
			}
			
			$totalTimes[] = $totalTime;
			if ($totalTime>$maxTime){
				$maxTime=$totalTime;
			}
			$usedTimes[] = 0;
			$timeDiff[] = 0;
			$recipeUsedTime[] = 0;
			$recipeStartTime[] = 0;
		}
		$info->stepNumbers = $stepNumbers;
		$info->stepStartTime = $stepStartTime;
		$info->recipeStartTime = $recipeStartTime;
		$info->cookWithEveryCook = $cookWithEveryCook;
		$info->totalTime = $totalTimes;
		$info->usedTime = $usedTimes;
		$info->timeDiff = $timeDiff;
		$info->finishedIn = $maxTime;
		$info->recipeUsedTime = $recipeUsedTime;
		
		$this->loadSteps($info);
		for ($recipeNr=0; $recipeNr<count($course->couToRecs); ++$recipeNr){
			$this->sendActionToFirmware($info, $recipeNr);
		}
		return $info;
	}
	
	public function actionNext($recipeNr, $step){
		$info = Yii::app()->session['cookingInfo'];
		
		if (isset($info->stepNumbers[$recipeNr])){
			if ($info->stepNumbers[$recipeNr] == $step){
				if (isset($info->course->couToRecs[$recipeNr]->recipe->steps[$info->stepNumbers[$recipeNr]+1])){
					$currentTime = time();
					$course = $info->course;
					if (!$info->started){
						for ($recipeNrLoop=0; $recipeNrLoop<count($course->couToRecs); ++$recipeNrLoop){
							$info->stepStartTime[$recipeNrLoop] = $currentTime;
						}
					}
					$info->started = true;
					++$info->stepNumbers[$recipeNr];
					$timeDiff = $currentTime - $info->stepStartTime[$recipeNr];
					
					// echo "<pre>\n";
					// echo "next steps:\n";
					// echo "timeDiff:".$timeDiff."\n";
					// echo "info->usedTime[$recipeNr]:".$info->usedTime[$recipeNr]."\n";
					
					if ($step != -1){
						$info->usedTime[$recipeNr] = $info->usedTime[$recipeNr] + $timeDiff;
						
						$info->recipeUsedTime[$recipeNr] += $info->steps[$recipeNr]->stepDuration;
						$timeDiff = $timeDiff - $info->steps[$recipeNr]->stepDuration;
						$info->timeDiff[$recipeNr] = $info->timeDiff[$recipeNr] + $timeDiff;
						$info->recipeStartTime[$recipeNr] = $currentTime;
					}
					
					// echo "timeDiff:".$timeDiff."\n";
					// echo "info->timeDiff[$recipeNr]:".$info->timeDiff[$recipeNr]."\n";
					// echo "</pre>\n";
					
					/* //the follow code would calculate finishTime
					if ($timeDiff < 0){
						$timeDiff = 0;
					}
					$info->totalTime[$recipeNr] = $info->totalTime[$recipeNr] - $info->steps[$recipeNr]->stepDuration + $timeDiff;
					*/
					
					$info->stepStartTime[$recipeNr] = $currentTime;
					
					
					$maxtime = 0;
					for ($recipeNrLoop=0; $recipeNrLoop<count($course->couToRecs); ++$recipeNrLoop){
						if ($maxtime < $info->totalTime[$recipeNrLoop] - $info->recipeUsedTime[$recipeNrLoop]){
							$maxtime = $info->totalTime[$recipeNrLoop] - $info->recipeUsedTime[$recipeNrLoop];
						}
					}
					$info->finishedIn = $maxtime;
					
					$this->loadSteps($info);
					$this->sendActionToFirmware($info, $recipeNr);
					Yii::app()->session['cookingInfo'] = $info;
				} else {
					//TODO recipe ended, no next step available.
				}
			} else {
				//Don't change step, it is already next (->F5), but update time
				$this->loadSteps($info);
			}
		} else {
			//TODO error recipeNr doesnt exist.
		}
		$this->checkRenderAjax('index', array('info'=>$info));
		//$this->redirect('index');
	}
	
	private function loadSteps($info){
		$course = $info->course;
		$currentSteps = array();
		$currentTime = time();
		$maxtime = 0;
		$maxDiff = 0;
		for ($recipeNr=0; $recipeNr<count($course->couToRecs); ++$recipeNr){
			$mealStep = new CookAsisstantStep();
			$stepNr = $info->stepNumbers[$recipeNr];
			$recipe = $course->couToRecs[$recipeNr]->recipe;
			
			$mealStep->recipeNr = $recipeNr;
			$mealStep->stepNr = $stepNr;
			$mealStep->recipeName = $recipe->__get('REC_NAME_'.Yii::app()->session['lang']);
			$stepStartTime = $info->stepStartTime[$recipeNr];
			if ($stepNr == -1){
				//TODO Calculate Starttime
				$startIn = $info->finishedIn - $info->totalTime[$recipeNr];
				
				$mealStep->stepDuration = $startIn;
				$mealStep->nextStepTotal = $startIn;
				$mealStep->nextStepIn = $stepStartTime - $currentTime + $mealStep->nextStepTotal;
				
				$mealStep->finishedIn = $mealStep->nextStepIn;
				$mealStep->lowestFinishedIn = 0;
				
				if ($info->finishedIn == $info->totalTime[$recipeNr] || $mealStep->nextStepIn <= 0){
					if ($info->finishedIn == $info->totalTime[$recipeNr]){
						$mealStep->nextStepIn = 0; //sometimes it is -1;, time difference between nextStep & loadSteps...
					}
					$mealStep->actionText = "Please start cooking.";
				} else {
					$mealStep->actionText = "Please wait until start time.";
				}
				
				$mealStep->mustWait  = false;
				$mealStep->autoClick = false;
				
				$currentSteps[] = $mealStep;
				
				if ($maxtime < $info->totalTime[$recipeNr]){
					$maxtime = $info->totalTime[$recipeNr];
				}
			} else {
				$step = $recipe->steps[$stepNr];
				
				$mealStep->stepType = $step->STT_ID;
				$mealStep->stepDuration = $step->STE_STEP_DURATION;
				$mealStep->nextStepTotal = $step->STE_STEP_DURATION;
				$mealStep->nextStepIn = $stepStartTime - $currentTime + $mealStep->nextStepTotal;
				
				
				// echo "<pre>\n";
				// echo "loadSteps:\n";
				// echo "stepDuration:".$mealStep->stepDuration."\n";
				// echo "nextStepIn:".$mealStep->nextStepIn."\n";
				
				/*
				$stepUsed = $currentTime+$stepStartTime;
				if ($stepUsed>$mealStep->nextStepTotal){
					$stepUsed = $mealStep->nextStepTotal;
				}
				$mealStep->finishedIn = $info->totalTime[$recipeNr] - $info->usedTime[$recipeNr] - $stepUsed + $info->timeDiff[$recipeNr];
				*/
				
				$timeDiff = $currentTime-$stepStartTime;
				$usedTime = $info->usedTime[$recipeNr] + $timeDiff;
				
				// echo "info->usedTime[$recipeNr] :".$info->usedTime[$recipeNr] ."\n";
				// echo "timeDiff:".$timeDiff."\n";
				// echo "usedTime:".$usedTime."\n";
				
				$mealStep->finishedIn = $info->totalTime[$recipeNr] - ($info->recipeUsedTime[$recipeNr] + $timeDiff);
				
				$timeDiff = $timeDiff - $mealStep->stepDuration;
				
				if ($usedTime > $info->recipeUsedTime[$recipeNr]+$mealStep->stepDuration){
					$usedTime = $info->recipeUsedTime[$recipeNr]+$mealStep->stepDuration;
				}
				
				//$mealStep->finishedIn = $info->totalTime[$recipeNr] - $usedTime;
				//$mealStep->finishedIn = $info->totalTime[$recipeNr] - $info->usedTime[$recipeNr] - $timeDiff;
				//$mealStep->finishedIn = $info->recipeUsedTime[$recipeNr];
				$mealStep->lowestFinishedIn = $info->totalTime[$recipeNr] - $info->recipeUsedTime[$recipeNr]-$mealStep->stepDuration;
				
				// echo "info->recipeUsedTime[$recipeNr] :".$info->recipeUsedTime[$recipeNr] ."\n";
				// echo "info->totalTime[$recipeNr] :".$info->totalTime[$recipeNr] ."\n";
				// echo "finishedIn:".$mealStep->finishedIn."\n";
				// echo "lowestFinishedIn:".$mealStep->lowestFinishedIn."\n";
				// echo "</pre>\n";
				
				//$mealStep->inTime = $stepStartTime + $mealStep->nextStepTotal > $currentTime;
				$mealStep->inTime = $mealStep->finishedIn > $mealStep->lowestFinishedIn;
				//TODO nur wenn mit everycook gekocht wird?
				$mealStep->mustWait = $step->STT_ID >= 10 || ($info->cookWithEveryCook[$recipeNr][0]!=self::COOK_WITH_PAN && $step->STT_ID == self::SCALE);
				$mealStep->autoClick = false;
				
				//$finishedAt = $currentTime + $mealStep->finishedIn;
				//$mealStep->finishedAt = date('H:i:s', $finishedAt);
				
				if (!isset($recipe->steps[$stepNr+1])){
					$mealStep->endReached = true;
					$mealStep->inTime = true;
					$mealStep->nextStepIn = 0;
				}
				$textType = ($info->cookWithEveryCook[$recipeNr][0]!=self::COOK_WITH_PAN)?'AUTO_':'MAN_';
				$mealStep->actionText = $step->action->__get('ACT_DESC_'.$textType.Yii::app()->session['lang']);
				if (isset($step->ingredient)){
					$mealStep->ingredientId = $step->ingredient->ING_ID;
					$mealStep->ingredientCopyright = $step->ingredient->ING_IMG_AUTH;
					
					$replText = '<span class="igredient">' . $step->ingredient->__get('ING_NAME_' . Yii::app()->session['lang']) . '</span>';
					if (isset($step->STE_GRAMS) && $step->STE_GRAMS>0){
						$replText .= ' <span class="amount">' . $step->STE_GRAMS . 'g' . '</span> ';
					}
					$mealStep->actionText = str_replace('#objectofaction#', $replText, $mealStep->actionText);
				} else {
					$mealStep->ingredientId = 0;
					$mealStep->ingredientCopyright = '';
				}
				$mealStep->actionText = ($mealStep->stepNr+1) . '. ' . $mealStep->actionText;
				if ($mealStep->stepDuration == 0){
					$mealStep->percent = 1;
				} else {
					$mealStep->percent = 1 - ($mealStep->nextStepIn / $mealStep->stepDuration);
				}
				if ($mealStep->percent > 1){
					$mealStep->percent = 1;
				}
				
				if (isset($info->cookWithEveryCook[$recipeNr]) && ($info->cookWithEveryCook[$recipeNr][0]!=self::COOK_WITH_PAN) && isset($info->steps[$recipeNr])){
					$oldMealstep = $info->steps[$recipeNr];
					if ($oldMealstep->stepNr == $stepNr){
						//no step change, only update
						$mealStep->percent = $oldMealstep->percent;
						$mealStep->currentTemp = $oldMealstep->currentTemp;
						$mealStep->currentPress = $oldMealstep->currentPress;
					}
				}
				
				$currentSteps[] = $mealStep;
				
				if ($maxtime < $info->totalTime[$recipeNr] - $info->recipeUsedTime[$recipeNr]){
					$maxtime = $info->totalTime[$recipeNr] - $info->recipeUsedTime[$recipeNr];
				}
				//TODO: is this correct?
				if ($maxDiff < $timeDiff){
					$maxDiff = $timeDiff;
				}
			}
		}
		$info->finishedIn = $maxtime;
		$info->timeDiffMax = $maxDiff;
		
		$info->steps = $currentSteps;
	}
	
	public function actionUpdateState($recipeNr){
		$info = Yii::app()->session['cookingInfo'];
		
		if (isset($info->cookWithEveryCook[$recipeNr]) && $info->cookWithEveryCook[$recipeNr][0]!=self::COOK_WITH_PAN){
			$state = $this->readActionFromFirmware($info, $recipeNr);
			if (is_string($state) && strpos($state,"ERROR: ") !== false){
				echo '{"error":"' . substr($state, 7) . '"}';
				return;
			}
			
			$mealStep = $info->steps[$recipeNr];
			
			if ($info->steps[$recipeNr]->endReached){
				$additional=', T0:' . $state->T0;
				$additional.=', P0:' . $state->P0;
				$mealStep->currentTemp = $state->T0;
				$mealStep->currentPress = $state->P0;
				echo '{percent:1, restTime:0' .$additional . ', startTime:'.$_GET['startTime'] . '}';
				return;
			}
			
			$recipe = $info->course->couToRecs[$recipeNr]->recipe;
			$step = $recipe->steps[$info->stepNumbers[$recipeNr]];
			$executetTime = time() - $info->stepStartTime[$recipeNr];
			
			$currentTime = time();
			$stepStartTime = $info->stepStartTime[$recipeNr];
			$mealStep->nextStepIn = $stepStartTime - $currentTime + $mealStep->nextStepTotal;
			$mealStep->inTime = $stepStartTime + $mealStep->nextStepTotal < $currentTime;
			$restTime = $mealStep->nextStepIn;
			
			//$restTime = $state->STIME;
			$additional='';
			if ($state->SMODE==self::STANDBY || $state->SMODE==self::CUT || $state->SMODE==self::MOTOR || $state->SMODE==self::COOK || $state->SMODE==self::PRESSHOLD || $state->SMODE==self::COOK_TIMEEND || $state->SMODE==self::RECIPE_END){
				//$percent = 1 - ($state->STIME / $step->STE_STEP_DURATION);
				$percent = 1 - ($restTime / $mealStep->nextStepTotal);
			} else if ($state->SMODE==self::SCALE || $state->SMODE==self::WEIGHT_REACHED){
				$weight = floor($state->W0);
				$percent = $weight / $step->STE_GRAMS;
				$additional=', W0:' . $state->W0;
				if ($percent>0.05){ //>5%
					//$restTime = round(($executetTime / $percent) - $executetTime);
					$text = '<span class=\"igredient\">' . $step->ingredient->__get('ING_NAME_' . Yii::app()->session['lang']) . '</span> <span class=\"amount\">' . $step->STE_GRAMS . 'g' . '</span>: ' . round($percent*100) . '% / ' . round($state->W0) . 'g';
					if ($percent>1.05){
						$text = '<span class=\"toMuch\">' . $text . '</span>';
					}
					$additional .= ', text: "' . $text . '"';
				}
			} else if ($state->SMODE==self::HEADUP || $state->SMODE==self::HOT){
				$percent = $state->T0 / $step->STE_CELSIUS;
				if ($percent>0.05){ //>5%
					$restTime = round(($executetTime / $percent) - $executetTime);
				}
			} else if ($state->SMODE==self::COOLDOWN || $state->SMODE==self::COLD){
				$percent = $step->STE_CELSIUS / $state->T0; //TODO: correct?
				if ($percent>0.05){ //>5%
					$restTime = round(($executetTime / $percent) - $executetTime);
				}
			} else if ($state->SMODE==self::PRESSUP || $state->SMODE==self::PRESSURIZED){
				$percent = $state->P0 / $step->STE_KPA;
				if ($percent>0.05){ //>5%
					$restTime = round(($executetTime / $percent) - $executetTime);
				}
			} else if ($state->SMODE==self::PRESSDOWN  || $state->SMODE==self::PRESSVENT ||$state->SMODE==self::PRESSURELESS){
				$percent = $step->STE_KPA / $state->P0; //TODO: correct?
				if ($percent>0.05){ //>5%
					$restTime = round(($executetTime / $percent) - $executetTime);
				}
			} else if ($state->SMODE==self::INPUT_ERROR){
				echo '{"error":"' . 'Input Error' . '"}';
				return;
			} else if ($state->SMODE==self::EMERGANCY_SHUTDOWN){
				echo '{"error":"' . 'Emergency shutdown!'  . '"}';
				return;
			} else if ($state->SMODE==self::MOTOR_OVERLOAD){
				echo '{"error":"' . 'Motor overload'  . '"}';
				return;
			} else {
				echo '{"error":"' . 'Unknown EveryCook State/Mode:' . $state->SMODE . '"}';
				return;
			}
			$percent = round($percent, 2);
			if ($state->SMODE >= 30 && $state->SMODE <= 39){
				//Auto Next:
				if ($state->SMODE == self::WEIGHT_REACHED){
					if ($percent>=0.95 && $percent<=1.05){
						//Wait 5 Sec with no change
						if ($mealStep->percent == $percent && $mealStep->weightReachedTime != 0){
							if ($currentTime - $mealStep->weightReachedTime >=5){
								$additional.=', gotoNext: true';
							}
						} else {
							$mealStep->weightReachedTime = $currentTime;
						}
					} else {
						$mealStep->weightReachedTime = 0;
					}
				} else {
					$additional.=', gotoNext: true';
				}
			}
			
			$mealStep->percent = $percent;
			$mealStep->nextStepIn = $restTime;
			
			$additional.=', T0:' . $state->T0;
			$additional.=', P0:' . $state->P0;
			echo '{percent:' . $percent . ', restTime:' . $restTime .$additional . ', startTime:'.$_GET['startTime'] . '}';
			
			//{"T0":100,"P0":0,"M0RPM":0,"M0ON":0,"M0OFF":0,"W0":0,"STIME":30,"SMODE":10,"SID":0}
		}
	}
	
	private function sendActionToFirmware($info, $recipeNr){
		if (isset($info->cookWithEveryCook[$recipeNr]) && $info->cookWithEveryCook[$recipeNr][0]!=self::COOK_WITH_PAN){
			if (isset($info->course->couToRecs[$recipeNr]->recipe->steps[$info->stepNumbers[$recipeNr]])){
				$step = $info->course->couToRecs[$recipeNr]->recipe->steps[$info->stepNumbers[$recipeNr]];
				if ($info->steps[$recipeNr]->endReached){
					$command='{"T0":0,"P0":0,"M0RPM":0,"M0ON":0,"M0OFF":0,"W0":0,"STIME":0,"SMODE":'.self::RECIPE_END.',"SID":0}';
				} else {
					$command='{"T0":'.$step->STE_CELSIUS.',"P0":'.$step->STE_KPA.',"M0RPM":'.$step->STE_RPM.',"M0ON":'.$step->STE_STIR_RUN.',"M0OFF":'.$step->STE_STIR_PAUSE.',"W0":'.$step->STE_GRAMS.',"STIME":'.$step->STE_STEP_DURATION.',"SMODE":'.$step->STT_ID.',"SID":'.$step->STE_STEP_NO.'}';
				}
				
				$dest = $info->cookWithEveryCook[$recipeNr];
				
				if ($dest[0] == self::COOK_WITH_LOCAL){
					$fw = fopen($dest[1], "w");
					if (fwrite($fw, $command)) {
					} else {
						//TODO error an send command...
					}
					fclose($fw);
				} else if ($dest[0] == self::COOK_WITH_IP){
					require_once("remotefileinfo.php");
					$inhalt=remote_fileheader('http://'.$dest[1].self::SEND_COMMAND_URL.$command); //remote_file
					if (is_string($inhalt) && strpos($inhalt, 'ERROR: ') !== false){
						//TODO error an send command...
					}
				}
			}
		}
	}
	
	private function readActionFromFirmware($info, $recipeNr){
		require_once("remotefileinfo.php");
		$inhalt=remote_file("http://10.0.0.1/db/hw/status");
		
		$dest = $info->cookWithEveryCook[$recipeNr];
		$inhalt = '';
		if ($dest[0] == self::COOK_WITH_LOCAL){
			$fw = fopen(self::GET_STATUS_PATH, "r");
			if ($fw !== false){
				while (!feof($fw)) {
					$inhalt .= fread($fw, 128);
				}
				fclose($fw);
			} else {
				//TODO: error on read status
				$inhalt = 'ERROR: $errstr ($errno)';
			}
		} else if ($dest[0] == self::COOK_WITH_IP){
			require_once("remotefileinfo.php");
			$inhalt=remote_file('http://'.$dest[1].self::GET_STATUS_URL);
		}
		
		//$inhalt='{"T0":100,"P0":0,"M0RPM":0,"M0ON":0,"M0OFF":0,"W0":0,"STIME":5,"SMODE":1,"SID":0}';
		if (strpos($inhalt,"ERROR: ") !== false){
			$inhalt = str_replace("\r\n","", $inhalt);
			$inhalt = str_replace("<br />","", $inhalt);
			$inhalt = trim($inhalt);
			return $inhalt;
		} else {
			$jsonValue=json_decode($inhalt);
			return $jsonValue;
		}
	}
	
	private function sendStopToFirmware($info){
		for ($recipeNr=0; $recipeNr<count($info->$course->couToRecs); ++$recipeNr){
			if (isset($info->cookWithEveryCook[$recipeNr]) && $info->cookWithEveryCook[$recipeNr]){
				$command='{"T0":0,"P0":0,"M0RPM":0,"M0ON":0,"M0OFF":0,"W0":0,"STIME":0,"SMODE":'.self::RECIPE_END.',"SID":0}';
				
				$dest = $info->cookWithEveryCook[$recipeNr];
				
				if ($dest[0] == self::COOK_WITH_LOCAL){
					$fw = fopen($dest[1], "w");
					if (fwrite($fw, $command)) {
					} else {
						//TODO error an send command...
					}
					fclose($fw);
				} else if ($dest[0] == self::COOK_WITH_IP){
					require_once("remotefileinfo.php");
					$inhalt=remote_fileheader('http://'.$dest[1].self::SEND_COMMAND_URL.$command); //remote_file
					if (strpos($inhalt, 'ERROR: ') !== false){
						//TODO error an send command...
					}
				}
			}
		}
	}
	
	public function actionNextCourse(){
		$info = Yii::app()->session['cookingInfo'];
		$this->actionGotoCourse($info->courseNr + 1);
	}
	
	public function actionAbort() {
		//TODO abort cooking
		$info = Yii::app()->session['cookingInfo'];
		sendStopToFirmware($info);
		$this->checkRenderAjax('abort');
	}

	public function actionIndex(){
		$info = Yii::app()->session['cookingInfo'];
		$this->loadSteps($info);
		$this->checkRenderAjax('index', array('info'=>$info));
	}
	
	public function actionOverview() {
		$info = Yii::app()->session['cookingInfo'];
		$this->loadSteps($info);
		if(isset($_POST['cookwith'])){
			for($i=0; $i<count($_POST['cookwith']);++$i){
				if ($_POST['cookwith'][$i] === 'local'){
					$info->cookWithEveryCook[$i] = array(self::COOK_WITH_LOCAL,self::DEVICE_PATH);
				} else if ($_POST['cookwith'][$i] === 'remote'){
					$info->cookWithEveryCook[$i] = array(self::COOK_WITH_IP,$_POST['remoteip'][$i]);
				} else if ($_POST['cookwith'][$i] === 'pan'){
					$info->cookWithEveryCook[$i] = array(self::COOK_WITH_PAN);
				}
			}
			
			Yii::app()->session['cookingInfo'] = $info;
		}
		$this->checkRenderAjax('overview', array('info'=>$info));
	}

	public function actionPrev() {
		//TODO is a "back" action needed?
		$this->checkRenderAjax('prev');
	}
	
	public function actionEnd(){
		//TODO stop cooking
		$info = Yii::app()->session['cookingInfo'];
		sendStopToFirmware($info);
		$this->checkRenderAjax('end');
	}
	
	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=Meals::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}
}