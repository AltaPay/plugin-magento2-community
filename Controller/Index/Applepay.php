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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use SDM\Altapay\Api\Test\TestAuthentication;
use SDM\Altapay\Api\Payments\ApplepayWalletSession;
use SDM\Altapay\Helper\Config as storeConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Action\Context;
use SDM\Altapay\Model\SystemConfig;

class Applepay extends Action implements CsrfAwareActionInterface
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
     * Applepay constructor.
     *
     * @param Context               $context
     * @param storeConfig           $storeConfig
     * @param SystemConfig          $systemConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        storeConfig $storeConfig,
        SystemConfig $systemConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeConfig   = $storeConfig;
        $this->systemConfig  = $systemConfig;
        $this->_storeManager = $storeManager;
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

    public function execute()
    {
        $storeCode     = $this->getStoreCode();
        $validationUrl = $this->getRequest()->getParam('validationUrl');
        $terminalName = $this->getRequest()->getParam('termminalid');
        //Test the conn with the Payment Gateway
        $auth     = $this->systemConfig->getAuth($storeCode);
        $api      = new TestAuthentication($auth);
        $response = $api->call();
        if (!$response) {
            return false;
        }
       
        $request = new ApplepayWalletSession($auth);
        $request->setTerminal($terminalName)
                ->setValidationUrl($validationUrl)
                ->setDomain('creativeminors.com');

        $response = $request->call();
        if ($response->Result === 'Success') {
            echo json_encode($response->ApplePaySession);
        }
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
