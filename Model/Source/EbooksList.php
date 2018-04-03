<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace EditionGuard\EditionGuard\Model\Source;

/**
 * TypeUpload source class
 */
class EbooksList implements \Magento\Framework\Data\OptionSourceInterface
{

    protected $helper;

    public function __construct(
        \EditionGuard\EditionGuard\Helper\Data $dataHelper
    ) {
        $this->helper = $dataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {

        $listings = [
            ['value' => '', 'label' => ' --Select ebook --']
        ];

        $editionGuardListings = $this->getEditionGuardListing();

        if (!$editionGuardListings) {
            return $listings;
        }

        foreach ($editionGuardListings as $key => $listing) {
            $listings[] = ['value' => $listing->resource, 'label' => $listing->title . ' (' . $listing->resource . ')'];
        }

        return $listings;
    }

    private function getEditionGuardListing()
    {

        $editionguard_listing = $this->helper->listResource($this->helper->getEditionguardDistributorId());
        return $editionguard_listing;
    }
}
