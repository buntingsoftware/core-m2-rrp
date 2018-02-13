<?php
namespace Bunting\Core\Block;

use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Catalog\Block\Product\View\AbstractView;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class Product extends AbstractView
{
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        CollectionFactory $buntingCollectionFactory,
        Configurable $configurable,
        array $data = [])
    {
        $bunting = $buntingCollectionFactory->create()->getFirstItem();
        $this->assign('bunting',$bunting);
        $this->_configurable = $configurable;
        parent::__construct($context, $arrayUtils, $data);
    }
    
    public function getProductIdOrParent() {
        $product_id = $this->getProduct()->getId();
        if ($this->getProduct()->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $parentIds = $this->_configurable->getParentIdsByChild($product_id);
            if(isset($parentIds[0])){
                $product_id = $parentIds[0];
            }
        }
        return $product_id;
    }
}