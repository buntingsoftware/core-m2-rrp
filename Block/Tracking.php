<?php
namespace Bunting\Core\Block;

use Magento\Backend\Block\Template\Context;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;

class Tracking extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        Context $context,
        CollectionFactory $buntingCollectionFactory,
        \Magento\Store\Api\Data\StoreInterface $store,
        array $data = [])
    {
        $bunting = $buntingCollectionFactory->create()->getFirstItem();
        $this->assign('bunting', $bunting);
        $this->assign('store', $store);


        parent::__construct($context, $data);
    }
}