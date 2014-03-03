<?php
require_once("../utils/config.php");
require_once (PATH."utils/Ajax.php");

class ImportTypeAjax extends Ajax {
	
	function __construct($db) {
		parent::__construct($db);
	}
	
	public function getTable() {
		require_once(PATH."controller/AjaxTableController.php");
		foreach($_GET as $key=>$val) {
			$$key = $val;
		}
		try {
			$tc = AjaxTableController::createFromSession($this->db,$tableId,$order,$limit);
			$tc->run(true);
		} catch (Exception $exception) {
			echo $exception->getMessage();
			Error::unsetError();
		}
		exit();
	}
	
	public function deleteRow() {
		foreach($_GET as $key=>$val) {
			$$key = $val;
		}
		echo intval($this->db->query("DELETE FROM ".$tables." WHERE id=".$rowId));
		exit();
	}
	
}

$importTypeAjax = new ImportTypeAjax($db);
$importTypeAjax->run();