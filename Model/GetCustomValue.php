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
use Magento\Checkout\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetCustomValue implements ConfigProviderInterface
{
    public $_checkoutSession;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var Currency
     */
    private $_currency;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigData;

    public function __construct(
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Currency $currency,
        ScopeConfigInterface $scopeConfigData

    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager    = $storeManager;
        $this->_currency        = $currency;
        $this->scopeConfigData  = $scopeConfigData;

    }

    public function getConfig()
    {
        $getCurrentQuote = $this->_checkoutSession->getQuote();
        $config                     = [];
        $baseUrl                    = $this->_storeManager->getStore()->getBaseUrl();
        $grandTotal                 = $getCurrentQuote->getGrandTotal();
        $countryCode                = $this->scopeConfigData->getValue('general/country/default',
            ScopeInterface::SCOPE_STORE);
        $config['grandTotalAmount'] = $grandTotal;
        $config['countryCode']      = $countryCode;
        $config['currencyCode']     = $this->_storeManager->getStore()->getBaseCurrencyCode();
        $config['url']              = $baseUrl;

        return $config;
    }
}