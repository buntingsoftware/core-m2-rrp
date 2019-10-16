<?php
namespace Bunting\Core\Block;

use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Catalog\Block\Product\View\AbstractView;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Product extends AbstractView
{
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        CollectionFactory $buntingCollectionFactory,
        Configurable $configurable,
        ScopeConfigInterface $scopeConfig,
        StockItemRepository $stockItemRepository,
        ProductRepositoryInterface $productRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = [])
    {
        $bunting = $buntingCollectionFactory->create()->getFirstItem();
        $this->assign('bunting',$bunting);
        $this->_configurable = $configurable;
        $this->_scopeConfig = $scopeConfig;
        $this->_stockItemRepository = $stockItemRepository;
        $this->_productRepositoryInterface = $productRepositoryInterface;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
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

    /**
     * @return float
     */
    public function getProductDisplayPrice() {
        $product = $this->getProduct();
        $price = $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
        $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getAmount()->getValue();
        return number_format($specialPrice ? $specialPrice : $price, 2);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductCurrencyCode() {
        try {
            return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch (\Exception $e) {
            return 'UNKNOWN';
        }
    }

    /**
     * @return string
     */
    public function getProductName() {
        return $this->getProduct()->getName();
    }

    /**
     * @return string|bool
     */
    public function getProductImageUrl() {
        $image = $this->getProduct()->getData('small_image');
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $image != 'no_selection' ? "{$mediaUrl}catalog/product{$image}" : false;
    }

    /**
     * @return int|string
     */
    public function getProductStockQty() {
        $product = $this->getProduct();
        $isProductConfigurable = $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;

        try {
            $stockItem = $this->_stockItemRepository->get($product->getId());
            return $stockItem->getIsInStock()
                ? $isProductConfigurable
                    ? $this->getConfigurableStock($product, $stockItem)
                    : (int)$stockItem->getQty()
                : 'n';
        } catch (\Exception $e) {
            return 'y';
        }
    }

    /**
     * @return bool|string
     */
    public function getProductBrand() {
        $product = $this->getProduct();

        foreach (['brand', 'manufacturer'] as $code) {
            $attribute = $product->getResource()->getAttribute('brand');

            if (is_object($attribute)) {
                $value = $attribute->getFrontend()->getValue($product);

                if ($value) {
                    return $value;
                }
            }
        }

        return false;
    }

    /**
     * Bunting refers to languages by a 3 digit code (for example, ENG) instead of a standard localisation code (for example, en_GB)
     * This method maps those
     *
     * @return string|null
     */
    public function getProductLanguageCode() {
        $code = strtoupper(substr($this->_scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), 0, 2));
        $mapping = $this->getBuntingLanguageCodeMapping();
        return isset($mapping[$code]) ? $mapping[$code] : 'UNK';
    }

    /**
     * @return array
     */
    private function getBuntingLanguageCodeMapping() {
        return array(
            'EN' => 'ENG',
            'FR' => 'FRA',
            'DE' => 'DEU',
            'AR' => 'ARA',
            'ES' => 'SPA',
            'BN' => 'BEN',
            'ZH' => 'ZHO',
            'HI' => 'HIN',
            'RU' => 'RUS',
            'PT' => 'POR',
            'JA' => 'JPN',
            'JV' => 'JAV',
            'KO' => 'KOR',
            'TR' => 'TUR',
            'VI' => 'VIE',
            'TE' => 'TEL',
            'MR' => 'MAR',
            'TA' => 'TAM',
            'IT' => 'ITA',
            'UR' => 'URD',
            'GU' => 'GUJ',
            'PL' => 'POL',
            'UK' => 'UKR',
            'NL' => 'NLD',
            'CS' => 'CES',
            'DA' => 'DAN',
            'SK' => 'SLO',
            'HE' => 'HEB'
        );
    }

    /**
     * @param $product
     * @return int
     */
    private function getConfigurableStock($product) {
        $stockQty = 0;
        $subIds = $this->_configurable->getChildrenIds($product->getId());

        if (isset($subIds[0]) && is_array($subIds[0]) && !empty($subIds[0])) {
            try {
                foreach ($this->getEnabledProductsByIds($subIds[0]) as $subProduct) {
                    $subStockItem = $this->_stockItemRepository->get($subProduct->getId());
                    $stockQty = $subStockItem->getIsInStock() ? (int)$subStockItem->getQty() + $stockQty : $stockQty;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return 0;
            }
        }

        return $stockQty;
    }

    /**
     * @param array $ids
     * @return mixed
     */
    private function getEnabledProductsByIds(array $ids) {
        $searchCriteria = $this->_searchCriteriaBuilder->addFilter('entity_id', $ids, 'in')
            ->addFilter('status', 1 )
            ->create();

        return $this->_productRepositoryInterface->getList($searchCriteria)->getItems();
    }
}
