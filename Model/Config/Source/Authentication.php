<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */
namespace SDM\Altapay\Model\Config\Source;

use Altapay\Api\Test\TestAuthentication;
use Magento\Framework\Option\ArrayInterface;
use SDM\Altapay\Model\SystemConfig;

/**
 * Class Authentication
 * @package SDM\Altapay\Model\Config\Source
 */
class Authentication implements ArrayInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Authentication constructor.
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
            $response = new TestAuthentication($this->systemConfig->getAuth());
            $result = $response->call();
        } catch (\Exception $e) {
            $result = false;
        }

        return [
            ['value' => '', 'label' => $result ? 'Authentication successful' : 'Could not authenticate']
        ];
    }
}
