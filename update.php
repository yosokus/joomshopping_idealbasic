<?php

$pmDb = JFactory::getDBO();
$pmQuery = "SELECT payment_id FROM #__jshopping_payment_method WHERE payment_class = 'pm_idealbasic'";
$pmDb->setQuery($pmQuery);
$hasPlugin = (int)$pmDb->loadResult();

if(!$hasPlugin)
{

	$pmQuery = "INSERT INTO `#__jshopping_payment_method` (
`payment_id`, `payment_code`, `payment_class`, `payment_params`, 
`name_en-GB`, `name_de-DE`, `description_en-GB`, `description_de-DE`, 
`payment_publish`, `payment_type`, `price`, `price_type`, `tax_id`, `show_descr_in_email`
) 
VALUES (
NULL, 'ideal_basic', 'pm_idealbasic', 'test_mode=1
merchant_id=
sub_id=0
secret_key=
transaction_end_status=6
transaction_failed_status=1
send_user_info=0', 
'iDEAL Basic', 'iDEAL Basic', '', '', 
0, 2, 0.00, 1, -1, 0
)";

	$pmDb->setQuery($pmQuery);
	$pmDb->execute();
}
