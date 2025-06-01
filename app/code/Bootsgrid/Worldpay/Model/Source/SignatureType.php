<?php

namespace Bootsgrid\Worldpay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class SignatureType implements ArrayInterface
{
	public function toOptionArray() {
		return [
			['value' => '1', 'label' => __('Static')],
			['value' => '2', 'label' =>__('Dynamic')]
		];
	}

}
