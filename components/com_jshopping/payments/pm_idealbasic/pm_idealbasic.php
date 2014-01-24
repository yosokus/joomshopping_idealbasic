<?php
/**
 * Joomshopping iDEAL Basic payment class
 *
 * @package 	JoomShopping
 * @subpackage 	payment
 * @author 		Yos
 * @copyright 	Copyright (C) 2013-2014 Yos. All rights reserved.
 * @license 	GNU General Public License version 2 or later
*/

defined('_JEXEC') or die('Restricted access');

class pm_idealbasic extends PaymentRoot
{

    /**
     * Display payment plugin parameters in admin interface
	 *
     * @param array $pluginConfig - payment plugin config
    */
	function showAdminFormParams($pluginConfig)
	{
		// load language file
		$this->loadLanguageFile();

		//initialize some parameters
		if (!isset($pluginConfig['test_mode'])) { $pluginConfig['test_mode'] = 1; }
		if (!isset($pluginConfig['transaction_end_status'])) { $pluginConfig['transaction_end_status'] = 6; }
		if (!isset($pluginConfig['transaction_failed_status'])) { $pluginConfig['transaction_failed_status'] = 1; }

		$orders = JModelLegacy::getInstance('orders', 'JshoppingModel'); //admin model		
		$order_status = $orders->getAllOrderStatus();

		include(dirname(__FILE__)."/adminparamsform.php");
	}

    /**
     * show form payment. Checkout Step3
     * @param array $params - user params
     * @param array $pluginConfig - payment plugin config
    */
    function showPaymentForm($params, $pluginConfig)
	{
        include(dirname(__FILE__)."/paymentform.php");
    }

    /**
     * Start payment form. Checkout Step6.
     * @param array $pluginConfig - payment plugin config
     * @param jshopOrder $order
    */
	function showEndForm($pluginConfig, $order)
	{
        $jshopConfig = JSFactory::getConfig();

		$order_id = $order->order_id;
		$order_number = $order->order_number;
		$order_currency_code = $order->currency_code_iso;
		$order_hash = $order->order_hash;

		$amount = round((float) $order->order_total, 2);
		$amount = $amount * 100;	// in whole eurcents

		$merchant_id = $pluginConfig['merchant_id'];
		$sub_id = $pluginConfig['sub_id'];
		$secret_key = $pluginConfig['secret_key'];

		if (!$sub_id) { $sub_id=0; }

		$test_mode = (int) $pluginConfig['test_mode'];

		if ($test_mode)
		{
			$ideal_url = 'https://idealtest.secure-ing.com/ideal/mpiPayInitIng.do';
			$site_mode = 'Test Mode';
		}
		else
		{
			$ideal_url = 'https://ideal.secure-ing.com/ideal/mpiPayInitIng.do';
		}

		$order_desc = sprintf(_JSHOP_PAYMENT_NUMBER, $order_number);

		$config = JFactory::getConfig();
		$jdate = JFactory::getDate('+1 hour',$config->get('offset'));
		$valid_until = $jdate->format("Y-m-d\TG:i:s\Z",true); // expiration date

		$payment_type = 'ideal';

		// format - key|merchantID|subID|amount|purchaseID|paymentType|validUntil|itemNumber1|itemDescription1|itemQuantity1|itemPrice1

		$shastring = $secret_key . $merchant_id . $sub_id  . $amount . $order_id . $payment_type
					. $valid_until . $order_number . $order_desc . '1' . $amount ;

		$shastring = html_entity_decode($shastring);

		// remove forbidden characters
		$shastring = str_replace(array("\t", "\n", "\r", " "), "",$shastring);

		$shasign = sha1($shastring);

        $return_url = "index.php?option=com_jshopping&controller=checkout&task=step7";
		$return_url .= "&js_paymentclass=pm_idealbasic&order_id={$order_id}&hash={$order_hash}";

		// return urls
		$url_cancel = $return_url . "&act=cancel";
		$url_success = $return_url . "&act=return";
		$url_error = $return_url . "&act=error";

		// sef urls
		$url_success = SEFLink($url_success,0,1,-1);
		$url_cancel = SEFLink($url_cancel,0,1,-1);
		$url_error = SEFLink($url_error,0,1,-1);

		$post_variables = array(
								"merchantID" => $merchant_id,
								"subID" => $sub_id,
								"purchaseID" => $order_id,
								"amount" => $amount,
								"language" => 'nl',				//always nl
								"currency" => 'EUR',			//always EUR
								"paymentType" => $payment_type,	// always ideal
								"validUntil" => $valid_until,
								"urlCancel" => $url_cancel,
								"urlSuccess" => $url_success,
								"urlError" => $url_error,
								"hash" => $shasign,
								"description" => 'Bestelling:'.$order_number,
								"itemNumber1" => $order_number,		// order reference
								"itemDescription1" => $order_desc,	// order description
								"itemQuantity1" => 1,
								"itemPrice1" => $amount,
							);

?>

<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	</head>
	<body>
		<form id="paymentform" action="<?php echo $ideal_url; ?>" method="post" name="paymentform" >
		<?php

		$log_comment = "Order ID:{$order_id} | Order Total:{$order->order_total}";

		if ($test_mode)
		{
			$log_comment = "Test Mode | " . $log_comment;
		}

		foreach ($post_variables as $name => $value)
		{
			echo "\n".'<input type="hidden" name="' . $name . '" value="' . $value . '" />';

			$log_comment .= "\n{$name}={$value}";	// log parameter
		}

		//log transaction
		saveToLog("paymentdata.log", $log_comment); // joomshopping log function

		?>
		</form>

		<script type="text/javascript">document.getElementById("paymentform").submit();</script>

	</body>
</html>
<?php
		die();
	}

    /**
     * get url parameters for payment. Step7
	 *
     * @param array $pluginConfig - Payment plugin config
	 *
     * @return array
    */
    function getUrlParams($pluginConfig)
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$params = array();
		$params['hash'] = $input->getVar('hash','');
		$params['order_id'] = $input->getInt('order_id','');
        $params['checkHash'] = 1;
		$params['checkReturnParams'] = 1;

		return $params;
    }

    /**
     * Check Transaction
	 *
     * @param array $pluginConfig - Payment plugin config
     * @param jshopOrder $order - Jshop order
     * @param string $act - jshop task
	 *
	 * @return array (jshop_status_code, comment)
    */
	function checkTransaction($pluginConfig, $order, $act)
	{
        // load language file
		$this->loadLanguageFile();

		$jshopConfig = JSFactory::getConfig();

		$app = JFactory::getApplication();
		$input = $app->input;

		// joomshopping status codes
		// 1=>transaction_end_status, 3=>transaction_failed_status, 4=>transaction_cancel_status

		if($act == 'return')
		{
			return array(1, "Order ID {$order->order_id} transaction complete"); // log transaction
		}

		return array(3, _JSHOP_IDEALBASIC_ERROR_PROCESSING_PAYMENT); // log transaction and raise warning
	}

	function loadLanguageFile()
    {
        $lang_dir  = dirname(__FILE__) . '/lang/';
        $lang_file = $lang_dir . JFactory::getLanguage()->getTag() . '.php';

		if (file_exists($lang_file))
		{
			require_once $lang_file;
		}
		else
		{
			require_once $lang_dir . 'en-GB.php';
		}
    }

}
?>
