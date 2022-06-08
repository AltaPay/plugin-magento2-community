<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Config\Source;

use Magento\Config\Model\Config\Source\Locale;
use Magento\Framework\Option\ArrayInterface;
use Altapay\Types\LanguageTypes;

class Languages extends Locale implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $languages     = [];
        $mainlanguages = parent::toOptionArray();
        $response      = new LanguageTypes();
        foreach ($mainlanguages as $keylang => $language) {
            list($key, $tmp) = explode('_', $language['value']);
            if (in_array($key, $response->getAllowed())) {
                $languages[$key] = $language;
            }
        }

        return array_merge([
            ['value' => '', 'label' => '- Auto select -']
        ], $languages);
    }
}
