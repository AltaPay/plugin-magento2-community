<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use SDM\Altapay\Api\Test\TestAuthentication;
use SDM\Altapay\Api\Test\TestConnection;
use SDM\Altapay\Model\SystemConfig;
use SDM\Altapay\Authentication;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Config\Source\Allmethods;
use Magento\Framework\View\Asset\Repository;
use SDM\Altapay\Model\TokenFactory;
use Magento\Customer\Model\Session;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'sdm_altapay';

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
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var allPaymentMethod
     */
    protected $allPaymentMethod;
    /**
     * @var Repository
     */
    protected $assetRepository;
    /**
     * @var TokenFactory
     */
    private $dataToken;
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * ConfigProvider constructor.
     *
     * @param Data                 $data
     * @param Escaper              $escaper
     * @param Allmethods           $allPaymentMethod
     * @param UrlInterface         $urlInterface
     * @param SystemConfig         $systemConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param Repository           $assetRepository
     * @param TokenFactory         $dataToken
     * @param Session              $customerSession
     */
    public function __construct(
        Data $data,
        Escaper $escaper,
        Allmethods $allPaymentMethod,
        UrlInterface $urlInterface,
        SystemConfig $systemConfig,
        ScopeConfigInterface $scopeConfig,
        Repository $assetRepository,
        TokenFactory $dataToken,
        Session $customerSession
    ) {
        $this->data             = $data;
        $this->escaper          = $escaper;
        $this->urlInterface     = $urlInterface;
        $this->systemConfig     = $systemConfig;
        $this->scopeConfig      = $scopeConfig;
        $this->allPaymentMethod = $allPaymentMethod;
        $this->assetRepository  = $assetRepository;
        $this->dataToken        = $dataToken;
        $this->customerSession  = $customerSession;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $store               = null;
        $activePaymentMethod = $this->getActivePaymentMethod();

        return [
            'payment' => [
                self::CODE => [
                    'url'          => $this->urlInterface->getDirectUrl(
                        $this->getData()->getConfigData('place_order_url')
                    ),
                    'auth'         => $this->checkAuth(),
                    'connection'   => $this->checkConn(),
                    'terminaldata' => $activePaymentMethod
                ]
            ]
        ];
    }

    public function getActivePaymentMethod()
    {
        $storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode         = $this->systemConfig->resolveCurrentStoreCode();
        $methods           = [];
        $allPaymentMethod  = $this->data->getPaymentMethods();
        $model             = $this->dataToken->create();
        $savedTokenList    = [];
        $primary           = '';
        $currentCustomerId = $this->customerSession->getCustomer()->getId();

        if (!empty($currentCustomerId)) {
            $collection = $model->getCollection()
                                ->addFieldToSelect(['id', 'masked_pan', 'primary', 'expires'])
                                ->addFieldToFilter('customer_id', $currentCustomerId);
            if (!empty($collection)) {
                $primary          = $this->ccTokenPrimaryOption($collection);
                $savedTokenList[] = [
                    'id'        => '',
                    'maskedPan' => 'Select from saved credit card'
                ];
                foreach ($collection as $item) {
                    $data             = $item->getData();
                    $id               = $data['id'];
                    $maskedPan        = $data['masked_pan'] . ' (' . $data['expires'] . ')';
                    $savedTokenList[] = [
                        'id'        => $id,
                        'maskedPan' => $maskedPan
                    ];
                }
            }
        }

        foreach ($allPaymentMethod as $key => $paymentModel) {
            $paymentCode    = 'payment/' . $key;
            $label          = $this->scopeConfig->getValue($paymentCode . '/title', $storeScope, $storeCode);
            $terminalName   = $this->scopeConfig->getValue($paymentCode . '/terminalname', $storeScope, $storeCode);
            $terminalStatus = $this->scopeConfig->getValue($paymentCode . '/active', $storeScope, $storeCode);
            $terminalLogo   = $this->scopeConfig->getValue($paymentCode . '/terminallogo', $storeScope, $storeCode);
            if (!empty($terminalLogo)) {
                $logoURL = $this->getLogoFilePath($terminalLogo);
            } else {
                $logoURL = '';
            }
            $showBoth      = $this->scopeConfig->getValue($paymentCode . '/showlogoandtitle', $storeScope, $storeCode);
            $saveCardToken = $this->scopeConfig->getValue($paymentCode . '/savecardtoken', $storeScope, $storeCode);
            if ($terminalStatus == 1) {
                $methods[$key] = [
                    'label'             => $label,
                    'value'             => $paymentCode,
                    'terminalname'      => $terminalName,
                    'terminalstatus'    => $terminalStatus,
                    'terminallogo'      => $logoURL,
                    'showlogoandtitle'  => $showBoth,
                    'enabledsavetokens' => $saveCardToken
                ];
                if ($saveCardToken == 1 && !empty($savedTokenList)) {
                    $methods[$key]['savedtokenlist']          = json_encode($savedTokenList);
                    $methods[$key]['savedtokenprimaryoption'] = $primary;
                }
            }
        }

        return $methods;
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    public function getLogoFilePath($name)
    {
        $fileId = 'SDM_Altapay::images/' . $name . '.png';
        $params = ['area' => 'frontend'];
        $asset  = $this->assetRepository->createAsset($fileId, $params);
        try {
            return $asset->getUrl();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function checkAuth()
    {
        $auth     = 0;
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
        $conn     = 0;
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
    protected function getData()
    {
        return $this->data->getMethodInstance('terminal1');
    }

    /**
     * @param $collection
     *
     * @return string
     */
    private function ccTokenPrimaryOption($collection)
    {
        $primaryOptionId = '';
        foreach ($collection as $item) {
            $data    = $item->getData();
            $primary = $data['primary'];
            if ($primary == true) {
                $primaryOptionId = $data['id'];
            }
        }

        return $primaryOptionId;
    }
}
