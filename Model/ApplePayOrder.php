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

    public function __construct(
        OrderLoaderInterface $orderLoader,
        Session $checkoutSession,
        StockStateInterface $stockItem,
        StockRegistryInterface $stockRegistry,
        TransactionRepositoryInterface $transactionRepository,
        SystemConfig $systemConfig,
        Order $order,
        CreatePaymentHandler $paymentHandler,
        TransactionFactory $transactionFactory
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
    }

    /**
     * @param                  $comment
     * @param RequestInterface $request
     *
     * @throws AlreadyExistsException
     */
    public function handleCardWalletPayment($response, $order)
    {
        $max_date = '';
        $latestTransKey = '';
        foreach ($response->Transactions as $key=>$value) {
            if ($value->CreatedDate > $max_date) {
                $max_date = $value->CreatedDate;
                $latestTransKey = $key;
            }
        }
        if ($response && $response->Result === 'Success' && isset($response->Transactions[$latestTransKey])) {
            $transaction = $response->Transactions[$latestTransKey];
            $paymentType    = $transaction->AuthType;
            $responseStatus = $transaction->TransactionStatus;
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
                //save transaction data
                $parametersData  = null;
                $transactionData = json_encode($response);
                $this->transactionRepository->addTransactionData(
                    $order->getIncrementId(),
                    $transaction->TransactionId,
                    $transaction->PaymentId,
                    $transactionData,
                    $parametersData
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

            }
        } else {
                $this->paymentHandler->setCustomOrderStatus($order, Order::STATE_CANCELED, 'cancel');
                $order->addStatusHistoryComment("Order status: ". $response->Result);
                $order->setIsNotified(false);
                $order->getResource()->save($order);
        }
    }

    public function sortFunction($a, $b) {
        return strtotime($b["date"]) - strtotime($a["date"]);
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