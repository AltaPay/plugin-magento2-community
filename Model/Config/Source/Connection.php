<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */
namespace SDM\Valitor\Model\Config\Source;

use SDM\Valitor\Api\Test\TestConnection;
use Magento\Framework\Option\ArrayInterface;
use SDM\Valitor\Model\SystemConfig;

/**
 * Class Connection
 * @package SDM\Valitor\Model\Config\Source
 */
class Connection implements ArrayInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Connection constructor.
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
        try {
            $response = new TestConnection($this->systemConfig->getApiConfig('productionurl'));
            if (! $response) {
                $result = false;
            } else {
                $result = $response->call();
            }
        } catch (\Exception $e) {
            $result = false;
        }

        return [
            ['value' => '', 'label' => $result ? 'Connection successful' : 'Could not connect']
        ];
    }
}
