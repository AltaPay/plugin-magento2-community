<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */

namespace SDM\Altapay\Model\Handler;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use SDM\Altapay\Model\ConstantConfig;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage as CouponUsage;
use SDM\Altapay\Api\OrderLoaderInterface;

/**
 * Class RestoreQuote
 * @package SDM\Altapay\Model\Handler
 */
class RestoreQuote
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Order factory
     *
     * @var OrderFactory
     */
    protected $orderFactory;

    /** @var QuoteFactory */
    protected $quoteFactory;

     /** @var ManagerInterface */
    protected $messageManager;

    /**
     * @var Coupon
     */
    private $coupon;
    /**
     * @var CouponUsage
     */
    private $couponUsage;

    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * RestoreQuote Constructor
     * @param Session            $checkoutSession
     * @param OrderFactory       $orderFactory
     * @param QuoteFactory       $quoteFactory
     * @param ManagerInterface   $messageManager
     * @param Coupon             $coupon
     * @param CouponUsage        $couponUsage
     * @param OrderLoaderInterface $orderLoader
     */
    public function __construct(Session $checkoutSession, OrderFactory $orderFactory, QuoteFactory $quoteFactory, ManagerInterface $messageManager, Coupon $coupon, CouponUsage $couponUsage, OrderLoaderInterface $orderLoader)
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory    = $orderFactory;
        $this->quoteFactory    = $quoteFactory;
        $this->messageManager  = $messageManager;
        $this->coupon          = $coupon;
        $this->couponUsage     = $couponUsage;
        $this->orderLoader     = $orderLoader;
    }

    /**
     * @return void
     */
    public function handleQuote()
    {
        if ($this->orderLoader->getLastOrderIncrementIdFromSession()) {
            try {
                $orderId = $this->orderLoader->getLastOrderIncrementIdFromSession();
                $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
                if ($order) {
                    $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
                    //get quote Id from order and set as active
                    $quote->setIsActive(1)->setReservedOrderId(null)->save();
                    $this->checkoutSession->replaceQuote($quote)->unsLastRealOrderId();

                    $order->setState(Order::STATE_CANCELED);
                    $order->setIsNotified(false);
                    $order->addStatusHistoryComment(__(ConstantConfig::BROWSER_BK_BUTTON_COMMENT), Order::STATE_CANCELED);

                    if ($order->getCouponCode()) {
                        $this->resetCouponAfterCancellation($order);
                    }

                    $order->getResource()->save($order);
                }
            } catch (LocalizedException $e) {
                // catch and continue - do something when needed
            } catch (\Exception $e) {
                // catch and continue - do something when needed
            }

            $this->messageManager->addErrorMessage(__(ConstantConfig::BROWSER_BK_BUTTON_MSG));
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @throws \Exception
     */
    public function resetCouponAfterCancellation($order)
    {
        $this->coupon->load($order->getCouponCode(), 'code');
        if ($this->coupon->getId()) {
            $this->coupon->setTimesUsed($this->coupon->getTimesUsed() - 1);
            $this->coupon->save();
            $customerId = $order->getCustomerId();
            if ($customerId) {
                $this->couponUsage->updateCustomerCouponTimesUsed($customerId, $this->coupon->getId(), false);
            }
        }
    }
}
