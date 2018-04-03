<?php

namespace EditionGuard\EditionGuard\Plugin;

use Magento\Ui\Component\Form;
use EditionGuard\EditionGuard\Model\Source\TypeEbookUpload;
use EditionGuard\EditionGuard\Model\Source\EbooksList;

class ProductFormModifierLinksPlugin
{

    /**
     * @var TypeEbookUpload
     */
    protected $typeEbookUpload;

    /**
     * @var EbooksList (Remote Ebooks LIst)
     */
    protected $eBooksList;

    /**
     * @param TypeEbookUpload $typeEbookUpload
     * @param EbooksList $eBooksList
     */
    public function __construct(
        TypeEbookUpload $typeEbookUpload,
        EbooksList $eBooksList
    ) {
        $this->typeEbookUpload = $typeEbookUpload;
        $this->eBooksLists = $eBooksList;
    }

    public function afterModifyMeta(\Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\Links $subject, $result)
    {
        $fileTypeField['arguments']['data']['config'] = [
            'formElement' => Form\Element\Select::NAME,
            'componentType' => Form\Field::NAME,
            'component' => 'EditionGuard_EditionGuard/js/components/upload-type-handler',
            'dataType' => Form\Element\DataType\Text::NAME,
            'dataScope' => 'type',
            'options' => $this->typeEbookUpload->toOptionArray(),
            'typeFile' => 'links_file',
            'typeUrl' => 'link_url',
            'typeEbook' => 'editionguard_resource',
        ];
        $eBookLinkUrl['arguments']['data']['config'] = [
            'formElement' => Form\Element\Select::NAME,
            'componentType' => Form\Field::NAME,
            'dataType' => Form\Element\DataType\Text::NAME,
            'dataScope' => 'editionguard_resource',
            'options' => $this->eBooksLists->toOptionArray(),
            'validation' => [
                'required-entry' => true,
            ],
        ];
        $result['downloadable']['children']['container_links']['children']['link']['children']['record']['children']['container_file']['children']['type'] = $fileTypeField;
        $result['downloadable']['children']['container_links']['children']['link']['children']['record']['children']['container_file']['children']['editionguard_resource'] = $eBookLinkUrl;

        return $result;
    }
}