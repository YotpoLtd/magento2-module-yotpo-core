<?php
namespace Yotpo\Core\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class is for schedule the cron job for trigger product api
 */
class CronConfig extends Value
{
    /**
     * Cron expression configuration path
     */
    const CRON_STRING_PATH = 'crontab/default/jobs/yotpo_cron_core_products_sync/schedule/cron_expr';

    /**
     * Cron expression model path
     */
    const CRON_MODEL_PATH = 'crontab/default/jobs/yotpo_cron_core_products_sync/run/model';

    /**
     * Cron expression configuration path
     */
    const CRON_STRING_PATH_CATEGORY = 'crontab/default/jobs/yotpo_cron_core_category_sync/schedule/cron_expr';

    /**
     * Cron expression model path
     */
    const CRON_MODEL_PATH_CATEGORY = 'crontab/default/jobs/yotpo_cron_core_category_sync/run/model';

    /**
     * @var ValueFactory
     */
    protected $configValueFactory;

    /**
     * @var string
     */
    protected $runModelPath = '';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param AbstractResource $resource
     * @param AbstractDb<mixed> $resourceCollection
     * @param string $runModelPath
     * @param array<mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $configValueFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->runModelPath = $runModelPath;
        $this->configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     * @throws \Exception
     */
    public function afterSave()
    {
        $cronExprString = $this->getData('groups/sync_settings/groups/catalog_sync/fields/frequency/value');
        try {
            /** @phpstan-ignore-next-line */
            $this->configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
            /** @phpstan-ignore-next-line */
            $this->configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();

            /** @phpstan-ignore-next-line */
            $this->configValueFactory->create()->load(
                self::CRON_STRING_PATH_CATEGORY,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH_CATEGORY
            )->save();
            /** @phpstan-ignore-next-line */
            $this->configValueFactory->create()->load(
                self::CRON_MODEL_PATH_CATEGORY,
                'path'
            )->setValue(
                $this->runModelPath
            )->setPath(
                self::CRON_MODEL_PATH_CATEGORY
            )->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('We can\'t save the cron expression.'),
                $e
            );
        }

        return parent::afterSave();
    }
}
