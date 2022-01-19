<?php
 
 namespace SDM\Altapay\Model\Config;
  
 class CronConfig extends \Magento\Framework\App\Config\Value
 {
    const CRON_STRING_PATH = 'crontab/default/jobs/sdm_altapay_cron_job/schedule/cron_expr';
 
    const CRON_MODEL_PATH = 'crontab/default/jobs/sdm_altapay_cron_job/run/model';

    const CRON_ENABLED = 'payment/sdm_altapay_config/cronScheduled/enabled';
     
      /**
      * @var \Magento\Framework\App\Config\ValueFactory
      */
  
     protected $_configValueFactory;
  
     /**
      * @var mixed|string
      */
  
     protected $_runModelPath = '';
  
     /**
      * CronConfig1 constructor.
      * @param \Magento\Framework\Model\Context $context
      * @param \Magento\Framework\Registry $registry
      * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
      * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
      * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
      * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
      * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
      * @param string $runModelPath
      * @param array $data
      */
  
     public function __construct(
         \Magento\Framework\Model\Context $context,
         \Magento\Framework\Registry $registry,
         \Magento\Framework\App\Config\ScopeConfigInterface $config,
         \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
         \Magento\Framework\App\Config\ValueFactory $configValueFactory,
         \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
         \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
         \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
         $runModelPath = '',
         array $data = [])
     {
         $this->_runModelPath = $runModelPath;
         $this->_configValueFactory = $configValueFactory;
         $this->scopeConfig = $scopeConfig;
         parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
     }
  
     /**
      * @return CronConfig1
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
             throw new \Exception(__('Some Thing Want Wrong , We can\'t save the cron expression.'));
         }
         return parent::afterSave();
     }
 }