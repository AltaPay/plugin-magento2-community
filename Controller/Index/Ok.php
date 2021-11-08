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
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Url\EncoderInterface;


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
    public function execute()
    {
        $this->writeLog();
        $checkAvs = false;
        $post     = $this->getRequest()->getPostValue();
        $orderId = $post['shop_orderid'];
        if (isset($post['avs_code']) && isset($post['avs_text'])) {
            $checkAvs = $this->generator->avsCheck(
                $this->getRequest(),
                strtolower($post['avs_code']),
                strtolower($post['avs_text'])
            );
        }
        if ($this->checkPost() && $checkAvs == false) {
            $this->generator->handleOkAction($this->getRequest());
            
            return $this->setSuccessPath($orderId);
        } else {
            $this->_eventManager->dispatch('order_cancel_after', ['order' => $this->order]);
            $this->generator->restoreOrderFromRequest($this->getRequest());

            return $this->_redirect('checkout');
        }
    }
}
