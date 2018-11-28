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
namespace SDM\Altapay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;

/**
 * Class ConfigProvider
 * @package SDM\Altapay\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE  = 'sdm_altapay';
    /**
     * @var Data
     */
    private $data;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * ConfigProvider constructor.
     * @param Data $data
     * @param Escaper $escaper
     * @param UrlInterface $urlInterface
     */
    public function __construct(Data $data, Escaper $escaper, UrlInterface $urlInterface)
    {
        $this->data = $data;
        $this->escaper = $escaper;
        $this->urlInterface = $urlInterface;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'url' => $this->urlInterface->getDirectUrl($this->getData()->getConfigData('place_order_url'))
                ]
            ]
        ];
    }

    /**
     * @return \Magento\Payment\Model\MethodInterface
     */
    private function getData()
    {
        return $this->data->getMethodInstance('terminal1');
    }
}
