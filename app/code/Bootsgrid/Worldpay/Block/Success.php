<?php
/**
 * Magento
 *
 * @category   Bootsgrid
 * @package    Bootsgrid_Worldpay
 * @copyright  Copyright (c) 2017-2018 Bootsgrid(https://www.bootsgrid.com)
 */
namespace Bootsgrid\Worldpay\Block;

class Success extends \Magento\Framework\View\Element\Template
{
    
    protected $request;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $request, 
        array $data = [])
    {
        parent::__construct($context, $data);       
        $this->request = $request;      
    }    

}