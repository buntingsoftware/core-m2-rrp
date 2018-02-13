<?php

namespace Bunting\Core\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Manager;

class Init implements ObserverInterface
{
    /** @var \Magento\Framework\Event\Manager  */
    private $eventManager;

    /**
     * @param Manager $eventManager
     */
    public function __construct(Manager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->eventManager->dispatch('bunting_core_init_hook');
    }
}