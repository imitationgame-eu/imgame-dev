<?php
/**
 * de-reference experiment metadata in admin pages and other
 * 
 * @author MartinHall
 */
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/parseJSON.php';              // parse and escape JSON elements


class metadataConverter {
  private $subjects = array();
  private $countries = array();
  private $locations = array();
  private $languages = array();
  
  // <editor-fold defaultstate="collapsed" desc=" public interface"
  
  public function getIdFromLabel($collectionName, $label) {
    switch ($collectionName) {
      case 'subject' : {
        return $this->getIndexValue($label, $this->subjects);
        break;
      }
      case 'country' : {
        return $this->getIndexValue($label, $this->countries);
        break;
      }
      case 'location' : {
        return $this->getIndexValue($label, $this->locations);
        break;
      }
      case 'language' : {
        return $this->getIndexValue($label, $this->languages);
        break;
      }
      default: {
        if (is_numeric($label)) {
          return intval($label);
        }
        else {
          return -1;  //houston
        }
      }
    }
  }
  
  public function getLabelFromId($collectionName, $id) {
    switch ($collectionName) {
      case 'subject' : {
        return $this->getTextValue($id, $this->subjects);
        break;
      }
      case 'country' : {
        return $this->getTextValue($id, $this->countries);
        break;
      }
      case 'location' : {
        return $this->getTextValue($id, $this->locations);
        break;
      }
      case 'language' : {
        return $this->getTextValue($id, $this->languages);
        break;
      }
    }    
  }
  
  public function getJSONArray($collectionName) {
    switch ($collectionName) {
      case 'subject' : {
        return $this->makeJSONarray($this->subjects);
        break;
      }
      case 'country' : {
        return $this->makeJSONarray($this->countries);
        break;
      }
      case 'location' : {
        return $this->makeJSONarray($this->locations);
        break;
      }
      case 'language' : {
        return $this->makeJSONarray($this->languages);
        break;
      }      
    }
  }
  
  public function getSelectJSONArray($min, $max) {
    $realIndex = 0;
    $json = "[";
    for ($i=$min; $i<$max; $i++) {
      if ($realIndex++ > 0) { $json.= ","; }
      $json.= "\"".$i."\"";
    }
    $json.= "]";
    return $json;
  }
  
  public function getSpacedSelectJSONArray($max) {
    $value = 0;
    $json = "[";
    while ($value < $max) {
      if ($value > 0) { $json.=","; }
      $json.= "\"".$value."\"";
      $value = $value + 5;
    }
    $json.= "]";
    return $json;
  }

  public function getmsSpacedSelectJSONArray($max) {
    $value = 500;
    $count = 0;
    $json = "[";
    while ($value < $max) {
      if ($count++ > 0) { $json.=","; }
      $json.= "\"".$value."\"";
      $value = $value + 500;
    }
    $json.= "]";
    return $json;
  }
  
  // </editor-fold>
                             
  // <editor-fold defaultstate="collapsed" desc=" helpers">
  
  private function makeJSONarray($collection) {
    $json = "[";
    for ($i=0; $i<count($collection); $i++) {
      if ($i > 0) { $json.=","; }
      $json.= JSONparse($collection[$i]['label']);
    }    
    $json.= "]";
    return $json;    
  }

  private function getSelectItems($tblName) {
    global $igrtSqli;
    $items = [];
    $qry = sprintf("SELECT * FROM %s ORDER BY id ASC", $tblName);
    $qResult = $igrtSqli->query($qry);
		while ($qRow = $qResult->fetch_object()) {
			array_push($items, array('id'=>$qRow->id, 'label'=>$qRow->label));
		}
    return $items;
  }
  
  private function getTextValue($needle, $haystack) {
    for ($i=0; $i<count($haystack); $i++) {
      if ($haystack[$i]['id'] == $needle) { return $haystack[$i]['label']; } 
    }
    return "unset";
  }
  
  private function getIndexValue($needle, $haystack) {
    for ($i=0; $i<count($haystack); $i++) {
      if ($haystack[$i]['label'] == $needle) { return $haystack[$i]['id']; } 
    }
    return -1;
  }
  
  private function getSelectLists() {
   // get all control values 
    global $igrtSqli;
    $this->subjects = $this->getSelectItems("igTopics");
    $this->countries = $this->getSelectItems("igCountries");
    $this->languages = $this->getSelectItems("igLanguages");
    $this->locations = $this->getSelectItems("igLocations");
  }
  
  // </editor-fold>
                       
  // <editor-fold defaultstate="collapsed" desc=" constructor">

  function __construct() {
    $this->getSelectLists();
  }
  
  // </editor-fold>

}

