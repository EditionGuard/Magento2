# EditionGuard eBook Sales with DRM extension for Magento 2.1+ Stores

## Purpose

The purpose of this extension is to achieve integration between EditionGuard and a Magento 2.x e-commerce store. By doing so, the store will have the ability to sell eBooks protected by the industry standard Adobe DRM scheme provided by EditionGuard as a Saas solution.

## Usage

### Installation and Configuration

* Login to your Magento site admin
* Install extension through the Magento Marketplace
* Activate the extension 
* Go to *Stores > Configuration > EditionGuard*
* Enter your EditionGuard account e-mail, shared secret and distributor id
	* *These credentials found in your account dashboard page at https://app.editionguard.com/account/dashboard*
* Save the configuration

### Product Mapping

In order to start DRM protected eBook sales, you must now start mapping Magento 2 products to eBooks that were uploaded on your EditionGuard account. To do so, follow these steps.

* Login to your Magento site admin
* Go to Product > Catalog and find the product you wish to map
* Edit the product then expand the *Downloadable Information* section
* Check the *Is this downloadable Product?* checkbox
* Under the *Links* section, click *Add Link*
* From the *File* dropdown, pick *eBook*
* A new dropdown will appear listing eBooks on your EditionGuard account
* Pick the eBook you wish to map to this product from the new dropdown
* Click *Save* at the top right corner of your screen to save the product

### Sales

After the *Installation and Configuration* and *Product Mapping* steps are followed, whenever the product is sold, the user will be able to download their eBook from the store frontend under *My Account > My Downloadable Products*
