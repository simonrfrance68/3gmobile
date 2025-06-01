<?php

use Magento\Framework\App\Bootstrap;
use Magento\Sales\Model\Order;

define('__ROOT__', dirname(dirname(__FILE__))); 
require_once(__ROOT__.'/../app/bootstrap.php');

$params = $_SERVER;
 
$bootstrap = Bootstrap::create(BP, $params);
 
$obj = $bootstrap->getObjectManager();
 
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

// get request variables
$MC_orderid = $_POST['cartId'];
$authCurrency = $_POST['authCurrency'];
$authAmount = $_POST['authAmount'];
$transId = $_POST['transId'];
$AVS = $_POST['AVS'];
$cardType = $_POST['cardType'];
$authMode = $_POST['authMode'];
$transStatus = $_POST['transStatus'];

// check order id
if (empty($MC_orderid) || strlen($MC_orderid) > 50) {
    throw new \Magento\Framework\Exception\LocalizedException(
        __('Missing or invalid order ID.')
    );
}

$storeManager = $obj->get('\Magento\Store\Model\StoreManagerInterface');
$baseurl = $storeManager->getStore()->getBaseUrl();

/** @var \Magento\Sales\Model\Order\Status $status */
$status = $obj->create('Magento\Sales\Model\Order\Status');

// load order for further validation
$order = $obj->get('Magento\Sales\Model\Order')->loadByIncrementId($MC_orderid);
if (!$order->getId()) {
    throw new \Magento\Framework\Exception\LocalizedException(
        __('Order not found.')
    );
}

$session = $obj->create('Magento\Checkout\Model\Session');
$session->setLastOrderId($order->getId());
$session->setLastRealOrderId($MC_orderid);

//DB Transaction
$db_transaction = $obj->get('Magento\Framework\DB\Transaction');

$paymentInst = $order->getPayment()->getMethodInstance();

if ($transStatus == 'Y') {
	$price = number_format($order->getBaseGrandTotal(),2,'.','');
	$currency = $order->getOrderCurrencyCode();

	// check transaction amount
	if ($price != $authAmount) {
		throw new \Magento\Framework\Exception\LocalizedException(
	        __('Transaction currency doesn\'t match.')
	    );
	}

	// check transaction currency
	if ($currency != $authCurrency) {
		throw new \Magento\Framework\Exception\LocalizedException(
	        __('Transaction currency doesn\'t match.')
	    );
	}

	// save transaction information
	$order->getPayment()
		->setTransactionId($transId)
		->setLastTransId($transId)
		->setCcAvsStatus($AVS)
		->setCcType($cardType);

	switch($authMode) {
	    case 'A':
	        if ($order->canInvoice()) {
	            $invoice = $order->prepareInvoice();
	            $invoice->register()->capture();
	            $db_transaction->addObject($invoice)
	                ->addObject($invoice->getOrder())
	                ->save();
	        }
	        $order->addStatusToHistory(Order::STATE_COMPLETE, 'authorize: Customer returned successfully', true);
	        break;
	    case 'E':
	        $order->setState(Order::STATE_PROCESSING, 'preauthorize: Customer returned successfully', true);
	        break;
	}
	//$order->sendNewOrderEmail();
	$order->setEmailSent(true);
	$order->save(); ?>
	<div style="max-width: 800px;margin: 0px auto;text-align: center;margin: 200px auto;">
		<h2>Please wait you will be redirecting to shopping page. <img src="<?php echo $baseurl ?>wpresponse/Squares.gif"></h2>
	</div>
	<meta http-equiv="refresh" content="0;url=<?php echo $baseurl ?>checkout/onepage/success" />
<?php } elseif ($transStatus == 'C') {
	// cancel order
    if ($order->canCancel()) {
        $order->cancel();
        $order->addStatusToHistory(Order::STATE_CANCELED, 'canceled', 'Payment was canceled', true);
        $order->save();
    } ?>
    <div style="max-width: 800px;margin: 0px auto;text-align: center;margin: 200px auto;">
		<h2>Please wait you will be redirecting to shopping page. <img src="<?php echo $baseurl ?>wpresponse/Squares.gif"></h2>
	</div>
	<meta http-equiv="refresh" content="0;url=<?php echo $baseurl ?>bworldpay/index/success" />
<?php } else {
	throw new \Magento\Framework\Exception\LocalizedException(
        __('Transaction was not successful.')
    );
}
?>