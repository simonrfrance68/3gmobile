<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;
/**
 * Class MethodList
 */
class MethodList
{
    /**
     * @var array
     */
    private $methodCodes;
    /**
     * MethodList constructor.
     * @param array $methodCodes
     */
    public function __construct(array $methodCodes = [])
    {
        $this->methodCodes = $methodCodes;
    }
    /**
     * @return array
     */
    public function get()
    {
        return $this->methodCodes;
    }
}