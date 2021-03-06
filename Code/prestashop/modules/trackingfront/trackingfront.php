<?php

class TrackingFront extends Module
{
	public function __construct()
	{
		$this->name = 'trackingfront';
		$this->tab = 'Stats';
		$this->version = 1.0;

		parent::__construct();

		$this->displayName = $this->l('Tracking - Front office');
		$this->description = $this->l('Enable your affiliates to access their own statistics.');
	}
	
	public function postProcess()
	{
		global $cookie, $smarty;

		if (Tools::isSubmit('ajaxProductFilter'))
		{
			$fakeEmployee = new Employee();
			$fakeEmployee->stats_date_from = $cookie->stats_date_from;
			$fakeEmployee->stats_date_to = $cookie->stats_date_to;
			$result = Db::getInstance()->getRow('
			SELECT `id_referrer`
			FROM `'._DB_PREFIX_.'referrer`
			WHERE `id_referrer` = '.intval(Tools::getValue('id_referrer')).' AND `passwd` = \''.pSQL(Tools::getValue('token')).'\'');
			if (isset($result['id_referrer']) ? $result['id_referrer'] : false)
				Referrer::getAjaxProduct(intval(Tools::getValue('id_referrer')), intval(Tools::getValue('id_product')), $fakeEmployee);
		}
		elseif (Tools::isSubmit('logout_tracking'))
		{
			unset($cookie->tracking_id);
			unset($cookie->tracking_passwd);
			Tools::redirect('modules/trackingfront/stats.php');
		}
		elseif (Tools::isSubmit('submitLoginTracking'))
		{
			$errors = array();
			$login = trim(Tools::getValue('login'));
			$passwd = trim(Tools::getValue('passwd'));
			if (empty($login))
				$errors[] = $this->l('login is required');
			elseif (!Validate::isGenericName($login))
				$errors[] = $this->l('invalid login');
			elseif (empty($passwd))
				$errors[] = $this->l('password is required');
			elseif (!Validate::isPasswd($passwd))
				$errors[] = $this->l('invalid password');
			else
			{
				$passwd = Tools::encrypt($passwd);
				$result = Db::getInstance()->getRow('
				SELECT `id_referrer`
				FROM `'._DB_PREFIX_.'referrer`
				WHERE `name` = \''.pSQL($login).'\' AND `passwd` = \''.pSQL($passwd).'\'');
				if (!isset($result['id_referrer']) OR !($tracking_id = intval($result['id_referrer'])))
					$errors[] = $this->l('authentication failed');
				else
				{
					$cookie->tracking_id = $tracking_id;
					$cookie->tracking_passwd = $passwd;
					Tools::redirect('modules/trackingfront/stats.php');
				}
			}
			$smarty->assign('errors', $errors);
		}

		if (Tools::isSubmit('submitDatePicker'))
		{
			$cookie->stats_date_from = Tools::getValue('datepickerFrom');
			$cookie->stats_date_to = Tools::getValue('datepickerTo');
		}
		if (Tools::isSubmit('submitDateToday'))
		{
			$cookie->stats_date_from = date('Y-m-d');
			$cookie->stats_date_to = date('Y-m-d');
		}
		if (Tools::isSubmit('submitDateMonth'))
		{
			$cookie->stats_date_from = date('Y-m-01');
			$cookie->stats_date_to = date('Y-m-t');
		}
		if (Tools::isSubmit('submitDateYear'))
		{
			$cookie->stats_date_from = date('Y-01-01');
			$cookie->stats_date_to = date('Y-12-31');
		}
	}
	
	public function isLogged()
	{
		global $cookie;
		if (!$cookie->tracking_id OR !$cookie->tracking_passwd)
			return false;
		$result = Db::getInstance()->getRow('
		SELECT `id_referrer`
		FROM `'._DB_PREFIX_.'referrer`
		WHERE `id_referrer` = '.intval($cookie->tracking_id).' AND `passwd` = \''.pSQL($cookie->tracking_passwd).'\'');
		return isset($result['id_referrer']) ? $result['id_referrer'] : false;
	}
		
	public function displayLogin()
	{
		return $this->display(__FILE__, 'login.tpl');
	}
	
	public function displayAccount()
	{
		global $smarty, $cookie;
		
		if (!isset($cookie->stats_date_from))
			$cookie->stats_date_from = date('Y-m-d');
		if (!isset($cookie->stats_date_to))
			$cookie->stats_date_to = date('Y-m-t');
		$fakeEmployee = new Employee();
		$fakeEmployee->stats_date_from = $cookie->stats_date_from;
		$fakeEmployee->stats_date_to = $cookie->stats_date_to;
		Referrer::refreshCache(array(array('id_referrer' => intval($cookie->tracking_id))), $fakeEmployee);
		
		$referrer = new Referrer(intval($cookie->tracking_id));
		$smarty->assign('referrer', $referrer);
		$smarty->assign('datepickerFrom', $fakeEmployee->stats_date_from);
		$smarty->assign('datepickerTo', $fakeEmployee->stats_date_to);
		
		$displayTab = array(
			'uniqs' => $this->l('Unique visitors'),
			'visitors' => $this->l('Visitors'),
			'visits' => $this->l('Visits'),
			'pages' => $this->l('Pages viewed'),
			'registrations' => $this->l('Registrations'),
			'orders' => $this->l('Orders'),
			'base_fee' => $this->l('Base fee'),
			'percent_fee' => $this->l('Percent fee'),
			'click_fee' => $this->l('Click fee'),
			'sales' => $this->l('Sales'),
			'cart' => $this->l('Average cart'),
			'reg_rate' => $this->l('Registration rate'),
			'order_rate' => $this->l('Order rate'));
		$smarty->assign('displayTab', $displayTab);
		
		$products = Product::getSimpleProducts(intval($cookie->id_lang));
		$productsArray = array();
		foreach ($products as $product)
			$productsArray[] = $product['id_product'];
		
		$echo = '
		<script type="text/javascript" src="../../js/jquery/datepicker/ui/i18n/ui.datepicker-'.Db::getInstance()->getValue('SELECT iso_code FROM '._DB_PREFIX_.'lang WHERE `id_lang` = '.intval($cookie->id_lang)).'.js"></script>
		<script type="text/javascript">
			$("#datepickerFrom").datepicker({
				prevText:"",
				nextText:"",
				dateFormat:"yy-mm-dd"});
			$("#datepickerTo").datepicker({
				prevText:"",
				nextText:"",
				dateFormat:"yy-mm-dd"});
			
			function updateValues()
			{
				$.getJSON("stats.php",{ajaxProductFilter:1,id_referrer:'.$referrer->id.',token:"'.$cookie->tracking_passwd.'",id_product:0},
					function(j) {';
		foreach ($displayTab as $key => $value)
			$echo .= '$("#'.$key.'").html(j[0].'.$key.');';
		$echo .= '		}
				)
			}		

			var productIds = new Array(\''.implode('\',\'', $productsArray).'\');
			var referrerStatus = new Array();
			
			function newProductLine(id_referrer, result, color)
			{
				return \'\'+
				\'<tr id="trprid_\'+id_referrer+\'_\'+result.id_product+\'" style="background-color: rgb(\'+color+\', \'+color+\', \'+color+\');">\'+
				\'	<td align="center">\'+result.id_product+\'</td>\'+
				\'	<td>\'+result.product_name+\'</td>\'+
				\'	<td align="center">\'+result.uniqs+\'</td>\'+
				\'	<td align="center">\'+result.visits+\'</td>\'+
				\'	<td align="center">\'+result.pages+\'</td>\'+
				\'	<td align="center">\'+result.registrations+\'</td>\'+
				\'	<td align="center">\'+result.orders+\'</td>\'+
				\'	<td align="right">\'+result.sales+\'</td>\'+
				\'	<td align="right">\'+result.cart+\'</td>\'+
				\'	<td align="center">\'+result.reg_rate+\'</td>\'+
				\'	<td align="center">\'+result.order_rate+\'</td>\'+
				\'	<td align="center">\'+result.click_fee+\'</td>\'+
				\'	<td align="center">\'+result.base_fee+\'</td>\'+
				\'	<td align="center">\'+result.percent_fee+\'</td>\'+
				\'</tr>\';
			}
			
			function showProductLines()
			{
				var irow = 0;
				for (var i = 0; i < productIds.length; ++i)
					$.getJSON("stats.php",{ajaxProductFilter:1,token:"'.$cookie->tracking_passwd.'",id_referrer:'.$referrer->id.',id_product:productIds[i]},
						function(result) {
							var newLine = newProductLine('.$referrer->id.', result[0], (irow++%2 ? 204 : 238));
							$(newLine).hide().insertBefore($(\'#trid_dummy\')).fadeIn();
						}
					);
			}
		</script>';
		
		$echo2 = '
		<script type="text/javascript">
			updateValues();
			showProductLines();
		</script>';
		
		return $this->display(__FILE__, 'header.tpl').$echo.$this->display(__FILE__, 'account.tpl').$echo2;
	}	
}

?>