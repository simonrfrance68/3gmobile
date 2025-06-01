<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment\Update;

class Defaultupdate
    extends \Sapient\Worldpay\Model\Payment\Update\Base
    implements \Sapient\Worldpay\Model\Payment\Update
{
    /** @var \Sapient\Worldpay\Helper\Data */
    private $_configHelper;
    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Payment\State $paymentState
     * @param \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment
     * @param \Sapient\Worldpay\Helper\Data $configHelper        
     */
    public function __construct(
        \Sapient\Worldpay\Model\Payment\State $paymentState,
        \Sapient\Worldpay\Model\Payment\WorldPayPayment $worldPayPayment,
        \Sapient\Worldpay\Helper\Data $configHelper
    ) {
        $this->_paymentState = $paymentState;
        $this->_worldPayPayment = $worldPayPayment;
        $this->_configHelper = $configHelper;
    }

    public function apply($payment,$order = null)
    {
        if (!empty($order)) {
            $this->_worldPayPayment->updateWorldPayPayment($this->_paymentState);
        }
    }


}
