<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use SDM\Altapay\Api\OrderLoaderInterface;
use Magento\Checkout\Model\Session;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use SDM\Altapay\Api\TransactionRepositoryInterface;
use SDM\Altapay\Model\SystemConfig;
use Magento\Sales\Model\Order;
use SDM\Altapay\Model\ConstantConfig;
use SDM\Altapay\Model\Handler\CreatePaymentHandler;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use SDM\Altapay\Helper\Data;
use Magento\Checkout\Model\Cart;

class ApplePayOrder {

    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var StockStateInterface
     */
    private $stockItem;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var CreatePaymentHandler
     */
    private $paymentHandler;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var Data
     */
    protected $helper;


    /**
     * @var Cart
     */
    private $modelCart;

    /**
     * @param OrderLoaderInterface $orderLoader
     * @param Session                        $checkoutSession
     * @param StockStateInterface            $stockItem
     * @param StockRegistryInterface         $stockRegistry
     * @param TransactionRepositoryInterface $transactionRepository
     * @param SystemConfig                   $systemConfig
     * @param Order                          $order
     * @param CreatePaymentHandler           $paymentHandler
     * @param TransactionFactory             $transactionFactory
     * @param OrderSender                    $orderSender
     * @param Data                           $helper
     * @param Cart                           $modelCart modelCart
     */
    public function __construct(
        OrderLoaderInterface $orderLoader,
        Session $checkoutSession,
        StockStateInterface $stockItem,
        StockRegistryInterface $stockRegistry,
        TransactionRepositoryInterface $transactionRepository,
        SystemConfig $systemConfig,
        Order $order,
        CreatePaymentHandler $paymentHandler,
        TransactionFactory $transactionFactory,
        OrderSender $orderSender,
        Data $helper,
        Cart $modelCart
    ) {
        $this->orderLoader           = $orderLoader;
        $this->checkoutSession       = $checkoutSession;
        $this->stockItem             = $stockItem;
        $this->stockRegistry         = $stockRegistry;
        $this->transactionRepository = $transactionRepository;
        $this->systemConfig          = $systemConfig;
        $this->order                 = $order;
        $this->paymentHandler        = $paymentHandler;
        $this->transactionFactory    = $transactionFactory;
        $this->orderSender           = $orderSender;
        $this->helper                = $helper;
        $this->modelCart             = $modelCart;
    }

    /**
     * @param $response
     * @param $order
     *
     * @throws AlreadyExistsException
     */
    public function handleCardWalletPayment($response, $order)
    {
        $latestTransKey = $this->helper->getLatestTransaction($response->Transactions);

        if ($response && $response->Result === 'Success' && isset($response->Transactions[$latestTransKey])) {
            $transaction = $response->Transactions[$latestTransKey];
            $paymentType    = $transaction->AuthType;
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode  = $order->getStore()->getCode();
            if ($order->getId()) {
                $cardType = '';
                $expires  = '';
                //Update stock quantity
                if($order->getState() == 'canceled') {
                    $this->updateStockQty($order);
                }
                $this->resetCanceledQty($order);
                if (isset($transaction->CreditCardExpiry->Month) && isset($transaction->CreditCardExpiry->Year)) {
                    $expires = $transaction->CreditCardExpiry->Month . '/' . $transaction->CreditCardExpiry->Year;
                }
                if (isset($transaction->PaymentSchemeName)) {
                    $cardType = $transaction->PaymentSchemeName;
                }
                $payment = $order->getPayment();
                $payment->setPaymentId($transaction->PaymentId);
                $payment->setLastTransId($transaction->TransactionId);
                $payment->setCcTransId($transaction->CreditCardToken);
                $payment->setAdditionalInformation('cc_token', $transaction->CreditCardToken);
                $payment->setAdditionalInformation('expires', $expires);
                $payment->setAdditionalInformation('card_type', $cardType);
                $payment->setAdditionalInformation('payment_type', $paymentType);
                $payment->save();
                //send order confirmation email
                if (!$order->getEmailSent()) {
                    $this->orderSender->send($order);
                }
                //save transaction data
                $this->transactionRepository->addTransactionData(
                    $order->getIncrementId(),
                    $transaction->TransactionId ?? null,
                    $transaction->PaymentId ?? null,
                    $transaction->Terminal ?? null,
                    $response->requireCapture ?? null,
                    $transaction->TransactionStatus ?? null,
                    $transaction->PaymentNature ?? null,
                    $response->Result ?? null,
                    $response->CardHolderMessageMustBeShown ?? null,
                    $response->CardHolderErrorMessage ?? null,
                    $response->MerchantErrorMessage ?? null,
                    $transaction->FraudRiskScore ?? null,
                    $transaction->FraudExplanation ?? null,
                    $transaction->FraudRecommendation ?? null
                );
                $orderStatusAfterPayment = $this->systemConfig->getStatusConfig('process', $storeScope, $storeCode);
                $orderStatusCapture      = $this->systemConfig->getStatusConfig('autocapture', $storeScope, $storeCode);
                $setOrderStatus          = true;
                $orderState              = Order::STATE_PROCESSING;
                $statusKey               = 'process';

                if ($this->isCaptured($response, $storeCode, $storeScope, $latestTransKey) && $orderStatusCapture == "complete") {
                    if ($this->orderLines->sendShipment($order)) {
                        $orderState = Order::STATE_COMPLETE;
                        $statusKey  = 'autocapture';
                        $order->addStatusHistoryComment(__(ConstantConfig::PAYMENT_COMPLETE));
                    } else {
                        $setOrderStatus = false;
                        $order->addStatusToHistory($orderStatusCapture, ConstantConfig::PAYMENT_COMPLETE, false);
                    }
                } else {
                    if ($orderStatusAfterPayment) {
                        $orderState = $orderStatusAfterPayment;
                    }
                }
                if ($setOrderStatus) {
                    $this->paymentHandler->setCustomOrderStatus($order, $orderState, $statusKey);
                }
                $order->addStatusHistoryComment("ApplePay Status: ". $response->Result);
                $order->setIsNotified(false);
                $order->getResource()->save($order);

                if (isset($response->Transactions[$latestTransKey])) {
                    $paymentType = $response->Transactions[$latestTransKey]->AuthType ?? '';
                    if (strtolower($paymentType) === 'paymentandcapture') {
                        $this->paymentHandler->createInvoice($order);
                        $this->paymentHandler->saveReconciliationData($transaction, $order);
                    }
                }
            }
        } else {
                $this->paymentHandler->setCustomOrderStatus($order, Order::STATE_CANCELED, 'cancel');
                $order->addStatusHistoryComment("Order status: ". $response->Result);
                $order->setIsNotified(false);
                $order->getResource()->save($order);
        }
    }

    protected function updateStockQty($order)
    {
        $cart = $this->modelCart;
        $quoteItems = $this->checkoutSession->getQuote()->getItemsCollection();
        foreach ($order->getAllItems() as $item) {
            $stockQty  = $this->stockItem->getStockQty($item->getProductId(), $item->getStore()->getWebsiteId());
            $qty       = $stockQty - $item->getQtyOrdered();                 
            $stockItem = $this->stockRegistry->getStockItemBySku($item['sku']);         
            $stockItem->setQty($qty);
            $stockItem->setIsInStock((bool)$qty); 
            $this->stockRegistry->updateStockItemBySku($item['sku'], $stockItem);
        }
        foreach($quoteItems as $item)
        {
            $cart->removeItem($item->getId())->save(); 
        }
    }

    /**
     * @param $order
     * 
     * @return void
     */
    public function resetCanceledQty($order) {
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyCanceled() > 0) {
                    $item->setQtyCanceled($item->getQtyToCancel());
                    $item->save();
            }
        }
    }
    /**
     * @param $response
     * @param $storeCode
     * @param $storeScope
     * @param $latestTransKey
     *
     * @return bool|\Magento\Payment\Model\MethodInterface
     */
    private function isCaptured($response, $storeCode, $storeScope, $latestTransKey)
    {
        $isCaptured = false;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig = $this->systemConfig->getTerminalConfigFromTerminalName(
                $terminalName,
                'terminalname',
                $storeScope,
                $storeCode
            );
            if ($terminalConfig === $response->Transactions[$latestTransKey]->Terminal) {
                $isCaptured = $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'capture',
                    $storeScope,
                    $storeCode
                );
                break;
            }
        }

        return $isCaptured;
    }

}