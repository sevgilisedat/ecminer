<?php

require_once PATH.'bean/Model.php';

class ImportType extends Model{
    //put your code here
    public $id;
    public $name;

    function __construct($db) {
        parent::__construct($db,"import_types",array("name"));
    }
    
	public function add() {
    	$this->_db->select("SQL_CALC_FOUND_ROWS id","import_types","","name='".$this->name."'");
    	$this->_db->select("FOUND_ROWS() AS foundRows");
    	if($this->_db->rows[0]["foundRows"] != "0") {
    		ExceptionController::throwException("Model","ERROR_IMPORT_TYPE_NAME_EXISTS");
    	}
    	parent::add();
    }
    
    public function update($attributes = array()) {
    	$this->_db->select("SQL_CALC_FOUND_ROWS id","import_types","","name='".$this->name."' AND id<>".$this->id);
    	$this->_db->select("FOUND_ROWS() AS foundRows");
    	if($this->_db->rows[0]["foundRows"] != "0") {
    		ExceptionController::throwException("Model","ERROR_IMPORT_TYPE_NAME_EXISTS");
    	}
    	parent::update($attributes);
    }
}
?>
