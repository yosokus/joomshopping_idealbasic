<?php
/**
 * Joomshopping iDEAL Basic payment class
 *
 * @package 	JoomShopping
 * @subpackage 	payment
 * @author 		Yos Okusanya
 * @copyright 	Copyright (C) 2013-2014 Yos Okusanya. All rights reserved.
 * @license 	GNU General Public License version 2 or later
*/

defined('_JEXEC') or die('Restricted access');

class pm_idealbasic extends PaymentRoot
{
    /**
     * Display payment plugin parameters in admin interface
     *
     * @param array $pluginConfig   payment plugin config
     */
    function showAdminFormParams($pluginConfig)
    {
        $this->loadLanguageFile();

        include(dirname(__FILE__)."/adminparamsform.php");
    }

    /**
     * Shows the form payment. Checkout Step3
     *
     * @param array $params         entered params
     * @param array $pluginConfig   payment plugin config
     */
    function showPaymentForm($params, $pluginConfig)
    {
        include(dirname(__FILE__)."/paymentform.php");
    }

    /**
     * Creates the payment form and redirect to the payment page.
     * Checkout Step6.
     *
     * @param array         $pluginConfig   payment plugin config
     * @param JshopOrder    $order
     */
    function showEndForm($pluginConfig, $order)
    {
        $orderId = $order->order_id;
        $orderNumber = $order->order_number;
        $orderHash = $order->order_hash;

        $amount = round((float) $order->order_total, 2);
        $amount = $amount * 100;	// in whole eurcents

        $merchantId = $pluginConfig['merchant_id'];
        $secret_key = $pluginConfig['secret_key'];
        $testMode = (int)$pluginConfig['test_mode'];
        $subId = $pluginConfig['sub_id'];

        if (!$subId) {
            $subId=0;
        }

        if ($testMode) {
            $idealUrl = 'https://idealtest.secure-ing.com/ideal/mpiPayInitIng.do';
        } else {
            $idealUrl = 'https://ideal.secure-ing.com/ideal/mpiPayInitIng.do';
        }

        $orderDesc = sprintf(_JSHOP_PAYMENT_NUMBER, $orderNumber);

        $jdate = JFactory::getDate('+1 hour', JFactory::getConfig()->get('offset'));
        $validUntil = $jdate->format("Y-m-d\TG:i:s\Z",true); // expiration date

        $paymentType = 'ideal';

        // format - <key><merchantID><subID><amount><purchaseID><paymentType>
        // <validUntil><itemNumber1><itemDescription1><itemQuantity1><itemPrice1>
        $shastring = $secret_key . $merchantId . $subId  . $amount . $orderId . $paymentType
                    . $validUntil . $orderNumber . $orderDesc . '1' . $amount ;

        $shastring = html_entity_decode($shastring);

        // remove forbidden characters
        $shastring = str_replace(array("\t", "\n", "\r", " "), "",$shastring);

        $shasign = sha1($shastring);

        $returnUrl = 'index.php?option=com_jshopping&controller=checkout&task=step7'
            .'&js_paymentclass=pm_idealbasic&order_id=' . $orderId . '&hash=' . $orderHash;

        // return urls
        $cancelUrl = $returnUrl . '&act=cancel';
        $successUrl = $returnUrl . '&act=return';
        $errorUrl = $returnUrl . '&act=error';

        // sef urls
        $successUrl = SEFLink($successUrl, 0, 1, -1);
        $cancelUrl = SEFLink($cancelUrl, 0, 1, -1);
        $errorUrl = SEFLink($errorUrl, 0, 1, -1);

        $postVariables = array(
            'merchantID' => $merchantId,
            'subID' => $subId,
            'purchaseID' => $orderId,
            'amount' => $amount,
            'language' => 'nl',				//always nl
            'currency' => 'EUR',			//always EUR
            'paymentType' => $paymentType,	// always ideal
            'validUntil' => $validUntil,
            'urlCancel' => $cancelUrl,
            'urlSuccess' => $successUrl,
            'urlError' => $errorUrl,
            'hash' => $shasign,
            'description' => 'Bestelling:' . $orderNumber,
            'itemNumber1' => $orderNumber,		// order reference
            'itemDescription1' => $orderDesc,	// order description
            'itemQuantity1' => 1,
            'itemPrice1' => $amount,
        );

?>

<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    </head>
    <body>
        <form id="paymentform" action="<?php echo $idealUrl; ?>" method="post" name="paymentform" >
        <?php

        $logComment = "Order ID:{$orderId} | Order Total:{$order->order_total}";

        if ($testMode) {
            $logComment = "Test Mode | " . $logComment;
        }

        foreach ($postVariables as $name => $value) {
            echo "\n".'<input type="hidden" name="' . $name . '" value="' . $value . '" />';
            $logComment .= "\n{$name}={$value}";	// log parameter
        }

        //log transaction
        saveToLog("paymentdata.log", $logComment);

        ?>
        </form>

        <script type="text/javascript">
            document.getElementById("paymentform").submit();
        </script>
    </body>
</html>
<?php
        die();
    }

    /**
     * Returns the payment options from the url. Step7
     *
     * @param array $pluginConfig
     *
     * @return array
     */
    function getUrlParams($pluginConfig)
    {
        $input = JFactory::getApplication()->input;

        $params = array();
        $params['hash'] = $input->getVar('hash','');
        $params['order_id'] = $input->getInt('order_id','');
        $params['checkHash'] = 1;
        $params['checkReturnParams'] = 1;

        return $params;
    }

    /**
     * Returns the joomshopping transaction status code.
     *
     * joomshopping status codes
     * 1 => transaction_end_status
     * 3 => transaction_failed_status
     * 4 => transaction_cancel_status
     *
     * @param array         $pluginConfig
     * @param JshopOrder    $order
     * @param string        $act           joomshopping controller action
     *
     * @return array (jshop_status_code, comment)
     */
    function checkTransaction($pluginConfig, $order, $act)
    {
        $this->loadLanguageFile();

        if ('return' == $act) {
            // return success code and log transaction
            return array(1, "Order ID {$order->order_id} transaction complete");
        } elseif ('cancel' == $act) {
            // return cancel code and log transaction
            return array(4, "Order ID {$order->order_id} transaction canceled");
        }

        // return error code, log transaction and raise warning
        return array(3, _JSHOP_IDEALBASIC_ERROR_PROCESSING_PAYMENT);
    }

    /**
     * Load language file
     */
    function loadLanguageFile()
    {
        $langDir  = __DIR__ . '/lang/';
        $langFile = $langDir . JFactory::getLanguage()->getTag() . '.php';

        if (file_exists($langFile)) {
            require_once $langFile;
        } else {
            require_once $langDir . 'en-GB.php';	//load default language
        }
    }
}
?>
