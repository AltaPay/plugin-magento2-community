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
namespace SDM\Valitor\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use SDM\Valitor\Api\Test\TestAuthentication;
use SDM\Valitor\Api\Test\TestConnection;
use SDM\Valitor\Model\SystemConfig;
use SDM\Valitor\Authentication;

/**
 * Class ConfigProvider
 * @package SDM\Valitor\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE  = 'sdm_valitor';

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
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * ConfigProvider constructor.
     * @param Data $data
     * @param Escaper $escaper
     * @param UrlInterface $urlInterface
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(Data $data, Escaper $escaper, UrlInterface $urlInterface,         SystemConfig $systemConfig
    )
    {
        $this->data = $data;
        $this->escaper = $escaper;
        $this->urlInterface = $urlInterface;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $store = null;
        return [
            'payment' => [
                self::CODE => [
                    'url' => $this->urlInterface->getDirectUrl($this->getData()->getConfigData('place_order_url')),
                    'auth' => $this->checkAuth(),
                    'connection' => $this->checkConn()
                ]
            ]
        ];
    }

    public function checkAuth()
    {
        $auth = 0;
        $response = new TestAuthentication($this->systemConfig->getAuth());
        if (!$response) {
            $result = false;
        } else {
            $result = $response->call();
        }
        if ($result) {
            $auth = 1;
        }

        return $auth;
    }

    public function checkConn()
    {
        $conn = 0;
        $response = new TestConnection($this->systemConfig->getApiConfig('productionurl'));
        if (!$response) {
            $result = false;
        } else {
            $result = $response->call();
        }
        if ($result) {
            $conn = 1;
        }
        return $conn;
    }

    /**
     * @return \Magento\Payment\Model\MethodInterface
     */
    private function getData()
    {
        return $this->data->getMethodInstance('terminal1');
    }
}
