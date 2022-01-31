<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SDM\Altapay\Model\Config;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Config\Value;

 class CronConfig extends Value
 {
    const CRON_STRING_PATH = 'crontab/default/jobs/sdm_altapay_cron_job/schedule/cron_expr';
    const CRON_MODEL_PATH = 'crontab/default/jobs/sdm_altapay_cron_job/run/model';
    const CRON_ENABLED = 'payment/sdm_altapay_config/cronScheduled/enabled';

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
      * CronConfig constructor.
      *
      * @param Context               $context
      * @param Registry              $registry
      * @param TypeListInterface     $cacheTypeList
      * @param ValueFactory          $configValueFactory
      * @param AbstractResource|null $resource
      * @param AbstractDb|null       $resourceCollection
      * @param ScopeConfigInterface  $scopeConfig
      * @param string                $runModelPath
      * @param array                 $data
      */
     public function __construct(
         Context $context,
         Registry $registry,
         TypeListInterface $cacheTypeList,
         ValueFactory $configValueFactory,
         AbstractResource $resource = null,
         AbstractDb $resourceCollection = null,
         ScopeConfigInterface $scopeConfig,
         $runModelPath = '',
         array $data = [])
     {
         $this->_runModelPath = $runModelPath;
         $this->_configValueFactory = $configValueFactory;
         $this->scopeConfig = $scopeConfig;
         parent::__construct($context, $registry, $scopeConfig, $cacheTypeList, $resource, $resourceCollection, $data);
     }

     /**
      * @return mixed
      * @throws \Exception
      */
     public function afterSave()
     {
        $time = $this->getData('groups/sdm_altapay_config/groups/cronScheduled/fields/time/value');
        $frequency = $this->getData('groups/sdm_altapay_config/groups/cronScheduled/fields/frequency/value');
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $cronEnabled = $this->scopeConfig->getValue(self::CRON_ENABLED, $storeScope);
         try
         {
             if($cronEnabled) {
                $cronExprArray = [
                    intval($time[1]), //Minute
                    intval($time[0]), //Hour
                    $frequency == \Magento\Cron\Model\Config\Source\Frequency::CRON_MONTHLY ? '1' : '*',
                    '*',
                    $frequency == \Magento\Cron\Model\Config\Source\Frequency::CRON_WEEKLY ? '1' : '*',
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
         }
         catch (\Exception $e)
         {
             throw new \Exception(__('Something went wrong, Can\'t save the cron expression.'));
         }
         return parent::afterSave();
     }
 }