<?php
/**
 * EditionGuard
 *
 * This source file is proprietary property of EditionGuard. Any reuse or
 * distribution of any part of this file without prior consent is prohibited.
 *
 * @category    EditionGuard
 * @package     Editionguard_Editionguard
 * @copyright   Copyright (c) 2012 EditionGuard. All rights Reserved.
 */

namespace EditionGuard\EditionGuard\Block\Customer\Products;

use Magento\Downloadable\Model\Link\Purchased\Item;

class ListProducts extends \Magento\Downloadable\Block\Customer\Products\ListProducts
{
    /**
     * Return number of left downloads or unlimited
     *
     * @return string
     */
    
     /**
      * Enter description here...
      *
      * @return $this
      */
    protected $helper;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Downloadable\Model\ResourceModel\Link\Purchased\CollectionFactory $linksFactory,
        \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory $itemsFactory,
        \EditionGuard\EditionGuard\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $currentCustomer, $linksFactory, $itemsFactory, $data);
    }
        
    public function getRemainingDownloads($item)
    {
        if ($this->helper->getUseEditionguard($item)) {
            return __('Unlimited');
        } else {
            return parent::getRemainingDownloads($item);
        }
    }

    /**
     * Return url to download link
     *
     * @param Mage_Downloadable_Model_Link_Purchased_Item $item
     * @return string
     */
    public function getDownloadUrl($item)
    {
        if ($this->helper->getUseEditionguard($item)) {
            return $this->helper->getDownloadUrl($item->getOrderItemId(), $item->getEditionguardResource());
        } else {
            return parent::getDownloadUrl($item);
        }
    }
}
