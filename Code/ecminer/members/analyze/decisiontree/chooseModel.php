<?php
require_once("../../../utils/config.php");

if(!Lib::isMember()) {
	Lib::redirect("members/login.php");
}

if(!isset($_SESSION["DecisionTree"]["AnalyzeClass"]) || empty($_SESSION["DecisionTree"]["AnalyzeClass"])) {
	Error::set("Lütfen bir sınıf seçiniz");
	Lib::redirect("members/analyze/decisiontree/chooseClass.php");
}

include_once(PATH."headers/member.php");
Error::write();

$import_id = intval($_SESSION["DecisionTree"]["ImportId"]);
$testChoice = intval($_SESSION["DecisionTree"]["TestChoice"]);
$class = json_decode($_SESSION["DecisionTree"]["AnalyzeClass"]);

// import acc control
require_once(PATH."bean/Import.php");
$import = new Import($db);
if(!$import->load($import_id)) {
	Error::set("Lütfen geçerli bir veri seti seçiniz");
	Lib::redirect("members/analyze/decisiontree/default.php");
}
if($import->getAccountId() != $_SESSION["MemberId"]) {
	Error::set("Yeterli izniniz yok");
	Lib::redirect("members/analyze/decisiontree/default.php");
}

require_once(PATH."lib/importer/Importer.php");
$importer = Importer::createInstance($db,$import);

if(!empty($_SESSION["DecisionTree"]["DataSet"])) {
	require_once(PATH."lib/core/DataSet.php");
	$dataSet = new DataSet();
	$dataSet->loadFromStdClass(json_decode($_SESSION["DecisionTree"]["DataSet"]));
} else {
	$dataSet = $importer->getDataSet();
}

$dataSet->selectAttributeForClass($class);

?>
<form action="run.php?do=chooseModelData" method="POST" >
	<table class="Table" cellpadding="1" cellspacing="0" border="0" width="100%">
		<tr>
			<td align="center" valign="top">
<?php 
require_once(PATH."controller/DataSetTableController.php");
$tc = new DataSetTableController($db,"imports",$dataSet,"Model Veri Seti (".$importer->name.")","",PATH,"",$testChoice == 2 ? "samples[]" : "");
$tc->run();
?>
			</td>
		</tr>
		<tr>
			<td height="10">&nbsp;</td>
		</tr>
		<tr>
			<td align="center" valign="top">
				<input type="submit" value="Devam" />
				<input type="button" style="margin-left: 10px;" value="Geri Dön" onclick="javascript:window.location='<?php echo PATH;?>members/analyze/decisiontree/chooseClass.php';" />
			</td>
		</tr>
		<tr>
			<td height="10">&nbsp;</td>
		</tr>
	</table>
</form>
<?php


include_once(PATH."footers/member.php");