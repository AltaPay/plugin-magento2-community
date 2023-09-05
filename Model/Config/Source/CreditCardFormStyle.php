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

class CreditCardFormStyle implements ArrayInterface
{

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
        $output = [];
        foreach (self::$designOptions as $key => $label) {
            $output[] = ['value' => $key, 'label' => $label];
        }

        return $output;
    }
}