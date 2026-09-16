<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Altapay\Api\Others\Terminals;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * The terminal details of the gateway, stored with the configuration.
 */
class TerminalData
{
    public const FIELD_IDENTIFIER     = 'terminalidentifier';
    public const FIELD_IS_GOOGLE_PAY  = 'isgooglepay';
    public const FIELD_SCHEMES        = 'terminalschemes';
    public const FIELD_ENVIRONMENT    = 'walletenvironment';
    public const FIELD_MERCHANT_ID    = 'walletmerchantid';
    public const FIELD_MERCHANT_NAME  = 'walletmerchantname';

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @param SystemConfig         $systemConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param Config               $resourceConfig
     */
    public function __construct(
        SystemConfig $systemConfig,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig
    ) {
        $this->systemConfig   = $systemConfig;
        $this->scopeConfig    = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * Read the terminals from the gateway and store them.
     *
     * @param string $scope
     * @param int    $scopeId
     * @return void
     * @throws \Exception
     */
    public function sync($scope, $scopeId)
    {
        $this->save((new Terminals($this->systemConfig->getAuth()))->call(), $scope, $scopeId);
    }

    /**
     * Store the terminals of a getTerminals response.
     *
     * @param mixed  $response
     * @param string $scope
     * @param int    $scopeId
     * @return void
     */
    public function save($response, $scope, $scopeId)
    {
        if (empty($response->Terminals)) {
            return;
        }

        $terminals = [];

        foreach ($response->Terminals as $terminal) {
            $terminals[$terminal->Title] = $terminal;
        }

        $scopeType = $scope === ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            : $scope;
        $scopeCode = $scope === ScopeConfigInterface::SCOPE_TYPE_DEFAULT ? null : $scopeId;

        foreach (SystemConfig::getTerminalCodes() as $terminalCode) {
            $terminalName = $this->scopeConfig->getValue(
                'payment/' . $terminalCode . '/terminalname',
                $scopeType,
                $scopeCode
            );

            $this->saveTerminal($terminalCode, $terminals[$terminalName] ?? null, $scope, $scopeId);
        }
    }

    /**
     * Store the terminal details
     *
     * @param string     $terminalCode
     * @param mixed|null $terminal
     * @param string     $scope
     * @param int        $scopeId
     * @return void
     */
    private function saveTerminal($terminalCode, $terminal, $scope, $scopeId)
    {
        $identifier  = $terminal->PrimaryMethod->Identifier ?? '';
        $isGooglePay = $identifier === 'GooglePay';
        $wallet      = $this->getMethodConfiguration($terminal, $identifier);
        $schemes     = $this->getSchemes($terminal);

        $values = [
            self::FIELD_IDENTIFIER    => $identifier ?: null,
            self::FIELD_IS_GOOGLE_PAY => $isGooglePay ? 1 : null,
            self::FIELD_SCHEMES       => $schemes ?: null,
            self::FIELD_ENVIRONMENT   => $wallet->WalletEnvironment ?? null,
            self::FIELD_MERCHANT_ID   => $wallet->MerchantId ?? null,
            self::FIELD_MERCHANT_NAME => $wallet->MerchantName ?? null
        ];

        foreach ($values as $field => $value) {
            $this->resourceConfig->saveConfig(
                'payment/' . $terminalCode . '/' . $field,
                $value,
                $scope,
                $scopeId
            );
        }
    }

    /**
     * Payment method configurations.
     *
     * @param mixed|null $terminal
     * @param string     $identifier
     * @return mixed|null
     */
    private function getMethodConfiguration($terminal, $identifier)
    {
        if (!$terminal || !$identifier) {
            return null;
        }

        foreach ($terminal->MethodConfigurations ?? [] as $methodConfig) {
            if (isset($methodConfig->identifier) && $methodConfig->identifier === $identifier) {
                return $methodConfig;
            }
        }

        return null;
    }

    /**
     * The card schemes of the terminal.
     *
     * @param mixed|null $terminal
     * @return string
     */
    private function getSchemes($terminal)
    {
        $schemes = [];

        foreach ($terminal->Schemes ?? [] as $scheme) {
            if (!empty($scheme->code)) {
                $schemes[] = $scheme->code;
            }
        }

        return implode(',', $schemes);
    }
}
