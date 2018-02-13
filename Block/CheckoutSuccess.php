<?php
namespace Bunting\Core\Block;

use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class CheckoutSuccess extends \Magento\Checkout\Block\Success
{

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        CollectionFactory $buntingCollectionFactory,
        Configurable $configurable,
        array $data = []
    ) {
        parent::__construct($context, $orderFactory, $data);
        $this->_orderRepository = $orderRepository;
        $bunting = $buntingCollectionFactory->create()->getFirstItem();
        $this->assign('bunting',$bunting);
        $order = $this->_orderRepository->get($this->getRealOrderId());
        $this->assign('order', $order);
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
