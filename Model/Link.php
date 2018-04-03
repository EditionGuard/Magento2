<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace EditionGuard\EditionGuard\Model;

use EditionGuard\EditionGuard\Api\Data\LinkInterface;

class Link extends \Magento\Downloadable\Model\Link implements LinkInterface
{

    public function setEditionguardResource($resource_id)
    {
        return $this->setData('editionguard_resource', $resource_id);
    }
    
    public function getEditionguardResource()
    {
        return $this->getData('editionguard_resource');
    }
}
