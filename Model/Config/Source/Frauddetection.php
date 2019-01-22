<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */
namespace SDM\Altapay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Frauddetection
 * @package SDM\Altapay\Model\Config\Source
 */
class Frauddetection implements ArrayInterface
{

    /**
     * @var array
     */
    private static $availible = [
        '' => '- Disable -',
        'red' => 'Red',
        'maxmind' => 'Maxmind',
        'test' => 'Test'
    ];

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $output = [];
        foreach (self::$availible as $key => $label) {
            $output[] = ['value' => $key, 'label' => $label];
        }
        return $output;
    }
}
