<?php

use Magento\Framework\App\Bootstrap;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Cache\Type\Config as cacheConfig;
use SDM\Altapay\Api\Others\Terminals;
use SDM\Altapay\Api\Test\TestAuthentication;
use GuzzleHttp\Exception\ClientException;
use SDM\Altapay\Authentication;

require_once __DIR__ . '/app/bootstrap.php';

$params    = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);


class InstallConfigurationsData extends \Magento\Framework\App\Http implements \Magento\Framework\AppInterface
{
    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @var Http
     */
    private $response;

    /**
     *
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        Config $resourceConfig,
        Http $response,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->response       = $response;
        $this->cacheTypeList  = $cacheTypeList;
        $this->encryptor      = $encryptor;
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

        return $this->response;
    }

    public function catchException(Bootstrap $bootstrap, \Exception $exception): bool
    {
        return false;
    }
}

/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('InstallConfigurationsData');
$bootstrap->run($app);
