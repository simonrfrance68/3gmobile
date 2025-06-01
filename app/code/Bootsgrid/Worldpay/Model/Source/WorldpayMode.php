<?php

namespace Bootsgrid\Worldpay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class WorldpayMode implements ArrayInterface {

	public function toOptionArray() {
		return [
			['value' => 'test', 'label' =>__('Test Mode')],
			['value' => 'live', 'label' => __('Live Mode')]
			];
	}

}
