<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

interface Update
{
    public function apply($payment);
    public function getTargetOrderCode();
}
