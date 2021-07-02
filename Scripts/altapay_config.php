<?php

$bootstrap = __DIR__ . './../../../../app/bootstrap.php';

if (file_exists ($bootstrap)){
    require_once $bootstrap;
}
else {
    require_once __DIR__ . '/../../../../../app/bootstrap.php';
}
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use SDM\Altapay\Api\Others\Terminals;
use SDM\Altapay\Api\Test\TestAuthentication;
use GuzzleHttp\Exception\ClientException;
use SDM\Altapay\Authentication;
use Magento\Framework\App\Cache\Type\Config as cacheConfig;
use \Magento\Framework\AppInterface as AppInterface;
use \Magento\Framework\App\Http as Http;
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

class InstallTermConfig extends Http implements AppInterface
{
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
        TypeListInterface $cacheTypeList
    ) {
        $this->_objectManager = $objectManager;
        $this->_eventManager = $eventManager;
        $this->_areaList = $areaList;
        $this->_request = $request;
        $this->_response = $response;
        $this->_configLoader = $configLoader;
        $this->_state = $state;
        $this->_filesystem = $filesystem;
        $this->registry = $registry;
        $this->resourceConfig = $resourceConfig;
        $this->encryptor      = $encryptor;
        $this->cacheTypeList  = $cacheTypeList;
    }

    public function launch()
    {
        $apiUser = "~gatewayusername~";
        $apiPass = "~gatewaypass~";
        $url     = "~gatewayurl~";
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

        $terminals = array();

        try {
            $api      = new Terminals(new Authentication($apiUser, $apiPass, $url));
            $response = $api->call();
            foreach ($response->Terminals as $terminal) {
                $terminals[] = $terminal->Title;
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

        for ($i = 1; $i <= 5; $i++) {

            $this->resourceConfig->saveConfig(
                'payment/terminal' . $i . '/active',
                1,
                'default',
                0
            );

            $this->resourceConfig->saveConfig(
                'payment/terminal' . $i . '/title',
                $terminals[$i],
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
                $terminals[$i],
                'default',
                0
            );
        }

        $this->cacheTypeList->cleanType(cacheConfig::TYPE_IDENTIFIER);
        
        return $this->_response;
    }

}

/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('InstallTermConfig');
// $bootstrap->run($app);