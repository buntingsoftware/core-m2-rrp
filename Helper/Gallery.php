<?php

namespace Bunting\Core\Helper;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;

class Gallery extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var GalleryReadHandler
     */
    protected $galleryReadHandler;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        GalleryReadHandler $galleryReadHandler
    )
    {
        $this->galleryReadHandler = $galleryReadHandler;
        parent::__construct($context);
    }

    /**
     * Add image gallery to product
     *
     * @param $product
     */
    public function addGallery($product) {
        $this->galleryReadHandler->execute($product);
    }
}