<?php

namespace Yotpo\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Yotpo\Core\Model\Sync\Orders\Processor as OrdersProcessor;
use Yotpo\Core\Model\Config;
use Magento\Sales\Model\OrderRepository;

/**
 * Class SalesOrderSaveAfter
 * Observer is called when order is created/updated
 */
class AdminSalesOrderAddressUpdate implements ObserverInterface
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrdersProcessor
     */
    protected $ordersProcessor;

    /**
     * @var Config
     */
    protected $yotpoConfig;

    /**
     * AdminSalesOrderAddressUpdate constructor.
     * @param OrderRepository $orderRepository
     * @param OrdersProcessor $ordersProcessor
     * @param Config $yotpoConfig
     */
    public function __construct(
        OrderRepository $orderRepository,
        OrdersProcessor $ordersProcessor,
        Config $yotpoConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->ordersProcessor = $ordersProcessor;
        $this->yotpoConfig = $yotpoConfig;
    }
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var  \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($observer->getOrderId());
        if ($this->yotpoConfig->isOrdersSyncActive($order->getStoreId())) {
            $this->ordersProcessor->processOrder($order);
        }
    }
}
