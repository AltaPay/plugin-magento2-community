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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Altapay\Api\Others\Terminals;
use SDM\Altapay\Model\SystemConfig;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Type\Config as cacheConfig;
use Magento\Framework\App\Area;
use SDM\Altapay\Model\Config\Source\TerminalLogo;

class Button extends Action
{
    /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

    /**
     * @var JsonFactory
     */
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
     * @var ScopeConfigInterface
     */
    private $storeConfig;

    /**
     * @var TerminalLogo
     */
    private $terminalLogo;
    /**
     * @param Context               $context
     * @param SystemConfig          $systemConfig
     * @param Config                $resourceConfig
     * @param ScopeConfigInterface  $storeConfig
     * @param ResponseHttp          $response
     * @param TypeListInterface     $cacheTypeList
     * @param JsonFactory           $resultJsonFactory
     * @param State                 $state
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection    $resource
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig,
        Config $resourceConfig,
        ScopeConfigInterface $storeConfig,
        ResponseHttp $response,
        TypeListInterface $cacheTypeList,
        JsonFactory $resultJsonFactory,
        State $state,
        StoreManagerInterface $storeManager,
        ResourceConnection $resource,
        TerminalLogo $terminalLogo
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->systemConfig      = $systemConfig;
        $this->resourceConfig    = $resourceConfig;
        $this->storeConfig       = $storeConfig;
        $this->_response         = $response;
        $this->cacheTypeList     = $cacheTypeList;
        $this->storeManager      = $storeManager;
        $this->_state            = $state;
        $this->_resource         = $resource;
        $this->terminalLogo      = $terminalLogo;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {

        $currentStoreID = (int)$this->getRequest()->getParam('storeid');
        if ($currentStoreID == 0) {
            $scopeCode = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        } else {
            $scopeCode = ScopeInterface::SCOPE_STORES;
        }
        $currentCurrency = $this->storeConfig->getValue(
            self::COUNTRY_CODE_PATH,
            $scopeCode,
            $currentStoreID
        );

        try {
            $call = new Terminals($this->systemConfig->getAuth());
            /** @var TerminalsResponse $response */
            $response     = $call->call();
            $terminalList = $this->getTerminal($response, $currentCurrency);

            if (!empty($terminalList)) {
                if ($this->checkConfigAlreadyExist($terminalList, $scopeCode, $currentStoreID)) {
                    $message = __('Terminals are already configured, please check the dropdown manually.');
                } else {
                    $this->saveTerminalConfig($terminalList, $currentStoreID, $scopeCode);
                    $this->cacheTypeList->cleanType(cacheConfig::TYPE_IDENTIFIER);
                    $message = __('Terminals successfully configured!');
                }
            } else {
                $message = __('We could not match terminals to this store. Too many terminals exist, please check the dropdown manually.');
            }

        } catch (ClientException $e) {
            $message = __("Error:" . $e->getMessage());
        } catch (Exception $e) {
            $message = __("Error:" . $e->getMessage());
        }

        /** @var Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData(['message' => $message]);
    }

    /**
     * @param $response        array
     * @param $currentCurrency string
     *
     * @return array
     */
    public function getTerminal($response, $currentCurrency)
    {
        $terminals = [];
        $terminalLogoList = $this->terminalLogo->toOptionArray();

        foreach ($response->Terminals as $terminal) {
            if ($terminal->Country == $currentCurrency) {
                $identifier = $terminal->PrimaryMethod->Identifier;
                $logo = null;
                foreach ($terminalLogoList as $option) {
                    if ($option['label'] === $identifier) {
                        $logo = $option['value'];
                        break;
                    }
                }
                if ($logo !== null) {
                    $terminals[] = [
                        'title' => $terminal->Title,
                        'identifier' => $logo
                    ];
                }
            }
        }

        return $terminals;
    }

    /**
     * @param $terminals      array
     * @param $currentStoreID int
     * @param $scopeCode      string
     */
    public function saveTerminalConfig($terminals, $currentStoreID, $scopeCode)
    {
        foreach (array_slice($terminals, 0, 10) as $i => $terminal) {
            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/active',
                1,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/title',
                $terminal['title'],
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/language',
                null,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/capture',
                0,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/terminallogo',
                isset($terminal['identifier']) ? $terminal['identifier'] : '',
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/showlogoandtitle',
                0,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/savecardtoken',
                0,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/avscontrol',
                0,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/enforceavs',
                0,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/avs_acceptance',
                0,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/sort_order',
                0,
                $scopeCode,
                $currentStoreID
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . ($i + 1) . '/terminalname',
                $terminal['title'],
                $scopeCode,
                $currentStoreID
            );
        }
    }


    /**
     *  Check if a terminal configuration exists with a value of '1'.
     *
     * @param $terminalList array
     * @param $scopeCode    string
     *
     * @return bool
     */
    public function checkConfigAlreadyExist($terminalList, $scopeCode, $scopeID)
    {
        $tableName          = $this->_resource->getTableName('core_config_data');

        foreach ($terminalList as $i => $terminal) {
            //Initiate Connection
            $connection = $this->_resource->getConnection();
            $path       = 'payment/terminal' . ($i + 1) . '/active'; // Increment the terminal index
            $scope      = $scopeCode;
            $scopeId    = $scopeID;

            $select = $connection->select()
                ->from(
                    ['c' => $tableName],
                    ['config_id', 'value']
                )
                ->where(
                    "c.path = :path"
                )->where(
                    "c.scope = :scope"
                )->where(
                    "c.scope_id = :scope_id"
                );
            $bind   = ['path' => $path, 'scope' => $scope, 'scope_id' => $scopeId];

            $result = $connection->fetchRow($select, $bind);
            if ($result && $result['value'] === '1') {
                return true; // Break the loop and return true
            }
        }

        return false;
    }

}