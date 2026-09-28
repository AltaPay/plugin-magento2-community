<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use SDM\Altapay\Model\SystemConfig;
use SDM\Altapay\Model\ConstantConfig;
use Altapay\Api\Payments\CardWalletSession;
use SDM\Altapay\Helper\Config as storeConfig;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class ApplePay extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Helper Config
     */
    private $storeConfig;
    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var UrlInterface
     */
    private $_urlInterface;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * Apple Pay constructor.
     *
     * @param Context $context
     * @param storeConfig $storeConfig
     * @param SystemConfig $systemConfig
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlInterface
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        storeConfig $storeConfig,
        SystemConfig $systemConfig,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
        $this->storeConfig   = $storeConfig;
        $this->systemConfig  = $systemConfig;
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $storeCode     = $this->getStoreCode();
        $storeScope    = $this->storeConfig->getStoreScope();
        $validationUrl = $this->getRequest()->getParam('validationUrl');
        $terminalName = $this->getRequest()->getParam('terminalId');
        $terminalCode = $this->getRequest()->getParam('terminalCode');
        $currentUrl = $this->_urlInterface->getBaseUrl();
        $domain = parse_url($currentUrl, PHP_URL_HOST);
        $auth     = $this->systemConfig->getAuth($storeCode);
        $request = new CardWalletSession($auth);
        $request->setTerminal($terminalName)
                ->setValidationUrl($validationUrl)
                ->setDomain($domain);

        if (!$this->isLegacyApplePayFlow($terminalCode, $storeScope, $storeCode)) {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote || !$quote->getId()) {
                return $this->resultFactory
                    ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
                    ->setData(['message' => __(ConstantConfig::PAYMENT_FAILED)]);
            }

            $baseCurrency = $this->storeConfig->useBaseCurrency();
            $grandTotal   = $baseCurrency ? $quote->getBaseGrandTotal() : $quote->getGrandTotal();
            $currencyCode = $baseCurrency ? $quote->getBaseCurrencyCode() : $quote->getQuoteCurrencyCode();

            $request->setShopOrderId($quote->reserveOrderId()->getReservedOrderId())
                    ->setAmount(round($grandTotal, 2))
                    ->setCurrency($currencyCode)
                    ->setApplePayRequestData([
                        'validationUrl' => $validationUrl,
                        'domain'        => $domain,
                    ]);
        }

        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($this->getSessionData($request->call()));
    }

    /**
     * Extract the wallet session payload from the gateway response.
     *
     * @param mixed $response
     * @return mixed
     */
    private function getSessionData($response)
    {
        $data = ['message' => __(ConstantConfig::PAYMENT_FAILED)];

        if ($response->Result === 'Success') {
            if (isset($response->ApplePaySession)) {
                $data = $response->ApplePaySession;
            } elseif (isset($response->WalletData->Session)) {
                $transaction = !empty($response->Transactions) ? reset($response->Transactions) : null;
                if ($transaction && isset($transaction->PaymentId)) {
                    $this->checkoutSession->setData('altapay_payment_id', $transaction->PaymentId);
                }

                $data = $response->WalletData->Session;
            }
        }

        return $data;
    }

    /**
     * Whether the Apple Pay has the legacy flow enabled.
     *
     * @param string $terminalCode The Magento payment method code (e.g. "terminal1")
     * @param mixed  $storeScope
     * @param string $storeCode
     * @return bool
     */
    private function isLegacyApplePayFlow($terminalCode, $storeScope, $storeCode)
    {
        if (!$terminalCode) {
            return true;
        }

        $legacyFlow = $this->systemConfig->getTerminalConfigFromTerminalName(
            $terminalCode,
            'legacyapplepayflow',
            $storeScope,
            $storeCode
        );

        return $this->systemConfig->isLegacyApplePayFlow($legacyFlow);
    }

    /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }
}
