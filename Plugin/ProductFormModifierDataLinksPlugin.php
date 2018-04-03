<?php

namespace EditionGuard\EditionGuard\Plugin;

use Magento\Downloadable\Model\Product\Type;
use Magento\Catalog\Model\Locator\LocatorInterface;

class ProductFormModifierDataLinksPlugin
{

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @param LocatorInterface $locator
     */
    public function __construct(
        LocatorInterface $locator
    ) {
        $this->locator = $locator;
    }


    public function afterGetLinksData(\Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\Data\Links $subject, $linksData)
    {
        if ($this->locator->getProduct()->getTypeId() !== Type::TYPE_DOWNLOADABLE) {
            return $linksData;
        }

        $links = $this->locator->getProduct()->getTypeInstance()->getLinks($this->locator->getProduct());

        foreach ($links as $link) {
            foreach ($linksData as $key => $linkData) {
                if($linkData['link_id'] !== $link->getId()) {
                    continue;
                }

                $linksData[$key]['use_editionguard']  = $link->getUseEditionguard();
                $linksData[$key]['editionguard_resource'] = $link->getEditionguardResource();
            }

        }

        return $linksData;
    }
}