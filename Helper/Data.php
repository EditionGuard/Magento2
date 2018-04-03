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
namespace EditionGuard\EditionGuard\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const API_PACKAGE_URL                       = 'https://app.editionguard.com/api/package';
    const API_SET_STATUS_URL                    = 'https://app.editionguard.com/api/set_status';
    const API_DELETE_URL                        = 'https://app.editionguard.com/api/delete';
    const API_LINK_URL                          = 'http://acs4.editionguard.com/fulfillment/URLLink.acsm';
    const API_EBOOK_LISTING                     = 'https://app.editionguard.com/api/ebook_list'; //API for getting uploaded files

    const LINK_EDITIONGUARD_YES                 = 1;
    const LINK_EDITIONGUARD_NO                  = 0;
    const LINK_EDITIONGUARD_CONFIG              = 2;
    const XML_PATH_CONFIG_USE_EDITIONGUARD      = 'catalog/downloadable/editionguard';
    const XML_PATH_CONFIG_EDITIONGUARD_EMAIL    = 'editionguard/general/email';
    const XML_PATH_CONFIG_EDITIONGUARD_DISTRIBUTERID = 'editionguard/general/distributerid';
    const XML_PATH_CONFIG_EDITIONGUARD_SECRET   = 'editionguard/general/sharedsecret';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $salesOrderItemFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $zendClientFactory;

    /**
     * @var \Magento\Framework\Simplexml\ElementFactory
     */
    protected $elementFactory;
    
    protected $messageManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\ItemFactory $salesOrderItemFactory,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Framework\HTTP\ZendClientFactory $zendClientFactory,
        \Magento\Framework\Simplexml\ElementFactory $elementFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->zendClientFactory = $zendClientFactory;
        $this->elementFactory = $elementFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->salesOrderItemFactory = $salesOrderItemFactory;
        $this->salesOrderFactory = $salesOrderFactory;
         $this->messageManager = $messageManager;
    }
    /**
     * Given a URL and parameters, makes a request to the editionguard API using the
     * currently configured credentials.
     *
     * @param string $request_url
     * @param array $params
     *
     * @return \Magento\Framework\Simplexml\Element object containing the result
     * @throws \Exception on failure
     */
    protected function sendEditionguardApiRequest($request_url, array $params, $file = null)
    {
        $secret = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_EDITIONGUARD_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $email = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_EDITIONGUARD_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $nonce = rand(1000000, 999999999);
        $hash = hash_hmac("sha1", $nonce.$email, base64_decode($secret));

        $httpClient = $this->zendClientFactory->create();
        $params['email'] = $email;
        $params['nonce'] = $nonce;
        $params['hash'] = $hash;

        $httpClient->setUri($request_url)
            ->setParameterPost($params)
            ->setConfig(['timeout' => 3600]);

        if ($file && is_array($file)) {
            $httpClient->setFileUpload(
                $file['filename'],
                $file['formname'],
                $file['data'],
                $file['type']
            );
        }

        $response = $httpClient->request('POST');

        // TODO: Handle non 200 responses
        // - Follow 3**
        // - Give errors on 4** and 5**

        try {
            $body = $response->getBody();
        } catch (\Zend\Http\Exception\RuntimeException $e) {
            // HACK: EditionGuard currently sends the response raw, even though
            // the header indicates that it is chunked. Just grab the raw body
            // and use it.
            $body = $response->getRawBody();
        }
    
        try {
            $xml = $this->elementFactory->create("<root>".$body."</root>");
        } catch (\Exception $e) {
            // Not valid XML. Treat like a raw error
            throw new \Exception("Error: \"$body\" while uploading file to EditionGuard");
        }

        if (isset($xml->error)) {
            $error_data = $xml->error->getAttribute('data');
            if (preg_match('~([a-zA-Z0-9_]*) (.*)~', $error_data, $matches)) {
                $error_type = $matches[1];
                $error_url = $matches[2];
                // TODO: Map any documented error codes to more meaningful messages
                // TODO: Use a custom exception type
                throw new \Exception("Error: $error_type while uploading file to EditionGuard", $error_type, $error_url);
                $this->logger->debug("EditionGuard Error with returned XML: ".$xml);
            } else {
                // TODO: Use a custom exception type
                throw new \Exception("Unknown error while uploading file to EditionGuard");
                $this->logger->debug("EditionGuard Unknown error with XML: ".$xml);
            }
        }

        return $xml;
    }

    /**
     * Given a URL and parameters, makes a request to the editionguard API using the
     * currently configured credentials.
     *
     * @param string $request_url
     * @param array $params
     *
     * @return \Magento\Framework\Simplexml\Element jSon object containing the result
     * @throws \Exception on failure
     */
    protected function sendEditionguardApiRequestJson($request_url, array $params, $file = null)
    {
        $secret = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_EDITIONGUARD_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $email = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_EDITIONGUARD_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        if (empty($secret) || empty($email)) {
            $this->messageManager->addNotice(__("Your EditionGuard subscription is inactive. Click <a href='https://app.editionguard.com/account/login' target='_blank'>here</a> to fix"));
            return [];
        }
        
        $nonce = rand(1000000, 999999999);
        $hash = hash_hmac("sha1", $nonce.$email, base64_decode($secret));

        $httpClient = $this->zendClientFactory->create();
        $params['email'] = $email;
        $params['nonce'] = $nonce;
        $params['hash'] = $hash;

        $httpClient->setUri($request_url)
            ->setParameterPost($params)
            ->setConfig(['timeout' => 3600]);

        if ($file && is_array($file)) {
            $httpClient->setFileUpload(
                $file['filename'],
                $file['formname'],
                $file['data'],
                $file['type']
            );
        }
                
        try {
            $response = $httpClient->request('POST');
            $body = $response->getBody();
        } catch (\Zend\Http\Client\Exception $e) {
            // Wrong config or remote host unreachable ---> show error on Magento Admin and return null;
         
            $body = $response->getRawBody();
        }

        $bodyDecoded = json_decode($body);
        return $bodyDecoded;
    }


    /**
     * Gets Editionguard Distributor ID
     */
    public function getEditionguardDistributorId()
    {
        $distributorId = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_EDITIONGUARD_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $distributorId;
    }
    /**
     * Collects an Editionguard resources already uploaded files
     */
    public function listResource($resourceid)
    {
        $decoded_reponse = $this->sendEditionguardApiRequestJson(self::API_EBOOK_LISTING, [
            'resource_id'=>$resourceid,
        ]);
        
                        
        if (!is_array($decoded_reponse) || empty($decoded_reponse)) {
            return [];
        }

        return $decoded_reponse;
    }


    /**
     * Sends a file to Editionguard for DRM handling
     *
     * Use a null resourceid to upload a new file
     *
     * @return string - Unique ID for the file in Editionguard
     */
    public function sendToEditionguard($resourceid, $title, $filename, $filedata, $filetype = null)
    {
        $params = [
            'title'=>$title,
            'author'=>'',
            'publisher'=>'',
        ];
        if ($resourceid) {
            $params['resource_id'] = $resourceid;
        }
        $xml = $this->sendEditionguardApiRequest(
            self::API_PACKAGE_URL,
            $params,
            [
                'filename'=>$filename,
                'formname'=>'file',
                'data'=>$filedata,
                'type'=>$filetype,
            ]
        );

        if (!isset($xml->resourceItemInfo) && !isset($xml->response)) {
            // Unknown response type. Assume it's a raw error.
            $this->logger->debug("EditionGuard Error with returned XML: ".print_r($xml, true));
            throw new \Exception("Error: \"".$xml->error->getAttribute('data')."\" while uploading file to EditionGuard");
        }

        return [
            'resource'=>$xml->resourceItemInfo->resource,
            'src'=>$xml->resourceItemInfo->src,
        ];
    }

    /**
     * Changes the status of an Editionguard resource
     */
    public function setResourceActive($resourceid, $active = true)
    {
        $xml = $this->sendEditionguardApiRequest(self::API_SET_STATUS_URL, [
            'resource_id'=>$resourceid,
            'status'=>$active ? 'active' : 'inactive',
        ]);

        // Setting to inactive is currently returning deleted. Setting to active should
        // return a distributionrights object
        if (!isset($xml->response) || (!isset($xml->response->distributionRights) && !isset($xml->response->deleted))) {
            // Unknown response type. Assume it's a raw error.
            throw new \Exception("Error: \"{$xml->innerXml()}\" while uploading file to EditionGuard");
        }

        return true;
    }

    /**
     * Deletes an Editionguard resource
     */
    public function deleteResource($resourceid)
    {
        $xml = $this->sendEditionguardApiRequest(self::API_DELETE_URL, [
            'resource_id'=>$resourceid,
        ]);

        // NOTE: We get a blank response if the file had already been deleted. There's no sense
        // dying over this, so we're going to skip this verification process, even though it would
        // normally be a good thing.
//        if (!isset($xml->response) || !isset($xml->response->deleted) || !$xml->response->deleted)
//        {
//            // Unknown response type. Assume it's a raw error.
//            throw new Exception("Error: \"{$xml->innerXml()}\" while uploading file to EditionGuard");
//        }

        return true;
    }

    /**
     * Builds a link to a file managed by Editionguard
     *
     * @return string - Unique download URL for this purchase
     */
    public function getDownloadUrl($order_item_id, $resource)
    {
        $dateval=time();
        $sharedSecret = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_EDITIONGUARD_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $order_item = $this->salesOrderItemFactory->create()->load($order_item_id);
        $order = $this->salesOrderFactory->create()->load($order_item->getOrderId());
        $quantity = $order_item->getQtyOrdered();

        $transactionId = $order->getIncrementId();
        $resourceId = $resource;
        $linkURL = self::API_LINK_URL;
        $orderSource = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_EDITIONGUARD_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        // If only one of the item was sold, simply generate direct download link
        if ($quantity == 1) {
            // Create download URL
            $URL = "action=enterorder&ordersource=".urlencode($orderSource)."&orderid=".urlencode($order_item_id)."&resid=".urlencode("$resourceId")."&dateval=".urlencode($dateval)."&gblver=4";

            // Digitaly sign the request
            $URL = $linkURL."?".$URL."&auth=".hash_hmac("sha1", $URL, base64_decode($sharedSecret));

            return $URL;
        } // If more than one of the item has been sold, direct to download link listing page
        else {
            $hash = hash_hmac("sha1", $transactionId . $quantity . $orderSource, base64_decode($sharedSecret));
            return "http://www.editionguard.com/api/mage_links/".urlencode($orderSource)."/".urlencode($transactionId)."/".urlencode($resourceId)."/".urlencode($quantity)."/$hash";
        }
    }

    /**
     * Check is link DRM protected by EditionGuard or not
     *
     * @param \Magento\Downloadable\Model\Link | \Magento\Downloadable\Model\Link\Purchased\Item $link
     * @return bool
     */
    public function getUseEditionguard($link)
    {
        $editionguard = false;
        switch ($link->getUseEditionguard()) {
            case self::LINK_EDITIONGUARD_YES:
            case self::LINK_EDITIONGUARD_NO:
                $editionguard = (bool) $link->getUseEditionguard();
                break;
            case self::LINK_EDITIONGUARD_CONFIG:
                $editionguard = (bool) $this->scopeConfig->getValue(self::XML_PATH_CONFIG_USE_EDITIONGUARD, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return $editionguard;
    }
}
