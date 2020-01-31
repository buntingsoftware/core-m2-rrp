<?php
namespace Bunting\Core\Controller\Index;

use Magento\Framework\App\Action\Context;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Catalog\Model\Category;
use Magento\Review\Model\Review;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Bunting\Core\Helper\Gallery;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var string
     */
    private $xmlOutput;

    public function __construct(
        Context $context,
        CollectionFactory $buntingCollectionFactory,
        RawFactory $rawResultFactory,
        StoreManagerInterface $storeManagerInterface,
        ScopeConfigInterface $scopeConfigInterface,
        Collection $productCollection,
        StockItemRepository $stockItemRepository,
        Category $categoryModel,
        Review $reviewModel,
        ProductFactory $productFactory,
        Configurable $configurable,
        ProductRepositoryInterface $productRepositoryInterface,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Gallery $buntingHelper
    )
    {
        $this->_buntingCollectionFactory = $buntingCollectionFactory;
        $this->_rawResultFactory = $rawResultFactory;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_scopeConfigInterface = $scopeConfigInterface;
        $this->_productCollection = $productCollection;
        $this->_stockItemRepository = $stockItemRepository;
        $this->_categoryModel = $categoryModel;
        $this->_reviewModel = $reviewModel;
        $this->_productFactory = $productFactory;
        $this->_configurable = $configurable;
        $this->_productRepositoryInterface = $productRepositoryInterface;
        $this->_attributeRepository = $attributeRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_buntingHelper = $buntingHelper;
        $this->xmlOutput = '';
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $bunting = $this->_buntingCollectionFactory->create()->getFirstItem();
        $result = $this->_rawResultFactory->create();

        $feedToken = $this->getRequest()->getParam('feed_token');
        $limit = is_numeric($this->getRequest()->getParam('size')) ? min((int) $this->getRequest()->getParam('size'), 20000) : 1;
        $page = is_numeric($this->getRequest()->getParam('page')) ? ((int) $this->getRequest()->getParam('page')) : 0;
        $lastPage = floor($this->getEnabledProductCollection()->getSize() / $limit);

        // Internal pagination to prevent OOM errors
        $internalLimit = min(200, $limit);
        $internalPage = $page * ceil($limit / $internalLimit) + 1;
        $internalLastPage = (1 + $page) * (ceil($limit / $internalLimit));

        // Some pre-caching to improve performance
        $gtinAttribute = $this->getGtinAttributeCode();
        $stores = $this->getAllStoreData();

        if (!$feedToken || !$bunting || !$bunting->getFeedToken() || $feedToken !== $bunting->getFeedToken()) {
            return $this->sendError($result, 400, 'Bad Request');
        }

        $this->createXmlMetaOpen($bunting->getBuntingSubdomain(), $bunting->getBuntingWebsiteMonitorId(), $bunting->getServerRegionSubdomainId(), $page != $lastPage ? 'no' : 'yes');

        while ($internalPage <= $internalLastPage) {
            foreach ($this->getEnabledProducts($internalLimit, $internalPage) as $product) {
                $store = $mediaUrl = null;
                $cats = $product->getCategoryIds();
                end($cats);
                $categoryString = count($cats) && isset($cats[key($cats)]) ? $this->getCategoryBreadcrumb($this->_categoryModel->load($cats[key($cats)])) : false;
                $defaultStoreId = $stores[key($stores)]['store']->getId();
                $currencies = $names = [];
                $price = $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getAmount()->getValue();
                $isProductConfigurable = $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
                $productIsChild = !$isProductConfigurable && !empty($this->_configurable->getParentIdsByChild($product->getId()));
                $isMissingData = $product->getImage() == 'no_selection' || !$categoryString;

                try {
                    // Resolve an error where some indexed products don't have a valid stock item attached to them, also resolves issues for stockless products (gift cards)
                    $stockItem = $this->_stockItemRepository->get($product->getId());
                    $stockQty = $stockItem->getIsInStock() ? $isProductConfigurable ? $this->getConfigurableStock($product, $stockItem) : (int)$stockItem->getQty() : 'n';
                } catch(\Exception $e) {
                    $stockQty = "y";
                }

                if ($productIsChild || $isMissingData) {
                    continue;
                }

                foreach ($product->getStoreIds() as $storeId) {
                    $store = is_null($store) ? $stores[$storeId]['store'] : $store;
                    $mediaUrl = is_null($mediaUrl) ? $stores[$storeId]['media_url'] : null;
                    $currencies[] = $stores[$storeId]['currency'];
                    $language = $stores[$storeId]['language'];

                    if ($storeId != $defaultStoreId) {
                        $this->_storeManagerInterface->setCurrentStore($storeId);
                    }

                    $names[$language] = $storeId != $defaultStoreId ? $this->_productFactory->create()->load($product->getId())->getName() : $product->getName();
                }

                $this->_reviewModel->getEntitySummary($product, $product->getStoreId());
                $rating = $product->getRatingSummary()->getRatingSummary();

                $this->createXmlContainer('product', true, 1);

                $this->createXmlContainer('ns', true, 2);
                foreach ($names as $langCode => $langName) {
                    $this->createXmlTag($langCode, $langName, 3);
                }
                $this->createXmlContainer('ns', false, 2);

                $this->createXmlContainer('ps', true, 2);
                foreach ($currencies as $currency) {
                    $priceConverted = number_format($store->getBaseCurrency()->convert($specialPrice ? $specialPrice : $price, $currency), 2);
                    $this->createXmlTag($currency->getCode(), $priceConverted, 3);
                }
                $this->createXmlContainer('ps', false, 2);

                if ($specialPrice && $price != $specialPrice && (is_null($product->getSpecialToDate()) || time() < strtotime($product->getSpecialToDate()))) {
                    $this->createXmlContainer('oss', true, 2);
                    foreach ($currencies as $currency) {
                        $savingConverted = number_format($store->getBaseCurrency()->convert($price - $specialPrice, $currency), 2);
                        $this->createXmlTag($currency->getCode(), $savingConverted, 3);
                    }
                    $this->createXmlContainer('oss', false, 2);
                    $this->createXmlTag('oe', strtotime($product->getSpecialToDate()), 2, (bool)$product->getSpecialToDate());
                }

                $this->createXmlTag('upc', $product->getId(), 2);
                $this->createXmlTag('s', $stockQty, 2);
                $this->createXmlTag('iu', "{$mediaUrl}catalog/product{$product->getImage()}", 2, ($product->getImage() != 'no_selection'));
                $this->createXmlTag('b', $product->getAttributeText('brand'), 2, (bool)$product->getAttributeText('brand'));
                $this->createXmlTag('gtin', $product->getData($gtinAttribute), 2, ($gtinAttribute && $product->getData($gtinAttribute)));
                $this->createXmlTag('c', $categoryString, 2, (bool)$categoryString);
                $this->createXmlTag('rt', $rating / 20, 2, (bool)$rating);
                $this->createXmlTag('u', $product->getProductUrl(), 2);
                $this->createXmlTag('cv1', $product->getCustomAttribute('product_rrp')->getValue(), 2);

                $secondImage = $this->getSecondImageUrl($product);
                $this->createXmlTag('i2u', $secondImage, 2, $secondImage);

                $this->createXmlContainer('product', false, 1);
            }

            $internalPage++;
        }

        $this->createXmlContainer('feed', false);

        $result->setHeader('Content-Type', 'text/xml');
        $result->setContents($this->xmlOutput);
        return $result;
    }

    /**
     * @param $tag
     * @param bool $open
     * @param int $indent   Number of \t to render
     */
    private function createXmlContainer($tag, $open = true, $indent = 0) {
        $this->xmlOutput .= str_repeat("\t", $indent) . ($open ? "<{$tag}>\n" :  "</{$tag}>\n");
    }

    /**
     * Append a valid XML tag with properly escaped CDATA to $this->xmlOutput
     *
     * @param string $tag
     * @param string $value
     * @param int $indent   Number of \t to render
     * @param bool $conditional    If you pass a false to this (IE, a false conditional) it won't render the tag.
     *                              this is useful for rendering if statements unnecessary when rendering tags
     */
    private function createXmlTag($tag, $value, $indent = 0, $conditional = true) {
        if ($conditional) {
            $this->xmlOutput .= str_repeat("\t", $indent) . "<{$tag}><![CDATA[{$value}]]></{$tag}>\n";
        }
    }

    /**
     * @param string $domain
     * @param int $feedId
     * @param int $regionId
     * @param string $lastPage
     *
     * @param int
     */
    private function createXmlMetaOpen($domain, $feedId, $regionId, $lastPage) {
        $this->xmlOutput .= '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'."\n";
        $this->xmlOutput .= '<!DOCTYPE feed SYSTEM "https://' . $domain . (is_numeric($regionId) ? '.' . $regionId : '.') . 'bunting.com/feed-' . $feedId . '.dtd">'."\n";
        $this->xmlOutput .= '<feed last_page="' . $lastPage . '">'."\n";
    }

    /**
     * @param $category
     * @param string $breadcrumb
     * @return bool|string
     */
    private function getCategoryBreadcrumb($category, $breadcrumb = '') {
        $path = explode('/', $category->getPath());

        if (!count($path)) {
            return false;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        while (count($path) > 0) {
            $id = array_pop($path);
            $name = $objectManager->create('Magento\Catalog\Model\Category')->load($id)->getName();
            $breadcrumb = $name . (strlen($breadcrumb) ? "&gt;" : '') . $breadcrumb;
        }

        return $breadcrumb;
    }

    /**
     * @param \Magento\Framework\Controller\Result\Raw $result
     * @param $code
     * @param $message
     * @return \Magento\Framework\Controller\Result\Raw
     */
    private function sendError(\Magento\Framework\Controller\Result\Raw $result, $code, $message) {
        $result->setHttpResponseCode($code, true)
            ->setHeader('Content-Type', 'text/html');
        $result->setContents("{$code} ({$message})");
        return $result;
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
     * @return array
     */
    private function getAllStoreData() {
        $stores = [];

        foreach ($this->_storeManagerInterface->getStores(false) as $store) {
            $full_locale = $this->_scopeConfigInterface->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId());
            list($language, $locale) = explode('_', $full_locale);

            $stores[$store->getId()] = [
                'store' => $store,
                'media_url' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                'currency' => $store->getDefaultCurrency(),
                'language' => $language
            ];
        }

        return $stores;
    }

    /**
     * @return mixed
     */
    private function getEnabledProductCollection() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', array('eq' => 1))
            ->addAttributeToFilter('small_image', array('notnull'=>'','neq'=>'no_selection'));
    }

    /**
     * @param int $limit
     * @param int $page
     * @return mixed
     */
    private function getEnabledProducts($limit, $page) {
        $products = $this->getEnabledProductCollection()
            ->setOrder('date','ASC');

        $products->getSelect()->limitPage($page, $limit);
        $products->load();
        return $products;
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

    /**
     * GTIN isn't a globally recognised value, so localise GTINs to get the best approximation
     *
     * @return bool|string
     */
    private function getGtinAttributeCode() {
        foreach (['ean', 'upc', 'jan', 'isbn'] as $code) {
            if ($this->attributeCodeExists($code)) {
                return ucfirst($code);
            }
        }

        return false;
    }

    /**
     * @param string $code
     * @return bool
     */
    private function attributeCodeExists($code) {
        try {
            $this->_attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, $code);
            return true;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * This call requires a query for uncached images, so this block is to stop wasted calls where no images are present
     *
     * @param $product
     * @return bool
     */
    private function getSecondImageUrl($product) {
        if ($product->getImage() != 'no_selection') {
            $this->_buntingHelper->addGallery($product);
            $currentImage = 0;

            foreach ($product->getMediaGalleryImages() as $image) {
                if ($currentImage == 1) {
                    return $image->getUrl();
                }

                $currentImage++;
            }
        }

        return false;
    }
}
