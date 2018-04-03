<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace EditionGuard\EditionGuard\Model\Source;

/**
 * TypeUpload source class
 */
class TypeEbookUpload implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'file', 'label' => __('Upload File')],
            ['value' => 'url', 'label' => __('URL')],
            ['value' => 'ebook', 'label' => __('eBook')],
        ];
    }
}
