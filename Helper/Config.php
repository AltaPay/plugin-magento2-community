<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CurrencyFactory;

/**
 * Class Config for getting store configuration information.
 */
class Config extends AbstractHelper
{
    /**
     * Charged currency configuration
     */
    const ALTAPAY_CHARGED_CURRENCY = 'payment/sdm_altapay_config/charged_currency/setting';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var rule
     */
    protected $rule;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param RuleFactory $rule
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RuleFactory $rule,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory
    ) {
        $this->scopeConfig     = $scopeConfig;
        $this->rule            = $rule;
        $this->storeManager    = $storeManager;
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * check if store prices are incl or excl of tax.
     *
     * @param null|object $order
     *
     * @return bool
     */
    public function storePriceIncTax($order = null)
    {
        if ($order !== null) {
            if ($order->getAltapayPriceIncludesTax() !== null) {
                return $order->getAltapayPriceIncludesTax();
            }
        }

        if ((int)$this->scopeConfig->getValue('tax/calculation/price_includes_tax', $this->getStoreScope()) === 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getStoreScope()
    {
        return \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    }

    /**
     * Get rule information based on the rule id.
     *
     * @param $ruleID
     *
     * @return array
     */
    public function getRuleInformationByID($ruleID)
    {
        $ruleDetails                      = [];
        $ruleInfo                         = $this->rule->create()->load($ruleID);
        $ruleDetails['apply_to_shipping'] = $ruleInfo->getData('apply_to_shipping');
        $ruleDetails['simple_action']     = $ruleInfo->getData('simple_action');
        $ruleDetails['discount_amount']   = $ruleInfo->getData('discount_amount');

        return $ruleDetails;
    }

    /**
     * Get image url by image name.
     *
     * @param        $order
     * @param string $image
     *
     * @return string
     */
    public function getProductImageUrl($order, $image)
    {
        $url = $image;
        if ($image) {
            if (is_string($image)) {
                $url = $order->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                    ) . 'catalog/product/' . $image;
            }
        }

        return $url;
    }

    /**
     * Get Checkout form style value.
     *
     * @return string
     */
    public function ccFormStyle()
    {
        return $this->scopeConfig->getValue('payment/sdm_altapay_config/cc_form_style/cc_form_options');
    }
    
    /**
     * Check if base currency is enabled
     *
     * @param string $moduleVersion
     * @return bool
     */
    public function useBaseCurrency(string $moduleVersion = null): bool
    {
        $config = $this->scopeConfig->getValue(self::ALTAPAY_CHARGED_CURRENCY);
    
        if (is_null($moduleVersion) || version_compare($moduleVersion, '3.7.0', '>=')) {
            return $config === 'base_currency';
        }
    
        return false;
    }

    public function getCurrencySymbol()
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $currency = $this->currencyFactory->create()->load($currencyCode);
        return $currency->getCurrencySymbol();
    }
}
