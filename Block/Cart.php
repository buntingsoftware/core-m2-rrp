<?php
namespace Bunting\Core\Block;

use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class Cart extends \Magento\Checkout\Block\Cart\AbstractCart
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        CollectionFactory $buntingCollectionFactory,
        Configurable $configurable,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $data
        );
        $bunting = $buntingCollectionFactory->create()->getFirstItem();
        $this->assign('cart_quote', $checkoutSession->getQuote());
        $this->assign('bunting',$bunting);
        $this->_configurable = $configurable;
    }

    public function getProductIdOrParent($product) {
        $product_id = $product->getId();
        if ($product->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $parentIds = $this->_configurable->getParentIdsByChild($product_id);
            if(isset($parentIds[0])){
                $product_id = $parentIds[0];
            }
        }
        return $product_id;
    }
}