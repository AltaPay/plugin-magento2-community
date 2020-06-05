<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use SDM\Valitor\Model\TokenFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Savetoken extends Action implements CsrfAwareActionInterface
{
    private $orderFactory;
    private $dataToken;
    private $resultRedirect;
    private $customerSession;

    /**
     * Savetoken constructor.
     *
     * @param Context       $context
     * @param OrderFactory  $orderFactory
     * @param TokenFactory  $dataToken
     * @param ResultFactory $result
     * @param Session       $customerSession
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        TokenFactory $dataToken,
        ResultFactory $result,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->orderFactory    = $orderFactory;
        $this->dataToken       = $dataToken;
        $this->resultRedirect  = $result;
        $this->customerSession = $customerSession;
    }

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
     * Savetoken action
     *
     * @return void
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        $url            = $this->_url->getUrl('customer/account');
        $resultRedirect->setUrl($url);
        $post = (array)$this->getRequest()->getPost();
        if (!empty($post)) {
            $orderId           = $post['valitor_order_id'];
            $order             = $this->orderFactory->create()->load($orderId);
            $orderCustomerId   = $order->getCustomerId();
            $currentCustomerId = $this->customerSession->getCustomer()->getId();

            if (!empty($currentCustomerId) && $currentCustomerId == $orderCustomerId) {
                $payment   = $order->getPayment();
                $ccToken   = $payment->getAdditionalInformation('cc_token');
                $maskedPan = $payment->getAdditionalInformation('masked_credit_card');
                $expires   = $payment->getAdditionalInformation('expires');
                $cardType  = $payment->getAdditionalInformation('card_type');
                $currency  = $order->getOrderCurrencyCode();
                if (!empty($ccToken)) {
                    $model = $this->dataToken->create();
                    $model->addData([
                        "customer_id"   => $orderCustomerId,
                        "token"         => $ccToken,
                        "masked_pan"    => $maskedPan,
                        "currency_code" => $currency,
                        "expires"       => $expires,
                        "card_type"     => $cardType
                    ]);
                    $saveData = $model->save();
                    if ($saveData) {
                        $this->messageManager->addSuccess(__('Information saved successfully !'));
                    }
                }
            }
        }

        return $resultRedirect;
    }
}
