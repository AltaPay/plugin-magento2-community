<?php
/**
 * AltaPay Module for Magento 2.x.
 *
 * Copyright © 2018 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Model\ScopeInterface;
use SDM\Altapay\Model\SystemConfig;

class CreditCardFormStyle implements ArrayInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * CreditCardFormStyle constructor.
     *
     * @param SystemConfig $systemConfig
     */
    public function __construct(SystemConfig $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    /**
     * @var array
     */
    private static $designOptions = [
        'legacy' => 'Legacy',
        'checkout' => 'Checkout',
        'custom' => 'Custom'
    ];

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $storeScope = ScopeInterface::SCOPE_STORE;
        $storeCode = $this->systemConfig->resolveCurrentStoreCode();
        $login = $this->systemConfig->getApiConfig('api_log_in', $storeScope, $storeCode);
        $options = self::$designOptions;

        // Set checkout by default for new merchants
        if (empty($login)) {
            $options = array_merge(["checkout" => $options["checkout"]], $options);
        }
        
        $output = [];
        foreach ($options as $key => $label) {
            $output[] = ['value' => $key, 'label' => $label];
        }

        return $output;
    }
}
