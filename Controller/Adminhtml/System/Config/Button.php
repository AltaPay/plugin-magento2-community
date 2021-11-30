<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use SDM\Altapay\Model\SystemConfig;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Type\Config as cacheConfig;

class Button extends Action
{
    /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

    protected $resultJsonFactory;

    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @param Context              $context
     * @param SystemConfig         $systemConfig
     * @param Config               $resourceConfig
     * @param ScopeConfigInterface $storeConfig
     * @param ResponseHttp         $response
     * @param TypeListInterface    $cacheTypeList
     * @param JsonFactory          $resultJsonFactory
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig,
        Config $resourceConfig,
        ScopeConfigInterface $storeConfig,
        ResponseHttp $response,
        TypeListInterface $cacheTypeList,
        JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->systemConfig      = $systemConfig;
        $this->resourceConfig    = $resourceConfig;
        $this->storeConfig       = $storeConfig;
        $this->_response         = $response;
        $this->cacheTypeList     = $cacheTypeList;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $currentCurrency = $this->storeConfig->getValue(
            self::COUNTRY_CODE_PATH,
            ScopeInterface::SCOPE_STORE,
            $this->getRequest()->getParam('storeid')
        );
        try {
            $terminals = [];
            $call      = new \SDM\Altapay\Api\Others\Terminals($this->systemConfig->getAuth());
            /** @var TerminalsResponse $response */
            $response = $call->call();
            foreach ($response->Terminals as $terminal) {
                if ($terminal->Country == $currentCurrency) {
                    $terminals[] = $terminal->Title;
                }
            }
            if (count($terminals) <= 5) {
                $i = 1;
                foreach ($terminals as $terminal) {
                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/active',
                        1,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/title',
                        $terminal,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/language',
                        null,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/capture',
                        0,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/terminallogo',
                        '',
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/showlogoandtitle',
                        0,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/savecardtoken',
                        0,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/avscontrol',
                        0,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/enforceavs',
                        0,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/avs_acceptance',
                        0,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/sort_order',
                        0,
                        'default',
                        0
                    );

                    $this->resourceConfig->saveConfig(
                        'payment/terminal' . $i . '/terminalname',
                        $terminal,
                        'default',
                        0
                    );

                    $i++;
                }
                // Clean cache
                $this->cacheTypeList->cleanType(cacheConfig::TYPE_IDENTIFIER);

                return $this->_response;
            } else {
                $message
                    = 'We could not match terminals to this store. Too many terminals exists, please check the dropdown manually';
            }

        } catch (ClientException $e) {
            $message = "Error:" . $e->getMessage();
        } catch (Exception $e) {
            $message = "Error:" . $e->getMessage();
        }

        /** @var Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData(['message' => $message]);
    }

}