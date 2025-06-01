<?php

namespace Bootsgrid\Worldpay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class RequestType implements ArrayInterface
{
	public function toOptionArray() {
		return [
			['value' => 'authorize', 'label' => __('Preauthorization')],
			['value' => 'authorize_capture', 'label' =>__('Authorization')]
		];
	}

}
