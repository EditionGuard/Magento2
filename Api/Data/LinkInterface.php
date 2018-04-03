<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace EditionGuard\EditionGuard\Api\Data;

/**
 * @codeCoverageIgnore
 * @api
 * @since 100.0.2
 */
interface LinkInterface extends \Magento\Downloadable\Api\Data\LinkInterface
{

    /**
     * @return string
     */
    
    public function getEditionguardResource();

    /**
     * Set EditionGuard Resource
     *
     * @param string Editionguard Resource
     * @return $this
     */
    public function setEditionguardResource($editionguardResource);
}
