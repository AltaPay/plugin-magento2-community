<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use SDM\Altapay\Model\TokenFactory;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    /**
     * @var TokenFactory
     */
    protected $tokenFactory;
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    /**
     * @var Session
     */
    protected $checkoutSession;

    public function __construct(
        Context $context,
        TokenFactory $tokenFactory,
        ResultFactory $resultFactory,
        Session $checkoutSession
    ) {
        $this->tokenFactory    = $tokenFactory;
        $this->resultFactory   = $resultFactory;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }


    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface|void
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $action = $this->getRequest()->getParam('action');
        $model  = $this->tokenFactory->create();
        if ($action == "delete") {
            $response = ['status' => 'error'];
            $tokenId  = $this->getRequest()->getParam('token_id');
            $token    = $model->load($tokenId);
            if ($token->getId()) {
                if ($this->checkoutSession->getCustomer()->getId() == $token->getCustomerId()) {
                    try {
                        $model->load($tokenId)->delete();
                        $response = ['status' => 'deleted'];
                    } catch (\Exception $e) {
                        $response = ['status' => 'error'];
                    }
                }
            }

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                                       ->setData([
                                           'Content-type' => 'text/json; charset=UTF-8',
                                           'status'       => $response
                                       ]);
        } elseif ($action == "primary") {
            $response = ['status' => 'error'];
            $tokenId  = $this->getRequest()->getParam('token_id');
            $token    = $model->load($tokenId);
            if ($token->getId()) {
                if ($this->checkoutSession->getCustomer()->getId() == $token->getCustomerId()) {
                    try {
                        $model->setPrimary(1);
                        $model->setId($tokenId);
                        $model->save();
                        $collection = $this->tokenFactory->create()->getCollection()
                                                         ->addFieldToFilter('customer_id', $token->getCustomerId())
                                                         ->addFieldToFilter('id', ['neq' => $token->getId()])
                                                         ->addFieldToFilter('primary', 1);
                        foreach ($collection as $model) {
                            $model->setPrimary(0)->save();
                        }
                        $response = ['status' => 'updated'];
                    } catch (\Exception $e) {
                        $response = ['status' => 'error'];
                    }
                }
            }

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                                       ->setData([
                                           'Content-type' => 'text/json; charset=UTF-8',
                                           'status'       => $response
                                       ]);
        } else {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        }
    }
}
