<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Handler;

use SDM\Altapay\Model\SystemConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use SDM\Altapay\Model\ReconciliationIdentifierFactory;
use SDM\Altapay\Helper\Data;

/**
 * Class CreatePaymentHandler
 * To handle the functionality related to create payment
 * request at altapay.
 */
class CreatePaymentHandler
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var ReconciliationIdentifierFactory
     */
    protected $reconciliation;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Gateway constructor.
     *
     * @param SystemConfig          $systemConfig
     * @param Order                 $order
     * @param TransactionFactory    $transactionFactory
     * @param InvoiceService        $invoiceService
     */
    public function __construct(
        SystemConfig                    $systemConfig,
        Order                           $order,
        TransactionFactory              $transactionFactory,
        InvoiceService                  $invoiceService,
        ReconciliationIdentifierFactory $reconciliation,
        Data $helper
    )
    {
        $this->systemConfig         = $systemConfig;
        $this->order                = $order;
        $this->transactionFactory   = $transactionFactory;
        $this->invoiceService       = $invoiceService;
        $this->reconciliation       = $reconciliation;
        $this->helper                = $helper;
    }

    /**
     * @param Order  $order
     * @param string $state
     * @param string $statusKey
     *
     * @throws AlreadyExistsException
     */
    public function setCustomOrderStatus(Order $order, string $state, string $statusKey)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode  = $order->getStore()->getCode();
        $status     = $this->systemConfig->getStatusConfig($statusKey, $storeScope, $storeCode);
        $saveOrder  = false;

        if ($order->getState() !== $state) {
            $order->setState($state);
            $saveOrder = true;
        }

        if ($status && $order->getStatus() !== $status) {
            $order->setStatus($status);
            $saveOrder = true;
        }


        if ($saveOrder) {
            $order->getResource()->save($order);
        }
    }

    /**
     * Creates and registers an invoice for the given order if no invoice exists.
     * @param Order $order
     *
     * @return void
     */
    public function createInvoice(Order $order)
    {
        if (!$order->getInvoiceCollection()->count()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $transaction = $this->transactionFactory->create()->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transaction->save();
        }
    }

    /**
     * @param $transaction
     * @param $order
     * @return void
     */
    public function saveReconciliationData($transaction, $order)
    {
        $reconciliationData = $transaction->ReconciliationIdentifiers ?? '';

        if ($reconciliationData && is_array($reconciliationData)) {
            $model = $this->reconciliation->create();

            foreach ($reconciliationData as $value) {
                $collection = $this->helper->getReconciliationData($order->getIncrementId(), $value->Id);
                if (!$collection->getSize()) {
                    $model->addData([
                        "order_id" => $order->getIncrementId(),
                        "identifier" => $value->Id,
                        "type" => $value->Type
                    ]);
                }
            }
            $model->save();
        }
    }
}
