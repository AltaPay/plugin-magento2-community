<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Block;

use Magento\Framework\View\Element\Template;
use SDM\Valitor\Model\TokenFactory;
use Magento\Payment\Helper\Data;
use SDM\Valitor\Model\ConfigProvider;

class Success extends \Magento\Checkout\Block\Onepage\Success
{

    private $dataToken;
    /**
     * @var ConfigProvider
     */
    private $dataPayment;

    /**
     * Success constructor.
     *
     * @param Template\Context                    $context
     * @param \Magento\Checkout\Model\Session     $checkoutSession
     * @param \Magento\Sales\Model\Order\Config   $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param TokenFactory                        $dataToken
     * @param array                               $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        TokenFactory $dataToken,
        array $data = [],
        ConfigProvider $dataPayment
    ) {
        parent::__construct(
            $context,
            $checkoutSession,
            $orderConfig,
            $httpContext,
            $data
        );
        $this->dataPayment = $dataPayment;
        $this->dataToken   = $dataToken;
    }

    public function getTokenData()
    {
        $order      = $this->_checkoutSession->getLastRealOrder();
        $customerId = $order->getCustomerId();

        if (!empty($customerId)) {
            $payment       = $order->getPayment();
            $paymentMethod = $this->dataPayment->getActivePaymentMethod();
            $method        = $payment->getMethod();
            $ccToken       = $payment->getAdditionalInformation('cc_token');

            if (!empty($ccToken) && isset($paymentMethod[$method])
                && isset($paymentMethod[$method]['enabledsavetokens'])
                && $paymentMethod[$method]['enabledsavetokens'] == 1
            ) {
                $model      = $this->dataToken->create();
                $collection = $model->getCollection()
                                    ->addFieldToSelect(['id'])
                                    ->addFieldToFilter('customer_id', $customerId)
                                    ->addFieldToFilter('token', $ccToken)
                                    ->getFirstItem();
                if (!empty($collection->getData())) {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        return $order;
    }
}
