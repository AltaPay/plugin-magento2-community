<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use SDM\Altapay\Authentication;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreResolver;

class SystemConfig
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Encrypted
     */
    private $encrypter;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string
     */
    private $storeScope;

    /**
     * @var StoreResolver
     */
    protected $storeResolver;

    /**
     * SystemConfig constructor.
     *
     * @param ScopeConfigInterface  $scopeConfig
     * @param Encrypted             $encrypter
     * @param RequestInterface      $request
     * @param State                 $state
     * @param StoreManagerInterface $storeManager
     * @param StoreResolver         $storeResolver
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Encrypted $encrypter,
        RequestInterface $request,
        State $state,
        StoreManagerInterface $storeManager,
        StoreResolver $storeResolver
    ) {
        $this->scopeConfig  = $scopeConfig;
        $this->encrypter    = $encrypter;
        $this->request      = $request;
        $this->state        = $state;
        $this->storeManager = $storeManager;
        $this->storeScope   = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
    }

    /**
     * @return array
     */
    public static function getTerminalCodes()
    {
        return [
            \SDM\Altapay\Model\Method\Terminal1::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal2::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal3::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal4::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal5::METHOD_CODE
        ];
    }

    /**
     * @param null $storeCode
     *
     * @return Authentication
     */
    public function getAuth($storeCode = null)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        if ($storeCode === null) {
            $storeCode = $this->resolveCurrentStoreCode();
        }

        $login    = $this->getApiConfig('api_log_in', $storeScope, $storeCode);
        $password = $this->encrypter->processValue($this->getApiConfig('api_pass_word', $storeScope, $storeCode));
        $baseurl  = $this->getApiConfig('productionurl', $storeScope, $storeCode);
        if (empty($baseurl)) {
            $baseurl = null;
        }

        return new Authentication($login, $password, $baseurl);
    }

    /**
     * @param string               $configKey
     * @param ScopeConfigInterface $storeScope
     * @param null|string          $storeCode
     *
     * @return string
     */
    public function getStatusConfig($configKey, $storeScope = null, $storeCode = null)
    {
        $storeScope = $this->checkStoreScope($storeScope);

        return $this->scopeConfig->getValue(
            sprintf(
                'payment/altapay_status/%s',
                $configKey
            ),
            $storeScope,
            $storeCode
        );
    }

    /**
     * @param int                  $terminalId
     * @param string               $configKey
     * @param ScopeConfigInterface $storeScope
     * @param null|string          $storeCode
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getTerminalConfig($terminalId, $configKey, $storeScope = null, $storeCode = null)
    {
        return $this->getTerminalConfigFromTerminalName(
            sprintf('terminal%d', $terminalId),
            $configKey,
            $storeScope,
            $storeCode
        );
    }

    /**
     * @param string               $terminalName
     * @param string               $configKey
     * @param ScopeConfigInterface $storeScope
     * @param null|string          $storeCode
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getTerminalConfigFromTerminalName($terminalName, $configKey, $storeScope = null, $storeCode = null)
    {
        $storeScope = $this->checkStoreScope($storeScope);

        return $this->scopeConfig->getValue(
            sprintf(
                'payment/%s/%s',
                $terminalName,
                $configKey
            ),
            $storeScope,
            $storeCode
        );
    }

    /**
     * @param string               $configKey
     * @param ScopeConfigInterface $storeScope
     * @param null|string          $storeCode
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getApiConfig($configKey, $storeScope = null, $storeCode = null)
    {
        $storeScope = $this->checkStoreScope($storeScope);

        return $this->scopeConfig->getValue(
            sprintf(
                'payment/altapay_config/%s',
                $configKey
            ),
            $storeScope,
            $storeCode
        );
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function resolveCurrentStoreCode()
    {
        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            //Admin area
            $storeId = (int)$this->request->getParam('store', 0);
        } else {
            //Frontend area
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->storeManager->getStore($storeId)->getCode();
    }

    /**
     * @param null|string $storeScope
     *
     * @return string|null
     */
    private function checkStoreScope($storeScope = null)
    {
        if ($storeScope === null) {
            $storeScope = $this->storeScope;
        }

        return $storeScope;
    }
}
