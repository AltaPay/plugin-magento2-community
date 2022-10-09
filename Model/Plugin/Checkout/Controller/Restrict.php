<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Plugin\Checkout\Controller;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlFactory;
use Magento\Checkout\Controller\Index\Index;
use Magento\Framework\DataObject;

class Restrict
{
    private $urlModel;
    private $resultRedirectFactory;
    private $messageManager;

    public function __construct(
        UrlFactory                   $urlFactory,
        RedirectFactory              $redirectFactory,
        \Magento\Checkout\Model\Cart $cart,
        ManagerInterface             $messageManager
    )
    {

        $this->urlModel = $urlFactory;
        $this->resultRedirectFactory = $redirectFactory;
        $this->cart = $cart;
        $this->messageManager = $messageManager;
    }

    public function aroundExecute(
        Index    $subject,
        \Closure $proceed
    )
    {
        // get array of all items what can be display directly
        $itemsVisible = $this->cart->getQuote()->getAllVisibleItems();
        $this->urlModel = $this->urlModel->create();
        $subscriptionProdCount = 0;
        $hasSubscription = false;
        foreach ($itemsVisible as $item) {
            /** @var DataObject $request */
            $request = $item->getBuyRequest();
            if (!$request && $item->getQuoteItem()) {
                $request = $item->getQuoteItem()->getBuyRequest();
            }
            if (!$request) {
                $request = new DataObject();
            }

            if (is_array($request)) {
                $request = new DataObject($request);
            }

            if ($request->getData('subscribe') === 'subscribe') {
                $subscriptionProdCount++;
                $hasSubscription = true;
            }
        }
        if($hasSubscription && !empty($request)) {
            if ($subscriptionProdCount > 1 || (int)$request->getData('qty') > 1 || $this->cart->getItemsCount() > 1) {
                $this->messageManager->addErrorMessage(__('You can purchase only one subscription product at a time'));
                $defaultUrl = $this->urlModel->getUrl('checkout/cart/', ['_secure' => true]);
                $resultRedirect = $this->resultRedirectFactory->create();
    
                return $resultRedirect->setUrl($defaultUrl);
            }
        }

        return $proceed();
    }
}