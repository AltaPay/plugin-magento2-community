<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Config;

use Magento\Cron\Model\Config\Source\Frequency;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Config\Value;
use Magento\Store\Model\ScopeInterface;

class CronAltapayTableConfig extends Value
{
    const CRON_STRING_PATH = 'crontab/default/jobs/sdm_altapay_cron_tranasaction_data/schedule/cron_expr';
    const CRON_MODEL_PATH = 'crontab/default/jobs/sdm_altapay_cron_tranasaction_data/run/model';
    const CRON_ENABLED = 'payment/sdm_altapay_config/cronScheduledTableOpt/enabled';
    
    /**
     * @var ValueFactory
     */
    protected $_configValueFactory;
    
    /**
     * @var mixed|string
     */
    protected $_runModelPath = '';
    
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @param Context               $context
     * @param Registry              $registry
     * @param TypeListInterface     $cacheTypeList
     * @param ValueFactory          $configValueFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null       $resourceCollection
     * @param ScopeConfigInterface  $scopeConfig
     * @param                       $runModelPath
     * @param array                 $data
     */
    public function __construct(
        Context              $context,
        Registry             $registry,
        TypeListInterface    $cacheTypeList,
        ValueFactory         $configValueFactory,
        AbstractResource     $resource = null,
        AbstractDb           $resourceCollection = null,
        ScopeConfigInterface $scopeConfig,
        $runModelPath = '',
        array                $data = []
    ) {
        $this->_runModelPath       = $runModelPath;
        $this->_configValueFactory = $configValueFactory;
        $this->scopeConfig         = $scopeConfig;
        parent::__construct($context, $registry, $scopeConfig, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    
    /**
     * @return mixed
     * @throws \Exception
     */
    public function afterSave()
    {
        $time        = $this->getData('groups/sdm_altapay_config/groups/cronScheduledTableOpt/fields/time/value');
        $frequency   = $this->getData('groups/sdm_altapay_config/groups/cronScheduledTableOpt/fields/frequency/value');
        $storeScope  = ScopeInterface::SCOPE_STORE;
        $cronEnabled = $this->scopeConfig->getValue(self::CRON_ENABLED, $storeScope);
        try {
            if ($cronEnabled) {
                $cronExprArray  = [
                    intval($time[1]),
                    // Hour
                    intval($time[0]),
                    // Minute
                    $frequency == Frequency::CRON_MONTHLY ? '1' : '*',
                    // Day of the month
                    '*',
                    // Month
                    $frequency == Frequency::CRON_WEEKLY ? '1' : '*',
                    // Day of the week
                ];
                $cronExprString = join(' ', $cronExprArray);
                $this->_configValueFactory->create()->load(
                    self::CRON_STRING_PATH,
                    'path'
                )->setValue(
                    $cronExprString
                )->setPath(
                    self::CRON_STRING_PATH
                )->save();
                $this->_configValueFactory->create()->load(
                    self::CRON_MODEL_PATH,
                    'path'
                )->setValue(
                    $this->_runModelPath
                )->setPath(
                    self::CRON_MODEL_PATH
                )->save();
            }
        } catch (\Exception $e) {
            throw new \Exception(__('Something went wrong, Can\'t save the cron expression.'));
        }
        return parent::afterSave();
    }
}