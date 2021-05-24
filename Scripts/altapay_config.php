<?php
require_once __DIR__ . './../../../../app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
require dirname(__FILE__) . '/abstract.php';
use SDM\Altapay\Api\Others\Terminals;
use SDM\Altapay\Api\Test\TestAuthentication;
use GuzzleHttp\Exception\ClientException;
use SDM\Altapay\Authentication;
use Magento\Framework\App\Cache\Type\Config as cacheConfig;

class InstallTermConfig extends AbstractApp
{

    public function run()
    {
        $apiUser = "username";
        $apiPass = "password";
        $url     = "shopurl";

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
    }
}

/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('InstallTermConfig');
$bootstrap->run($app);