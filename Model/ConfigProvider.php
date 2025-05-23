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
use SDM\Altapay\Helper\Data as Helper;
use Altapay\Api\Test\TestAuthentication;
use Altapay\Api\Test\TestConnection;
use SDM\Altapay\Model\SystemConfig;
use Altapay\Authentication;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Config\Source\Allmethods;
use Magento\Framework\View\Asset\Repository;
use SDM\Altapay\Model\TokenFactory;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface;
use \Exception;
use SDM\Altapay\Logger\Logger;
use Magento\Checkout\Model\Cart;
use SDM\Altapay\Helper\Config as storeConfig;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'sdm_altapay';

    protected $_checkoutSession;
    
    protected $_storeManager;
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
     * @var Helper
     */
    private $helper;
    /**
     * @var Cart
     */
    private $cart;
    /**
     * @var Logger
     */
    private $altapayLogger;
    /**
     * @var Helper Config
     */
    private $storeConfig;
    
    /**
     * ConfigProvider constructor.
     *
     * @param Data $data
     * @param Escaper $escaper
     * @param Allmethods $allPaymentMethod
     * @param UrlInterface $urlInterface
     * @param SystemConfig $systemConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param Repository $assetRepository
     * @param TokenFactory $dataToken
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Logger $altapayLogger
     * @param StoreManagerInterface $storeManager
     * @param Helper $helper
     * @param Cart $cart
     * @param storeConfig $storeConfig
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
        Session $customerSession,
        CheckoutSession $checkoutSession,
        Logger $altapayLogger,
        StoreManagerInterface $storeManager,
        Helper $helper,
        Cart $cart,
        storeConfig $storeConfig
    )
    {
        $this->data             = $data;
        $this->escaper          = $escaper;
        $this->urlInterface     = $urlInterface;
        $this->systemConfig     = $systemConfig;
        $this->scopeConfig      = $scopeConfig;
        $this->allPaymentMethod = $allPaymentMethod;
        $this->assetRepository  = $assetRepository;
        $this->dataToken        = $dataToken;
        $this->customerSession  = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager    = $storeManager;
        $this->altapayLogger    = $altapayLogger;
        $this->helper           = $helper;
        $this->cart             = $cart;
        $this->storeConfig      = $storeConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $activePaymentMethod = $this->getActivePaymentMethod();
        $baseUrl             = $this->_storeManager->getStore()->getBaseUrl();
        $baseCurrency        = $this->storeConfig->useBaseCurrency();
        $currencyCode        = $baseCurrency ? $this->_storeManager->getStore()->getBaseCurrencyCode() : $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $countryCode         = $this->scopeConfig->getValue('general/country/default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return [
            'payment' => [
                self::CODE => [
                    'url'          => $this->urlInterface->getDirectUrl(
                        $this->getData()->getConfigData('place_order_url')
                    ),
                    'terminaldata'      => $activePaymentMethod,
                    'countryCode'       => $countryCode,
                    'currencyCode'      => $currencyCode,
                    'baseUrl'           => $baseUrl,
                    'currencyConfig'    => $baseCurrency
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
                                ->addFieldToFilter('customer_id', $currentCustomerId)
                                ->addFieldToFilter('agreement_type', "unscheduled");
            if (!empty($collection)) {
                $primary          = $this->ccTokenPrimaryOption($collection);
                $savedTokenList[] = [
                    'id'        => '',
                    'maskedPan' => 'Select from saved credit card'
                ];
                foreach ($collection as $item) {
                    $data             = $item->getData();
                    $maskedPAN        = str_repeat('*', 8) . substr($data['masked_pan'], -4);
                    $id               = $data['id'];
                    $savedTokenList[] = [
                        'id'        => $id,
                        'maskedPan' => $maskedPAN
                    ];
                }
            }
        }

        foreach ($allPaymentMethod as $key => $paymentModel) {
            $paymentCode        = 'payment/' . $key;
            $label              = $this->scopeConfig->getValue($paymentCode . '/title', $storeScope, $storeCode);
            $terminalName       = $this->scopeConfig->getValue($paymentCode . '/terminalname', $storeScope, $storeCode);
            $terminalMessage    = $this->scopeConfig->getValue($paymentCode . '/terminalmessage', $storeScope, $storeCode);
            $terminalStatus     = $this->scopeConfig->getValue($paymentCode . '/active', $storeScope, $storeCode);
            $terminalLogo       = $this->scopeConfig->getValue($paymentCode . '/terminallogo', $storeScope, $storeCode);
            if (!empty($terminalLogo)) {
                $logoURL = $this->getLogoPath($terminalLogo);
            } else {
                $logoURL = '';
            }
            $showBoth      = $this->scopeConfig->getValue($paymentCode . '/showlogoandtitle', $storeScope, $storeCode);
            $saveCardToken = $this->scopeConfig->getValue($paymentCode . '/savecardtoken', $storeScope, $storeCode);
            $isApplePay    = $this->scopeConfig->getValue($paymentCode . '/isapplepay', $storeScope, $storeCode);
            $applePayLabel = $this->scopeConfig->getValue($paymentCode . '/applepaylabel', $storeScope, $storeCode);
            $agreementType = $this->scopeConfig->getValue($paymentCode . '/agreementtype', $storeScope, $storeCode);
            if($agreementType === "recurring" || $agreementType === "instalment") {
                $savedTokenList = null;
                $saveCardToken = null;
            }
            if ($terminalStatus == 1) {
                $methods[$key] = [
                    'label'             => $label,
                    'value'             => $paymentCode,
                    'terminalname'      => $terminalName,
                    'terminalmessage'   => $terminalMessage,
                    'terminalstatus'    => $terminalStatus,
                    'terminallogo'      => $logoURL,
                    'showlogoandtitle'  => $showBoth,
                    'enabledsavetokens' => $saveCardToken,
                    'isapplepay'        => $isApplePay,
                    'applepaylabel'     => $applePayLabel,
                    'isLoggedIn'        => $currentCustomerId
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
     * @param string $name
     * @return array
     */
    public function getLogoPath($name)
    {
        $path = [];
        $terminalLogo   = explode(",",$name);
        foreach ($terminalLogo as $logoName) {
            if(empty($logoName)){
                continue;
            }
            $fileId = 'SDM_Altapay::images/' . $logoName . '.png';
            $params = ['area' => 'frontend'];
            $asset  = $this->assetRepository->createAsset($fileId, $params);
            $path[] = $asset->getUrl();
        }

        return $path;
    }

    /**
     * @deprecated
     * @return int|null
     */
    public function checkAuth()
    {
        $auth     = 0;
        $response = new TestAuthentication($this->systemConfig->getAuth());

        try {
            $result = $response->call();
            if($result){
                $auth = 1;
            }
        } catch (\Exception $e){
            return $this->altapayLogger->addCriticalLog('Exception', $e->getMessage());
        }
        return $auth;
    }

    /**
     * @deprecated
     * @return int
     */
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
