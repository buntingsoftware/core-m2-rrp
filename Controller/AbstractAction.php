<?php
namespace Bunting\Core\Controller;

use Magento\Backend\App\Action\Context;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;

abstract class AbstractAction extends \Magento\Backend\App\Action {

    public function __construct(
        Context $context,
        CollectionFactory $buntingCollectionFactory
    )
    {
        $this->_buntingCollectionFactory = $buntingCollectionFactory;
        parent::__construct($context);
    }

    protected function getBuntingCollection() {
        return $this->_buntingCollectionFactory->create();
    }

    protected function getBunting() {
        $buntingCollection = $this->getBuntingCollection();
        return $buntingCollection->getFirstItem();
    }

    protected function buntingInstalled() {
        return $this->getBuntingCollection()->getSize();
    }

}