<?php
/**
 * library of htmlBuilder for dynamic creation of on-page controls
 *
 * @author MartinHall
 */
class htmlBuilder {
  private $initialised;
  
  function buildCustomControl($paId, $controlType, $label, $instruction, $paOptionList, $tabIndex = null, $content = null, $validation = null, $sharedClass=null, $checked=null) {
    $html = '';
    switch ($controlType) {
      case 0: {
        // cb
        //  == DON'T UNDERSTAND $paO here - probably something to do with ealier misunderstanding that cb could be a multiple option control
        //  $checked = ($content == $paO->option) ? "checked" : "";
        $html .= $this->makeCheckBox($paId, $checked, 'checkboxButton', 'checkbox', '#', '#', $label, true, $tabIndex, $content);          
        break;
      }
      case 5: {
        // rb - NOTE, $content is an array of checked/not checked
        $html .= '<br />';
        $html .= sprintf("<label class=\"checkboxLabel topAlign\" >%s</label>",$label);
        $html .= "<ul>";
        $i = 0;
        foreach ($paOptionList as $qo)
        {
          $html .= "<li>";
          $name = sprintf("rb_%s_%s", $paId, $i);
          $html .= $this->makeFormRadio($paId, $name, $qo['label'], $tabIndex, $content);
          $html .= "</li>";                   
          ++$i;
        }
        $html .= "</ul>";
        if ($validation == 1) {
          $html .= "<div class='validation'>** please choose **</div>";      
        }
        break;
      }
      case 6: {
        // select
          $html.=$this->makeSelect($paId, $label, "", true, $paOptionList, $tabIndex, $content, $validation); 
        break;
      }
      case 1: {
        // single
        $html .= $this->makeFormSingle($paId, $label, "text", $tabIndex, $content, $validation);
        break;
      }
      case 2: {
        // multi
        $html .= $this->makeFormMulti($paId, $label, "text", $tabIndex, $content, $validation);
        break;
      }
      case 3: {
        // email
        $html .= $this->makeFormEmail($paId, "email@example.com", $label, $tabIndex, $content, $validation);
        break;
      }
      case 4: {
        // date
        $html .= makeFormDate($paId, $instruction, "date", $tabIndex, $content, $validation);
        break;
      }
    }
    return $html;
  }
       
  function makeFormMulti($id, $label, $class, $tabIndex=null, $content=null, $validation=null, $sharedClass=null) {
    if ($tabIndex == null) { $tabIndex = 0; } 
    if ($content == null) { $content = ''; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html = sprintf("<label class=\"%s\" for=\"%s\">%s</label>", $sharedClass, $id, $label);
    $html .= sprintf("<textarea class=\"%s %s\" id=\"%s\" name=\"%s\" tabIndex=\"%s\">%s</textarea>", $class, $sharedClass, $id, $id, $tabIndex, $content);
    if ($validation == 1) {
      $html .= "<div class='validation'>** please enter a value **</div>";      
    }
    return $html;
  }
    
  function makeFormSingle($id, $label, $class, $tabIndex=null, $content=null, $validation=null, $sharedClass=null) {
    if ($tabIndex == null) { $tabIndex = 0; }
    if ($content == null) { $content = ''; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html = sprintf("<label for=\"%s\" class=\"%s\">%s</label>", $id, $sharedClass, $label);
    $html .= sprintf("<input type=\"text\" class=\"%s %s\" id=\"%s\" name=\"%s\" value=\"%s\" tabIndex=\"%s\" >", $class, $sharedClass, $id, $id, $content, $tabIndex);
    if ($validation == 1) {
      $html .= "<div class='validation'>** please enter a value **</div>";      
    }
    return $html;
  }

  function makeFormPassword($id, $label, $class, $tabIndex=null, $content=null, $validation=null, $sharedClass=null) {
    if ($tabIndex == null) { $tabIndex = 0; }
    if ($content == null) { $content = ''; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html = sprintf("<label for=\"%s\" class=\"%s\">%s</label>", $id, $sharedClass, $label);
    $html .= sprintf("<input type=\"password\" class=\"%s %s\" id=\"%s\" name=\"%s\" value=\"%s\" tabIndex=\"%s\" >", $class, $sharedClass, $id, $id, $content, $tabIndex);
    if ($validation == 1) {
      $html .= "<div class='validation'>** please enter a value **</div>";      
    }
    return $html;
  }
    
  function makeFormEmail($id, $placeholder, $label, $tabIndex=null, $content=null, $validation=null, $sharedClass=null) {
    if ($tabIndex == null) { $tabIndex = 0; }
    if ($content == null) { $content=''; }  // not currently used as validated by html5 input type
    if ($validation == null) { $validation = false; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html=sprintf("<label for=\"%s\" class=\"%s\">%s</label>", $id, $sharedClass, $label);
    $html.=sprintf("<input type=\"email\" placeholder=\"%s\" id=\"%s\" name=\"%s\" tabIndex=\"%s\" value=\"%s\" />", $placeholder, $id, $id, $tabIndex, $content);
    if ($validation == 1) {
      $html .= "<div class='validation'>** please enter an email address **</div>";
    }
    return $html;
  }
    
  function makeFormDate($id, $label, $class, $tabIndex=null, $content=null, $validation=null, $sharedClass=null) {
    if ($sharedClass == null) { $sharedClass = '';}
    if ($tabIndex == null) { $tabIndex = 0;}
    $html = sprintf("<label for=\"%s\" class=\"%s\">%s (use the date form dd/mm/yyyy)</label>", $id, $sharedClass, $label);
    $html .= sprintf("<input id=\"%s\" type=\"date\" name=\"%s\" class=\"%s %s\" tabIndex=\"%s\" value=\"%s\" />", $id, $id, $class, $sharedClass, $tabIndex, $content);
    if ($validation == 1) {
      $html .= "<div class='validation'>** please choose **</div>";      
    }
    return $html;
  }
      
  function makeFormRadio($id, $name, $optionValue, $tabIndex=null, $content=null, $sharedClass=null) {
    if ($tabIndex == null) { $tabIndex = 0; } 
    if ($content == null) { $checked = ""; } else { $checked = ($content == $name) ? "checked" : ""; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html = sprintf("<label for \"%s\" class=\%s\">%s</label>", $id, $sharedClass, $optionValue); //
    $html.= sprintf("<input type=\"radio\" class=\"%s\" name=\"%s\" id=\"%s\" tabIndex=\"%s\" value=\"%s\" %s/>", $sharedClass, $name, $id, $tabIndex, $content, $checked);
    return $html;
  }
    
  function makeSelect($id, $label, $class, $enabled, $optionList, $tabIndex=null, $content=null, $validation=null, $sharedClass=null) {
    if ($tabIndex == null) { $tabIndex = 0;}
    if ($content == null) { $content = 0; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html = "";   
    $html .= sprintf("<label for=\"%s\" class=\"%s\">%s</label>", $id, $sharedClass, $label);
    $ds = ($enabled===true)?" ":" disabled=\"disabled\"";
    $html .= sprintf("<select%s id=\"%s\" name=\"%s\" tabIndex=\"%s\" class=\"%s %s\">", $ds, $id, $id, $tabIndex, $class, $sharedClass);
    foreach ($optionList as $optionPair) {
      $ss = ($optionPair['id'] == $content) ? "selected=\"true\"" : "";
      $html .= sprintf("<option value=\"%s\"%s>%s</option>", $optionPair['id'], $ss, $optionPair['label']);
    }
    $html.="</select>";
    if ($validation == 1) {
      $html .= "<div class='validation'>** please choose **</div>";      
    }
   return $html;
  }
        
  function makeCheckBox($id, $checked, $class, $type, $name, $value, $label, $enabled, $tabIndex=null, $content=null, $validation=null, $sharedClass=null) {
    $cs=($checked==1)?"checked=\"checked\"":"";
    $ds=($enabled==true)?"":"disabled=\"disabled\"";
    if ($tabIndex == null) { $tabIndex = 0; }
    if ($content == null) { $content = $value; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html=sprintf("<input class=\"%s %s\" type=\"%s\" name=\"%s\" value=\"%s\" id=\"%s\" %s %s tabIndex=\"%s\" />",$class, $sharedClass, $type, $name, $value, $id, $cs, $ds, $tabIndex);
    $html.=sprintf("<label class=\"checkboxLabel %s\" for=\"%s\">%s</label>", $sharedClass, $id, $label);
    // no validation on a checkbox - but passsed for consistency
    return $html;
  }
  
  function makeButton($id, $text, $class, $label=null, $tabIndex=null, $type=null, $sharedClass=null) {
    if ($tabIndex == null) { $tabIndex = 0; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html = '';
    if ($label != null) { $html .= sprintf("<label for=\"id\" class=\"%s\">%s</label>", $id, $sharedClass, $label); }
    if ($type == null) {
      $html .= sprintf("<input id=\"%s\" class=\"%s %s\" value=\"%s\" tabIndex=\"%s\"/>", $id, $class, $sharedClass, $text, $tabIndex);
    }
    else {
      $html .= sprintf("<input id=\"%s\" type=\"%s\" class=\"%s %s\" value=\"%s\" tabIndex=\"%s\"/>", $id, $type, $class, $sharedClass, $text, $tabIndex);      
    }
    return $html;
  }
  
  function makeALink($id, $text, $enabled=null, $externalLink=null) {
    $enabledClass = $enabled ? "canUse" : "noUse";
    if ($externalLink === null) {
      return "<a class=\"$text $enabledClass\" id=\"$id\"href=\"#\">$text</a>";          
    }
    else {
      return "<a class=\"$text $enabledClass\" id=\"$id\"href=$externalLink>$text</a>";                
    }
  }
      
  function makeJudgeChoice($id, $label, $name) {
    $html =sprintf("<div id=\"%s\" class=\"judgesChoice\">", $id);
      $html.="<div id=\"cLeft\" class=\"choice\">";
        $html.=sprintf("<input type=\"radio\" name=\"%s\" value=\"left_judgement\" style=\"display: inline;\"/>", $name);
        $html.=sprintf("<label>%s</label>", $label);
      $html.="</div>";
      $html.="<div id=\"cRight\" class=\"choice\">";
        $html.=sprintf("<input type=\"radio\" name=\"%s\" value=\"right_judgement\" style=\"display: inline;\"/>", $name);
        $html.=sprintf("<label>%s</label>", $label);
      $html.="</div>";
    $html.="</div>";
    return $html;
  }
    
  function makeJudgeLikert($header, $labels) {
    $html = "<div class=\"judgesConfidence\" style=\"display: block;\">";
      $html.= sprintf("<h2>%s</h2>", $header);
      $html.= "<div class=\"confidenceSlider\"><div class=\"slideBar\"><div class=\"slidePointer\"></div>";               
        $i=1;
        foreach ($labels as $ll) {
          $html.= sprintf("<div class=\"interval\" id=\"interval%s\">%s</div>",$i , $ll['label']);
          ++$i;
        }
      $html.= "</div></div>";
    $html.= "</div>";
    return $html;
  }
  
  function makeJudgeReason($id, $label, $characterGuidance) {
    $html = "<div class=\"judgesReason\">";
      $html.= sprintf("<h2>%s</h2>", $label);
      $html.= sprintf("<textarea id=\"%s\"></textarea>", $id);
      $html.= sprintf("<p>%s</p>", $characterGuidance);
    $html.= "</div>";
    return $html;
  }

  function makeExtraJudgeLikert($header, $labels) {
    $html = "<div class=\"judgesConfidence\" style=\"display: block;\">";
      $html.= sprintf("<h2>%s</h2>",$header);
      $html.= "<div class=\"confidenceSlider\"><div class=\"extraSlideBar\"><div class=\"extraSlidePointer\"></div>";               
        $i=1;
        foreach ($labels as $ll) {
          $html.= sprintf("<div class=\"extraInterval\" id=\"extraInterval%s\">%s</div>", $i, $ll['label']);
          ++$i;
        }
      $html.="</div></div>";
    $html.="</div>";
    return $html;
  }

  function makeFinalJudgeLikert($header, $labels) {
    $html = "<div class=\"finalJudgesConfidence\" style=\"display: block;\">";
      $html.= sprintf("<h2>%s</h2>", $header);
      $html.= "<div class=\"confidenceSlider\"><div class=\"finalSlideBar\"><div class=\"finalSlidePointer\"></div>";               
        $i=1;
        foreach ($labels as $ll) {
          $html.= sprintf("<div class=\"finalInterval\" id=\"finalInterval%s\">%s</div>",$i,$ll['label']);
          ++$i;
        }
      $html.= "</div></div>";
    $html.= "</div>";
    return $html;
  }

  function makeJudgeFinalReason($id, $label) {
    $html = "<div class=\"finalJudgesReason\" style=\"display: block;\">";
      $html.= sprintf("<h2>%s</h2>", $label);
      $html.= sprintf("<textarea id=\"%s\"></textarea>", $id);
    $html.= "</div>";
    return $html;
  }

  function makeJudgeFinalChoice($id, $label, $name) {
    $html = sprintf("<div id=\"%s\" class=\"finalJudgesChoice\">", $id);
      $html.= "<div id=\"fcLeft\" class=\"finalChoice\">";
      $html.= sprintf("<input type=\"radio\" name=\"%s\" value=\"left_judgement\"/>", $name);
      $html.= sprintf("<label>%s</label>", $label);
      $html.= "</div>";
      $html.= "<div id=\"fcRight\" class=\"finalChoice\">";
      $html.= sprintf("<input type=\"radio\" name=\"%s\" value=\"right_judgement\"/>", $name);
      $html.= sprintf("<label>%s</label>", $label);
      $html.= "</div>";
     $html.= "</div>";
     return $html;
  }

  function makeJudgeFinalAlignmentOptions($eModel) {
    $html = '';
    if ($eModel->useS1QCategoryControl == 1) {
      $html.= $this->makeS1AlignmentCategoryLikert($eModel);
    }
    return $html;
  }
   
  function makeS1AlignmentCategoryLikert($eModel) {
    $html = "<div class=\"categoryLikert\" style=\"display: block;\">";
      $html.= sprintf("<h2>%s</h2>", $eModel->s1CategoryLabel);
      $html.= "<div class=\"categorySlider\"><div class=\"categorySlideBar\"><div class=\"categorySlidePointer\"></div>"; 
        for ($i=0; $i<count($eModel->s1AlignmentCategoryLabels); $i++) {
          $html.= sprintf("<div class=\"categoryInterval\" id=\"categoryInterval%s\">%s</div>",($i+1) , $eModel->s1AlignmentCategoryLabels[$i]);
        }
      $html.= "</div></div>";
    $html.= "</div>";
    return $html;
  }

  function makeS1AlignmentRespondentLikerts($eModel, $jType) {
  	if ($jType == -1) {
  		// use alignment in classic - jType doesn't make sense in classic
  		$jTypeLabel = "";
		}
		else {
			$jTypeLabel = ($jType == 0) ? $eModel->evenS1Label : $eModel->oddS1Label;
		}
    if ($eModel->useS1AlignmentAsRB == 1) {
      $html = "<div class=\"alignmentRBs\">";
        $html.= "<div class=\"responseOne\" style=\"display: block;\">";
          $html.= sprintf("<h3>%s</h3>", $eModel->s1AlignmentLabel);
          if ($eModel->useS1AlignmentNoneLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentNoneLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_1_1", "irb1", null, null, null, "irb");
            $html.= $eModel->s1AlignmentNoneLabel.$append.'</p>';
          }
          if ($eModel->useS1AlignmentPartlyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentPartlyLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_1_2", "irb1", null, null, null, "irb");
            $html.= $eModel->s1AlignmentPartlyLabel.$append.'</p>';
          }
          if ($eModel->useS1AlignmentMostlyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentMostlyLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_1_3", "irb1", null, null, null, "irb");
            $html.= $eModel->s1AlignmentMostlyLabel.$append.'</p>';
          }
          if ($eModel->useS1AlignmentCompletelyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentCompletelyLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_1_4", "irb1", null, null, null, "irb");
            $html.= $eModel->s1AlignmentCompletelyLabel.$append.'</p>';
          }
          if ($eModel->useS1AlignmentExtraLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentExtraLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_1_5", "irb1", null, null, null, "irb");
            $html.= $eModel->s1AlignmentExtraLabel.$append.'</p>';
          }
        $html.= "</div>";
        $html.= "<div class=\"responseTwo\" style=\"display: block;\">";
          $html.= sprintf("<h3>%s</h3>", $eModel->s1AlignmentLabel);
          if ($eModel->useS1AlignmentNoneLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentNoneLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_2_1", "irb2", null, null, null, "irb");
            $html.= $eModel->s1AlignmentNoneLabel.$append.'</p>';
          }
          if ($eModel->useS1AlignmentPartlyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentPartlyLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_2_2", "irb2", null, null, null, "irb");
            $html.= $eModel->s1AlignmentPartlyLabel.$append.'</p>';
          }
          if ($eModel->useS1AlignmentMostlyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentMostlyLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_2_3", "irb2", null, null, null, "irb");
            $html.= $eModel->s1AlignmentMostlyLabel.$append.'</p>';
          }
          if ($eModel->useS1AlignmentCompletelyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentCompletelyLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_2_4", "irb2", null, null, null, "irb");
            $html.= $eModel->s1AlignmentCompletelyLabel.$append.'</p>';
          }
          if ($eModel->useS1AlignmentExtraLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentExtraLabel == 1 ? $jTypeLabel : "";
            $html.= '<p>'.$this->makeFormRadio("arb_2_5", "irb2", null, null, null, "irb");
            $html.= $eModel->s1AlignmentExtraLabel.$append.'</p>';
          }
        $html.= "</div>";
      $html.= "</div>";            
    }
    else {
      $html = "<div class=\"alignmentLikerts\">";
        $html.= "<div class=\"respondentLikert\" style=\"display: block;\">";
          $html.= sprintf("<h2>%s</h2>", $eModel->s1AlignmentLabel);
          $html.= "<div class=\"respondent1Slider\"><div id=\"r1SlideBar\" class=\"respondent1SlideBar\"><div class=\"respondent1SlidePointer\"></div>"; 
          if ($eModel->useS1AlignmentNoneLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentNoneLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval1\">". $eModel->s1AlignmentNoneLabel.$append ."</div>";          
          }
          if ($eModel->useS1AlignmentPartlyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentPartlyLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval2\">". $eModel->s1AlignmentPartlyLabel.$append ."</div>";          
          }
          if ($eModel->useS1AlignmentMostlyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentMostlyLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval3\">". $eModel->s1AlignmentMostlyLabel.$append ."</div>";          
          }
          if ($eModel->useS1AlignmentCompletelyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentCompletelyLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval4\">". $eModel->s1AlignmentCompletelyLabel.$append ."</div>";          
          }
          if ($eModel->useS1AlignmentExtraLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentExtraLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval5\">". $eModel->s1AlignmentExtraLabel.$append ."</div>";          
          }
          $html.= "</div></div>";
        $html.= "</div>";
        $html.= "<div class=\"respondentLikert\" style=\"display: block;\">";
          $html.= sprintf("<h2>%s</h2>", $eModel->s1AlignmentLabel);
          $html.= "<div class=\"respondent2Slider\"><div id=\"r2SlideBar\" class=\"respondent2SlideBar\"><div class=\"respondent2SlidePointer\"></div>"; 
          if ($eModel->useS1AlignmentNoneLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentNoneLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval1\">". $eModel->s1AlignmentNoneLabel.$append ."</div>";          
          }
          if ($eModel->useS1AlignmentPartlyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentPartlyLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval2\">". $eModel->s1AlignmentPartlyLabel.$append ."</div>";          
          }
          if ($eModel->useS1AlignmentMostlyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentMostlyLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval3\">". $eModel->s1AlignmentMostlyLabel.$append ."</div>";          
          }
          if ($eModel->useS1AlignmentCompletelyLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentCompletelyLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval4\">". $eModel->s1AlignmentCompletelyLabel.$append ."</div>";          
          }
          if ($eModel->useS1AlignmentExtraLabel == 1) {
            $append = $eModel->appendITypetoS1AlignmentExtraLabel == 1 ? $jTypeLabel : "";
            $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval5\">". $eModel->s1AlignmentExtraLabel.$append ."</div>";          
          }
          $html.= "</div></div>";
        $html.= "</div>";    
      $html.= "</div>";      
    }
    return $html;
  }
  
  function makeJudgeAlignmentOptions($eModel, $jType) {
    $html = '';
    $html.= $this->makeS1AlignmentRespondentLikerts($eModel, $jType);
    return $html;

  }
  
  function makeS4IntentionControl($eModel) {
    $html = "<div class=\"iIntentionBlock\">";
    $html.= sprintf("<h2 id=\"iIntentionLabel\">%s</h2>", $eModel->s4IntentionLabel);
    $html.= "<textarea id=\"iIntention\"></textarea>";
    $html.= sprintf("<p>%s</p>", $eModel->step4IntentionLimitGuidance);
    $html.= "</div>";
    return $html;
  }

  function makeS4AlignmentRespondentLikerts($eModel) {
    $html = "<div class=\"alignmentLikerts\">";
      $html.= "<div class=\"respondentLikert\" style=\"display: block;\">";
        $html.= sprintf("<h2>%s</h2>", $eModel->s4AlignmentLabel);
        $html.= "<div class=\"respondent1Slider\"><div id=\"r1SlideBar\" class=\"respondent1SlideBar\"><div class=\"respondent1SlidePointer\"></div>"; 
        $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval1\">". $eModel->s4AlignmentNoneLabel ."</div>";
        $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval2\">". $eModel->s4AlignmentPartlyLabel ."</div>";
        $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval3\">". $eModel->s4AlignmentMostlyLabel ."</div>";
        $html.= "<div class=\"respondentInterval\" id=\"respondent1Interval4\">". $eModel->s4AlignmentCompletelyLabel ."</div>";      
        $html.= "</div></div>";
      $html.= "</div>";
      $html.= "<div class=\"respondentLikert\" style=\"display: block;\">";
        $html.= sprintf("<h2>%s</h2>", $eModel->s4AlignmentLabel);
        $html.= "<div class=\"respondent2Slider\"><div id=\"r2SlideBar\" class=\"respondent2SlideBar\"><div class=\"respondent2SlidePointer\"></div>"; 
        $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval1\">". $eModel->s4AlignmentNoneLabel ."</div>";
        $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval2\">". $eModel->s4AlignmentPartlyLabel ."</div>";
        $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval3\">". $eModel->s4AlignmentMostlyLabel ."</div>";
        $html.= "<div class=\"respondentInterval\" id=\"respondent2Interval4\">". $eModel->s4AlignmentCompletelyLabel ."</div>";      
        $html.= "</div></div>";
      $html.= "</div>";   
    $html.= "</div>";
    return $html;
  }
  
  function makeS4JudgeAlignmentOptions($eModel) {
    $html = '';
    if ($eModel->useS4Intention == 1) {
      $html.= $this->makeS4AlignmentRespondentLikerts($eModel);
    }
    return $html;
  }

  function makeS4AlignmentCategoryLikert($eModel) {
    $html = "<div class=\"categoryLikert\" style=\"display: block;\">";
      $html.= sprintf("<h2>%s</h2>", $eModel->s4QCategoryLabel);
      $html.= "<div class=\"categorySlider\"><div class=\"categorySlideBar\"><div class=\"categorySlidePointer\"></div>"; 
        for ($i=0; $i<count($eModel->s4AlignmentCategoryLabels); $i++) {
          $html.= sprintf("<div class=\"categoryInterval\" id=\"categoryInterval%s\">%s</div>",($i+1) , $eModel->s4AlignmentCategoryLabels[$i]);
        }
      $html.= "</div></div>";
    $html.= "</div>";
    return $html;
  }

  function getCheckPtr($target, $checkArray) {
  $ptr = 0;
  foreach ($checkArray as $ca) {
    if ($ca['id'] == $target) { return $ptr; }
  }
  return -1;
}


  // ---------------------------------------------------------------------------
  // 
  // run-time form methods
  // 
  // ---------------------------------------------------------------------------
    
  function makeStepFormSlider($formName, $pageNo, $qNo, $header, $labels) {
    $id = $formName.'_slider_'.$pageNo.'_'.$qNo;
    $html = sprintf("<div id=\"%s\" class=\"judgesConfidence\" style=\"display: block;\">", $id);
      $html.=sprintf("<h2>%s</h2>", $header);
      $html.="<div class=\"confidenceSlider\"><div class=\"slideBar\"><div class=\"slidePointer\"></div>";               
      $i=1;
      foreach ($labels as $ll) {
        $html.=sprintf("<div class=\"interval\" id=\"interval%s\">%s</div>", $i, $ll['label']);
        ++$i;
      }
      $html.="</div></div>";
    $html.="</div>";
    return $html;
  }
  
  function makeStepFormRadio($formName, $pageNo, $qNo, $label, &$tabIndex, $options) {
    $id = $formName.'_rbgroup_'.$pageNo.'_'.$qNo;
    $html = sprintf("<div id=\"%s\" class=\"surveyRB\" style=\"display: block;\">", $id);
    $html.= sprintf("<p>%s</p>", $label);
    $id = $formName.'_rb_'.$pageNo.'_'.$qNo;
    foreach ($options as $o) {
      $html.= $this->makeFormRadio($id, $id, null, $tabIndex++, $o['label'], "topAlign");
    }
    return $html;
  }
  
  function makeStepFormsChoice($exptId, $formType, $pageNo, $qNo, $header, $labels) {
//    $html=sprintf("<div id=\"%s\" class=\"judgesChoice\" style=\"display: block;\">", $id);
//      $html.="<div id=\"cLeft\" class=\"choice\">";
//      $html.=sprintf("<input type=\"radio\" name=\"%s\" value=\"left_judgement\" style=\"display: inline;\"/>", $name);
//      $html.=sprintf("<label>%s</label>", $label);
//      $html.="</div>";
//      $html.="<div id=\"cRight\" class=\"choice\">";
//      $html.=sprintf("<input type=\"radio\" name=\"%s\" value=\"right_judgement\" style=\"display: inline;\"/>", $name);
//      $html.=sprintf("<label>%s</label>", $label);
//      $html.="</div>";
//    $html.="</div>";
    //return $html;
  }
  
  function makeStepFormsButton($id, $text, $class, $label=null, $tabIndex=null, $type=null, $sharedClass=null) {
    if ($tabIndex == null) { $tabIndex = 0; }
    if ($sharedClass == null) { $sharedClass = '';}
    $html = '';
    if ($label != null) { $html .= sprintf("<label for=\"id\" class=\"%s\">%s</label>", $id, $sharedClass, $label); }
    if ($type == null) {
      $html .= sprintf("<input id=\"%s\" class=\"%s %s\" value=\"%s\" tabIndex=\"%s\"/>", $id, $class, $sharedClass, $text, $tabIndex);
    }
    else {
      $html .= sprintf("<input id=\"%s\" type=\"%s\" class=\"%s %s\" value=\"%s\" tabIndex=\"%s\"/>", $id, $type, $class, $sharedClass, $text, $tabIndex);      
    }
    return $html;
  }
    
  function __construct() { 
      $this->initialised=true;    // dummy to avoid Netbeans error!
  }
}

