<?php

$bootstrap = __DIR__ . './../../../../app/bootstrap.php';

if (file_exists($bootstrap)) {
    require_once $bootstrap;
}
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use Altapay\Api\Others\Terminals;
use Altapay\Api\Test\TestAuthentication;
use GuzzleHttp\Exception\ClientException;
use Altapay\Authentication;
use Magento\Framework\App\Cache\Type\Config as cacheConfig;
use Magento\Framework\AppInterface as AppInterface;
use Magento\Framework\App\Http as Http;
use Magento\Framework\ObjectManager\ConfigLoaderInterface;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Event;
use Magento\Framework\Filesystem;
use Magento\Framework\App\AreaList as AreaList;
use Magento\Framework\App\State as State;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Registry;
use Magento\Framework\ObjectManagerInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class InstallTermConfig extends Http implements AppInterface
{
    /**
     * @var Filesystem
     */
    protected $_filesystem;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var Event\Manager
     */
    protected $_eventManager;
    /**
     * @var AreaList
     */
    protected $_areaList;
    /**
     * @var RequestHttp
     */
    protected $_request;
    /**
     * @var ResponseHttp
     */
    protected $_response;
    /**
     * @var ConfigLoaderInterface
     */
    protected $_configLoader;
    /**
     * @var State
     */
    protected $_state;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var Config
     */
    protected $resourceConfig;
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;
    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;
    
    public function __construct(
        ObjectManagerInterface $objectManager,
        Event\Manager $eventManager,
        AreaList $areaList,
        RequestHttp $request,
        ResponseHttp $response,
        ConfigLoaderInterface $configLoader,
        State $state,
        Filesystem $filesystem,
        Registry $registry,
        Config $resourceConfig,
        EncryptorInterface $encryptor,
        TypeListInterface $cacheTypeList,
        RuleCollectionFactory $ruleCollectionFactory
    ) {
        $this->_objectManager = $objectManager;
        $this->_eventManager  = $eventManager;
        $this->_areaList      = $areaList;
        $this->_request       = $request;
        $this->_response      = $response;
        $this->_configLoader  = $configLoader;
        $this->_state         = $state;
        $this->_filesystem    = $filesystem;
        $this->registry       = $registry;
        $this->resourceConfig = $resourceConfig;
        $this->encryptor      = $encryptor;
        $this->cacheTypeList  = $cacheTypeList;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    public function launch()
    {
        $apiUser = "~gatewayusername~";
        $apiPass = "~gatewaypass~";
        $url     = "~gatewayurl~";
        $terminals = array();
        try {
            $api      = new TestAuthentication(new Authentication($apiUser, $apiPass, $url));
            $response = $api->call();
            if (!$response) {
                echo "API credentials are incorrect";
                exit();
            }
        } catch (ClientException $e) {
            echo "Error:" . $e->getMessage();
            exit();
        } catch (Exception $e) {
            echo "Error:" . $e->getMessage();
            exit();
        }

        // Get Terminals

        try {
            $api      = new Terminals(new Authentication($apiUser, $apiPass, $url));
            $response = $api->call();
            $i        = 1;
            foreach ($response->Terminals as $terminal) {
                if ($i <= 5) {
                    $terminals[] = $terminal->Title;
                    $i++;
                }
            }
        } catch (ClientException $e) {
            echo "Error:" . $e->getMessage();
            exit();
        } catch (Exception $e) {
            echo "Error:" . $e->getMessage();
            exit();
        }

        // Save API details

        $this->resourceConfig->saveConfig(
            'payment/altapay_config/api_log_in',
            $apiUser,
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/altapay_config/api_pass_word',
            $this->encryptor->encrypt($apiPass),
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/altapay_config/productionurl',
            $url,
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/sdm_altapay_config/general/payment_template',
            'payment_sdm_altapay_config_general_payment_template',
            'default',
            0
        );

        // Save Order Status

        $this->resourceConfig->saveConfig(
            'payment/altapay_status/before',
            'pending',
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/altapay_status/process',
            'processing',
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/altapay_status/fraud',
            'fraud',
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/altapay_status/cancel',
            'canceled',
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/altapay_status/autocapture',
            'processing',
            'default',
            0
        );

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

        // Get All active catalog rules
        $catalogActiveRule = $this->ruleCollectionFactory->create();

        $discountRules = array(
            'by_fixed' => 'AltaPay Catalog Rule Fixed',
            'by_percent' => 'AltaPay Catalog Rule Percentage'
        );

        foreach ($catalogActiveRule as $rule) {
            $ruleData = $rule->getData();

            if (($key = array_search($ruleData['name'], $discountRules)) !== false) {
                unset($discountRules[$key]);
            }
        }

        // Create catalog rule if not exists
        if ($discountRules) {
            foreach ($discountRules as $key => $rule) {
                $this->altapayCatalogPriceRule($key, $rule);
            }
        }

        $taxRate = $this->_objectManager->create(\Magento\Tax\Model\Calculation\Rate::class);
        $taxRate->loadByCode('DKTaxRate');

        // Create tax rate if not exists
        if (!$taxRate->getId()) {
            $taxRate = $this->altapayCreateTaxRate($taxRate);
        }

        $taxRateId = $taxRate->getId();

        $taxRule = $this->_objectManager->create(\Magento\Tax\Model\Calculation\Rule::class);
        $taxRule->load('AltaPayTaxRule', 'code');

        // Create tax rule and assign tax rate
        if (!$taxRule->getId()) {
            $this->altapayCreateTaxRule($taxRule, $taxRateId);
        }

        // Clean cache
        $this->cacheTypeList->cleanType(cacheConfig::TYPE_IDENTIFIER);

        return $this->_response;
    }

    /**
     * @param $type
     * @param $name
     */
    private function altapayCatalogPriceRule($type, $name)
    {
        // Create catalog price rule
        $model = $this->_objectManager->create('Magento\CatalogRule\Model\Rule');
        $model->setName($name)
                ->setDescription($name)
                ->setIsActive(0)
                ->setCustomerGroupIds(array(0, 1, 2, 3))
                ->setWebsiteIds(array(1))
                ->setFromDate('')
                ->setToDate('')
                ->setSimpleAction($type)
                ->setDiscountAmount(15)
                ->setStopRulesProcessing(0);

        $conditions = array();
        $conditions["1"] = array(
            "type"          => "Magento\CatalogRule\Model\Rule\Condition\Combine",
            "aggregator"    => "all",
            "value"         => 1,
            "new_child"     => ""
        );
        $conditions["1--1"] = array(
            "type"      => "Magento\CatalogRule\Model\Rule\Condition\Product",
            "attribute" => "sku",
            "operator"  => "==",
            "value"     => "24-MB02"
        );

        $model->setData('conditions', $conditions);

        // Validating rule data before Saving
        $validateResult = $model->validateData(new \Magento\Framework\DataObject($model->getData()));
        if ($validateResult !== true) {
            foreach ($validateResult as $errorMessage) {
                echo $errorMessage;
            }
            return;
        }

        try {
            $model->loadPost($model->getData());
            $model->save();
            $ruleJob = $this->_objectManager->get('Magento\CatalogRule\Model\Rule\Job');
            $ruleJob->applyAll();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param $taxRate
     * @return \Magento\Tax\Model\Calculation\Rate[]
     */
    private function altapayCreateTaxRate($taxRate)
    {
        $taxRate->setTaxCountryId('DK');
        $taxRate->setTaxRegionId(0);
        $taxRate->setTaxPostcode('*');
        $taxRate->setCode('DKTaxRate');
        $taxRate->setRate('12.5');
        $taxRate->save();
        
        return $taxRate;
    }

    /**
     * @param $taxRule
     * @param $taxRateId
     */
    private function altapayCreateTaxRule($taxRule, $taxRateId)
    {
        $taxRule->setCode("AltaPayTaxRule");
        $taxRule->setPriority(0);
        $taxRule->setCustomerTaxClassIds(array(3));
        $taxRule->setProductTaxClassIds(array(2));
        $taxRule->setTaxRateIds(array($taxRateId));
        $taxRule->save();
    }

}

/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('InstallTermConfig');
// $bootstrap->run($app);