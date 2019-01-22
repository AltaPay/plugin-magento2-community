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

use SDM\Altapay\Response\TerminalsResponse;
use Magento\Framework\Option\ArrayInterface;
use SDM\Altapay\Model\SystemConfig;

/**
 * Class Terminals
 * @package SDM\Altapay\Model\Config\Source
 */
class Terminals implements ArrayInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Terminals constructor.
     * @param SystemConfig $systemConfig
     */
    public function __construct(SystemConfig $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $terminals = [];
        try {
            $call = new \SDM\Altapay\Api\Others\Terminals($this->systemConfig->getAuth());
            /** @var TerminalsResponse $response */
            $response = $call->call();
            foreach ($response->Terminals as $terminal) {
                $terminals[] = ['value' => $terminal->Title, 'label' => $terminal->Title];
            }
        } catch (\Exception $e) {
        }
        // Sort the terminals alphabetically
        array_multisort(array_column($terminals, 'label'), SORT_ASC, SORT_NUMERIC, $terminals);
        return $terminals;
    }
}
