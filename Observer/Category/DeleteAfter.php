<?php
namespace Yotpo\Core\Observer\Category;

use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Yotpo\Core\Model\Config;

/**
 * Class DeleteAfter - Update Yotpo Table
 */
class DeleteAfter implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Config
     */
    private $config;

    /**
     * DeleteAfter constructor.
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Config $config
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
    }

    /**
     * Set is_deleted = 1, is_deleted_at_yotpo = 0 in yotpo_category_sync table
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $connection = $this->resourceConnection->getConnection();

        /** @var Category $category */
        $category = $observer->getEvent()->getData('category');

        if (!($storeId = $category->getData('store_id'))) {
            $websiteId = $this->config->getDefaultWebsiteId();
            $storeId = $this->config->getDefaultStoreId($websiteId);
        }

        $connection->update(
            $connection->getTableName('yotpo_category_sync'),
            ['is_deleted' => 1, 'is_deleted_at_yotpo' => 0],
            [
                'category_id = ?' => $category->getData('entity_id'),
                'store_id = ? ' => $storeId
            ]
        );
    }
}
