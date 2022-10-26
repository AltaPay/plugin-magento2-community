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
        Session $customerSession,
        CheckoutSession $checkoutSession,
        Logger $altapayLogger,
        StoreManagerInterface $storeManager,
        Helper $helper,
        Cart $cart
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
        $getCurrentQuote     = $this->_checkoutSession->getQuote();
        $config                     = [];
        $baseUrl                    = $this->_storeManager->getStore()->getBaseUrl();
        $grandTotal                 = $getCurrentQuote->getGrandTotal();
        $countryCode                = $this->scopeConfig->getValue('general/country/default',
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return [
            'payment' => [
                self::CODE => [
                    'url'          => $this->urlInterface->getDirectUrl(
                        $this->getData()->getConfigData('place_order_url')
                    ),
                    'auth'         => $this->checkAuth(),
                    'connection'   => $this->checkConn(),
                    'terminaldata' => $activePaymentMethod,
                    'grandTotalAmount' => $grandTotal,
                    'countryCode' => $countryCode,
                    'currencyCode' => $this->_storeManager->getStore()->getBaseCurrencyCode(),
                    'baseUrl' => $baseUrl
                ]
            ]
        ];
    }

    public function getActivePaymentMethod()
    {
        $storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $quote             = $this->cart->getQuote();
        $storeCode         = $this->systemConfig->resolveCurrentStoreCode();
        $methods           = [];
        $allPaymentMethod  = $this->data->getPaymentMethods();
        $model             = $this->dataToken->create();
        $savedTokenList    = [];
        $primary           = '';
        $agreementType     = "unscheduled";
        $currentCustomerId = $this->customerSession->getCustomer()->getId();

        if (!empty($currentCustomerId)) {
            if ($this->helper->validateQuote($quote)) {
                $agreementType = "recurring";
            }
            $collection = $model->getCollection()
                                ->addFieldToSelect(['id', 'masked_pan', 'primary', 'expires'])
                                ->addFieldToFilter('customer_id', $currentCustomerId)
                                ->addFieldToFilter('agreement_type', $agreementType);
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
                    'applepaylabel'     => $applePayLabel
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
            $fileId = 'SDM_Altapay::images/' . $logoName . '.png';
            $params = ['area' => 'frontend'];
            $asset  = $this->assetRepository->createAsset($fileId, $params);
            $path[] = $asset->getUrl();
        }

        return $path;
    }

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
