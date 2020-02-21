<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace EditionGuard\EditionGuard\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Downloadable\Api\Data\LinkInterface;
use Magento\Downloadable\Model\Product\Type;
use Magento\Downloadable\Api\Data\File\ContentUploaderInterface;
use Magento\Downloadable\Model\Product\TypeHandler\Link as LinkHandler;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class LinkRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LinkRepository extends \Magento\Downloadable\Model\LinkRepository
{

        /**
         * @var \Magento\Catalog\Api\ProductRepositoryInterface
         */
    protected $productRepository;

    /**
     * @var \Magento\Downloadable\Api\Data\LinkInterfaceFactory
     */
    protected $linkDataObjectFactory;

    /**
     * @var \Magento\Downloadable\Model\LinkFactory
     */
    protected $linkFactory;

    /**
     * @var \Magento\Downloadable\Model\Link\ContentValidator
     */
    protected $contentValidator;

    /**
     * @var Type
     */
    protected $downloadableType;

    /**
     * @var ContentUploaderInterface
     */
    protected $fileContentUploader;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var LinkHandler
     */
    private $linkTypeHandler;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Downloadable\Model\Product\Type $downloadableType
     * @param \Magento\Downloadable\Api\Data\LinkInterfaceFactory $linkDataObjectFactory
     * @param LinkFactory $linkFactory
     * @param Link\ContentValidator $contentValidator
     * @param EncoderInterface $jsonEncoder
     * @param ContentUploaderInterface $fileContentUploader
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Downloadable\Model\Product\Type $downloadableType,
        \Magento\Downloadable\Api\Data\LinkInterfaceFactory $linkDataObjectFactory,
        LinkFactory $linkFactory,
        \Magento\Downloadable\Model\Link\ContentValidator $contentValidator,
        EncoderInterface $jsonEncoder,
        ContentUploaderInterface $fileContentUploader
    ) {
        $this->productRepository = $productRepository;
        $this->downloadableType = $downloadableType;
        $this->linkDataObjectFactory = $linkDataObjectFactory;
        $this->linkFactory = $linkFactory;
        $this->contentValidator = $contentValidator;
        $this->jsonEncoder = $jsonEncoder;
        $this->fileContentUploader = $fileContentUploader;
    }
    
    /**
     * Build a link data object
     *
     * @param \Magento\Downloadable\Model\Link $resourceData
     * @return \Magento\Downloadable\Model\Link
     */
    protected function buildLink($resourceData)
    {
        /** @var \Magento\Downloadable\Model\Link $link */
        $link = $this->linkDataObjectFactory->create();
        $this->setBasicFields($resourceData, $link);
        $link->setPrice($resourceData->getPrice());
        $link->setNumberOfDownloads($resourceData->getNumberOfDownloads());
        $link->setIsShareable($resourceData->getIsShareable());
        $link->setLinkType($resourceData->getLinkType());
        $link->setLinkFile($resourceData->getLinkFile());
        $link->setLinkUrl($resourceData->getLinkUrl());

        return $link;
    }

    
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function save($sku, LinkInterface $link, $isGlobalScopeContent = true)
    {
        $product = $this->productRepository->get($sku, true);
            if ($product->getTypeId() !== \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
                throw new InputException(__('Provided product must be type \'downloadable\'.'));
            }
            $validateLinkContent = !($link->getLinkType() === 'file' && $link->getLinkFile());
            $validateSampleContent = !($link->getSampleType() === 'file' && $link->getSampleFile());
            
            if($link->getLinkType() !== 'ebook') {
	        if (!$this->contentValidator->isValid($link, $validateLinkContent, $validateSampleContent)) {
	            throw new InputException(__('Provided link information is invalid.'));
	        }
	    }

            if (!in_array($link->getLinkType(), ['url', 'file','ebook'], true)) {
                throw new InputException(__('Invalid link type.'));
            }
            $title = $link->getTitle();
            if (empty($title)) {
                throw new InputException(__('Link title cannot be empty.'));
            }
            return $this->saveLink($product, $link, $isGlobalScopeContent);
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param LinkInterface $link
     * @param bool $isGlobalScopeContent
     * @return int
     */
    protected function saveLink(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        LinkInterface $link,
        $isGlobalScopeContent
    ) {
        $linkData = [
            'link_id' => (int)$link->getid(),
            'is_delete' => 0,
            'type' => $link->getLinkType(),
            'sort_order' => $link->getSortOrder(),
            'title' => $link->getTitle(),
            'price' => $link->getPrice(),
            'number_of_downloads' => $link->getNumberOfDownloads(),
            'is_shareable' => $link->getIsShareable(),
        ];

        if ($link->getLinkType() == 'file' && $link->getLinkFile() === null) {
            $linkData['file'] = $this->jsonEncoder->encode(
                [
                    $this->fileContentUploader->upload($link->getLinkFileContent(), 'link_file'),
                ]
            );
            $linkData['use_editionguard'] = 0;
        } elseif ($link->getLinkType() === 'url') {
            $linkData['link_url'] = $link->getLinkUrl();
            $linkData['use_editionguard'] = 0;
        } elseif ($link->getLinkType() === 'ebook') {
            $linkData['editionguard_resource'] = $link->getEditionguardResource();
            $linkData['use_editionguard'] = 1;//$link->getEditionguardResource();
        } else {
            //existing link file
            $linkData['file'] = $this->jsonEncoder->encode(
                [
                    [
                        'file' => $link->getLinkFile(),
                        'status' => 'old',
                    ]
                ]
            );
            $linkData['use_editionguard'] = 0;
        }

        if ($link->getSampleType() == 'file') {
            $linkData['sample']['type'] = 'file';
            if ($link->getSampleFile() === null) {
                $fileData = [
                    $this->fileContentUploader->upload($link->getSampleFileContent(), 'link_sample_file'),
                ];
            } else {
                $fileData = [
                    [
                        'file' => $link->getSampleFile(),
                        'status' => 'old',
                    ]
                ];
            }
            $linkData['sample']['file'] = $this->jsonEncoder->encode($fileData);
        } elseif ($link->getSampleType() == 'url') {
            $linkData['sample']['type'] = 'url';
            $linkData['sample']['url'] = $link->getSampleUrl();
        }

        $downloadableData = ['link' => [$linkData]];
        if ($isGlobalScopeContent) {
            $product->setStoreId(0);
        }
                
        $this->getLinkTypeHandler()->save($product, $downloadableData);
        return $product->getLastAddedLinkId();
    }
    /**
     * Get LinkTypeHandler  instance
     *
     * @deprecated 100.1.0 MAGETWO-52273
     * @return LinkHandler
     */
    private function getLinkTypeHandler()
    {
        if (!$this->linkTypeHandler) {
            $this->linkTypeHandler = ObjectManager::getInstance()->get(LinkHandler::class);
        }

        return $this->linkTypeHandler;
    }
}
