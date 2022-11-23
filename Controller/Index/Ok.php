<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use SDM\Altapay\Controller\Index;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Ok extends Index implements CsrfAwareActionInterface
{
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
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Exception
     */
    /**
     * @return void
     */
    public function execute()
    {
        $this->writeLog();
        $checkAvs     = false;
        $post         = $this->getRequest()->getPostValue();
        $orderId      = $post['shop_orderid'];
        $order        = $this->order->loadByIncrementId($orderId);
        $payment      = $order->getPayment();
        $terminalCode = $payment->getMethod();
        
        if (isset($post['avs_code']) && isset($post['avs_text'])) {
            $checkAvs = $this->generator->avsCheck(
                $this->getRequest(),
                strtolower($post['avs_code']),
                strtolower($post['avs_text'])
            );
        }
    
        $xml = simplexml_load_string($post['xml']);

        $transactions = json_decode( json_encode( $xml->Body->Transactions ), true );

        $latestTransactionKey = $this->getLatestTransaction($transactions['Transaction']);
  
        $reconciliationData = $transactions['Transaction'][$latestTransactionKey]['ReconciliationIdentifiers']['ReconciliationIdentifier'];
        
        if($reconciliationData){
             $model = $this->reconciliation->create();
             $model->addData([
                 "order_id"      => $orderId,
                 "identifier"    => $reconciliationData['Id'],
                 "type"          => $reconciliationData['Type']
             ]);
             $model->save();
         }

        if ($this->checkPost() && $checkAvs == false) {
            $isSuccessful = $this->generator->handleOkAction($this->getRequest());
            if (strtolower($post['type']) === "verifycard") {
                $response = $this->gateway->createRequest(
                    $terminalCode[strlen($terminalCode) - 1],
                    $orderId
                );
                if ($response['result'] === 'success') {
                    return $this->setSuccessPath($orderId);
                } else {
                    $this->redirectToCheckoutPage();
                }
            }
        
            if (isset($isSuccessful) && !$isSuccessful) {
                $this->redirectToCheckoutPage();
            } else {
                return $this->setSuccessPath($orderId);
            }
        } else {
            $this->redirectToCheckoutPage();
        }
    }
    
    /**
     * @return mixed
     */
    private function redirectToCheckoutPage()
    {
        $this->_eventManager->dispatch('order_cancel_after', ['order' => $this->order]);
        $this->generator->restoreOrderFromRequest($this->getRequest());
        
        return $this->_redirect('checkout');
    }
}
