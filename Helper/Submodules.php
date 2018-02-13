<?php

namespace Bunting\Core\Helper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Registry;

class Submodules extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Magento\Framework\Registry  */
    private $registry;

    /**
     * @param Context $context
     * @param Registry $registry
     */
    public function __construct(Context $context, Registry $registry)
    {
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * Secondary \Bunting\{Namespace} modules will add indexes to this global registry to hook their features in to the \Bunting\Core module
     *
     * @return array|mixed
     */
    public function getRegisteredBuntingSubmodules() {
        return is_array($this->registry->registry('bunting_submodules')) ? $this->registry->registry('bunting_submodules') : array();
    }

    /**
     * @param string id
     * @param object $submodule
     */
    public function addSubmodule($id, $submodule) {
        $buntingModules = $this->getRegisteredBuntingSubmodules();
        $buntingModules[$id] = $submodule;

        if (!is_null($this->registry->registry('bunting_submodules'))) {
            $this->registry->unregister('bunting_submodules');
        }

        $this->registry->register('bunting_submodules', $buntingModules);
    }
}