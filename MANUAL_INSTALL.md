# Manual Extension Installation on Magento 2.1+ stores

* Create folder named 'editionguard' in the Magento root folder
* Unzip extension inside the editionguard folder
* Go back to the Magento root folder
* Edit composer.json in the Magento root folder
* Add the "path" repository and require entry as shown below

```
{
	"repositories": [
		{
			"type": "path",
			"url": "editionguard"
		},
		...
	]
	"require": {
		"editionguard/module-editionguard": "*",
		...
	}
}	
```

* Run `composer update`
* Run `bin/magento cache:disable` or disable caches in admin
* Run `bin/magento setup:upgrade`
* Run `bin/magento setup:di:compile`
* Continue on admin
