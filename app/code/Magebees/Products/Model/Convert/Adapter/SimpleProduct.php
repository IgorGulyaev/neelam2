<?php

namespace Magebees\Products\Model\Convert\Adapter;

use Magento\Framework\Filesystem;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
 
class SimpleProduct {	
	protected $_filesystem;
	protected $_objectManager;
    protected $_imageCache = array();
	private $logger;
	protected $_simple_error=array();
	protected $linkReData=array();
	protected $_request;

    public function __construct(
		\Magento\Catalog\Model\ProductFactory $ProductFactory,
		Filesystem $filesystem,
		\Magento\Catalog\Model\Product $Product,
		\Magento\Framework\App\Request\Http $request,
		\Psr\Log\LoggerInterface $logger
    ) {
		 $this->_objectManager = $ProductFactory;
		 $this->_filesystem = $filesystem;
		 $this->Product = $Product;
		 $this->_request = $request;
		 $this->logger = $logger;
    }
	
	public function addImage($imageName, $columnName, $imageArray = array()) {
		if($imageName=="") { return $imageArray; }
		
		if($columnName == "media_gallery") {
			$galleryData = explode('|', $imageName);
			foreach($galleryData as $gallery_img) {
				if($gallery_img != ""){
					if (array_key_exists($gallery_img, $imageArray)) {
						array_push($imageArray[$gallery_img],$columnName);
					} else {
						$imageArray[$gallery_img] = array($columnName);
					}
				}
			}
		} else {
			if (array_key_exists($imageName, $imageArray)) {
				array_push($imageArray[$imageName],$columnName);
			} else {
				$imageArray[$imageName] = array($columnName);
			}
		}
		return $imageArray;
	}
	
	public function SimpleProductData($ProcuctData,$ProductAttributeData,$ProductImageGallery,$ProductStockdata,$ProductSupperAttribute,$ProductCustomOption)
	{	
		$allowUpdateOnly = false;
		if($productIdupdate = $this->Product->loadByAttribute('sku', $ProcuctData['sku'])) {
			$SetProductData = $productIdupdate;
		} else {
			$SetProductData = $this->_objectManager->create();
		}
	
		if ($allowUpdateOnly == false) {
		$imagePath = "/import";
		if(empty($ProductAttributeData['url_key'])) {
			unset($ProductAttributeData['url_key']);
		}
		if(empty($ProductAttributeData['url_path'])) {
			unset($ProductAttributeData['url_path']);
		}
		$SetProductData->setSku($ProcuctData['sku']);
		$SetProductData->setStoreId($ProcuctData['store_id']);
		if(isset($ProcuctData['name'])) { $SetProductData->setName($ProcuctData['name']); }
		if(isset($ProcuctData['websites'])) { $SetProductData->setWebsiteIds($ProcuctData['websites']); }
		if(isset($ProcuctData['attribute_set'])) { $SetProductData->setAttributeSetId($ProcuctData['attribute_set']); }
		if(isset($ProcuctData['type'])) { $SetProductData->setTypeId($ProcuctData['type']); }
		if(isset($ProcuctData['category_ids'])) { 
			if($ProcuctData['category_ids'] == "remove") { 
				$SetProductData->setCategoryIds(array()); 
			} else {
				$SetProductData->setCategoryIds($ProcuctData['category_ids']);
			}
		}
		if(isset($ProcuctData['status'])) { $SetProductData->setStatus($ProcuctData['status']); }
		if(isset($ProcuctData['weight'])) { $SetProductData->setWeight($ProcuctData['weight']); }
		if(isset($ProcuctData['price'])) { $SetProductData->setPrice($ProcuctData['price']); }
		if(isset($ProcuctData['visibility'])) { $SetProductData->setVisibility($ProcuctData['visibility']); }
		if(isset($ProcuctData['tax_class_id'])) { $SetProductData->setTaxClassId($ProcuctData['tax_class_id']); }
		if(isset($ProcuctData['special_price'])) { $SetProductData->setSpecialPrice($ProcuctData['special_price']); }
		if(isset($ProcuctData['description'])) { $SetProductData->setDescription($ProcuctData['description']); }
		if(isset($ProcuctData['short_description'])) { $SetProductData->setShortDescription($ProcuctData['short_description']); }
		if(isset($ProductAttributeData['special_from_date'])) { $SetProductData->setSpecialFromDate($ProductAttributeData['special_from_date']); }
		if(isset($ProductAttributeData['news_from_date'])) { $SetProductData->setNewsFromDate($ProductAttributeData['news_from_date']); }
		if(isset($ProductAttributeData['special_to_date'])) { $SetProductData->setSpecialToDate($ProductAttributeData['special_to_date']); }
		if(isset($ProductAttributeData['news_to_date'])) { $SetProductData->setNewsToDate($ProductAttributeData['news_to_date']); }
		try{
			$SetProductData->addData($ProductAttributeData);
		} catch (\Exception $e) {
			array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
		}
		// if($params['reimport_images'] == "true") 
		if(trim($ProductImageGallery['gallery']) != "" && trim($ProductImageGallery['image']) != "" && trim($ProductImageGallery['small_image']) != "" && trim($ProductImageGallery['thumbnail']) != "")
		{
			
			//Get Object Manager Instance
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$storeManager = $objectManager->create('Magento\Store\Model\StoreManagerInterface');
			$websiteGroups = $storeManager->getWebsites();
			if(count($websiteGroups) <= 1){
				/*Remove Images From Product*/
				try{
					$SetProductData->save();		
					//$objectManager = \Magento\Framework\App\ObjectManager::getInstance();	
					//$product = $objectManager->create('Magento\Catalog\Model\Product')->load($SetProductData->getId());
					$productObj = $this->Product->load($SetProductData->getId());
					$productRepository = $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
					$existingMediaGalleryEntries = $productObj->getMediaGalleryEntries();
					//$mediaGallery = $productObj->getMediaGallery('images');
					foreach ($existingMediaGalleryEntries as $key => $entry) {
						unset($existingMediaGalleryEntries[$key]);
					}		
					$productObj->setMediaGalleryEntries($existingMediaGalleryEntries);
					$productRepository->save($productObj);
					$productObj->save();

				}catch (\Exception $e) {
					array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
				}
				// Add Image Code
				$_productImages = array(
					'media_gallery'     => (isset($ProductImageGallery['gallery'])) ? $ProductImageGallery['gallery'] : '',
					'image'       		=> (isset($ProductImageGallery['image'])) ? $ProductImageGallery['image'] : '',
					'small_image'       => (isset($ProductImageGallery['small_image'])) ? $ProductImageGallery['small_image'] : '',
					'thumbnail'       	=> (isset($ProductImageGallery['thumbnail'])) ? $ProductImageGallery['thumbnail'] : '',
					'swatch_image'    	=> (isset($ProductImageGallery['swatch_image'])) ? $ProductImageGallery['swatch_image'] : ''

				);
				$imageArray = array();
				foreach ($_productImages as $columnName => $imageName) {
					$imageArray = $this->addImage($imageName, $columnName, $imageArray);
				}
				foreach ($imageArray as $ImageFile => $imageColumns) {
					$possibleGalleryData = explode( '|', $ImageFile );
					foreach($possibleGalleryData as $_imageForImport) {
						try {
							$SetProductData->addImageToMediaGallery($imagePath . $_imageForImport, $imageColumns, false, false);
						} catch (\Magento\Framework\Exception\LocalizedException $e) {
							array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
						}
					}
				}
			}else{
				$_productImages = array(
					'media_gallery'     => (isset($ProductImageGallery['gallery'])) ? $ProductImageGallery['gallery'] : '',
					'image'       		=> (isset($ProductImageGallery['image'])) ? $ProductImageGallery['image'] : '',
					'small_image'       => (isset($ProductImageGallery['small_image'])) ? $ProductImageGallery['small_image'] : '',
					'thumbnail'       	=> (isset($ProductImageGallery['thumbnail'])) ? $ProductImageGallery['thumbnail'] : '',
					'swatch_image'    	=> (isset($ProductImageGallery['swatch_image'])) ? $ProductImageGallery['swatch_image'] : ''

				);
				$imageArray = array();
				foreach ($_productImages as $columnName => $imageName) {
					$imageArray = $this->addImage($imageName, $columnName, $imageArray);
				}
				foreach ($imageArray as $ImageFile => $imageColumns) {
					$possibleGalleryData = explode( '|', $ImageFile );
					foreach($possibleGalleryData as $_imageForImport) {
						try {
							$SetProductData->addImageToMediaGallery($imagePath . $_imageForImport, $imageColumns, false, false);
						} catch (\Magento\Framework\Exception\LocalizedException $e) {
							array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
						}
					}
				}
			}
		}
		try {
			$SetProductData->save();
			if(!isset($ProductStockdata['qty']) || $ProductStockdata['qty'] == "" || $ProductStockdata['qty'] == null){
				unset($ProductStockdata['qty']);
			}
			$SetProductData->setStockData($ProductStockdata);
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			if(!empty($ProductCustomOption)){
				$productRepositoryopt = $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
				$productOpt = $productRepositoryopt->getById($SetProductData->getId());
				if($productOpt->getOptions() != ""){
       				foreach ($productOpt->getOptions() as $opt){
                   		$opt->delete();
               		}
       				$productOpt->setHasOptions(0)->save();
  				}
				/* For get Version Number */
				$productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
				$version = $productMetadata->getVersion();
				if($version < '2.1.0'){
					
					//$SetProductData->unsetOptions();
					$SetProductData->save();
					//$SetProductData->setHasOptions(true);
					//$SetProductData->setCanSaveCustomOptions(true);
					//$SetProductData->getResource()->save($SetProductData);
					$productId = $SetProductData->getId();
						foreach ($ProductCustomOption as $arrayOption) {
							$SetProductData->setHasOptions(1);
							$SetProductData->getResource()->save($SetProductData);
							$option = $objectManager->create('\Magento\Catalog\Model\Product\Option')
									->setProductId($productId)
									->setStoreId($SetProductData->getStoreId())
									->addData($arrayOption);
							$option->save();
							$SetProductData->addOption($option);
						}
				}else{
						//$SetProductData->unsetOptions();
						$SetProductData->save();
						$SetProductData->setHasOptions(true);
						$SetProductData->setCanSaveCustomOptions(true);
						//$SetProductData->getResource()->save($SetProductData);
						$productId = $SetProductData->getId();
							foreach ($ProductCustomOption as $arrayOption) {
								//$SetProductData->setHasOptions(true);
								//$SetProductData->getResource()->save($SetProductData);
								$option = $objectManager->create('\Magento\Catalog\Model\Product\Option')
										->setProductId($productId)
										->setStoreId($SetProductData->getStoreId())
										->addData($arrayOption);
								//$option->save();
								$SetProductData->addOption($option);
								//$SetProductData->save();
							}
						$SetProductData->setHasOptions(true);
				}
			}
			
		 } catch (\Exception $e) {
			array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku'],'error_level'=>0));
		}
		
		if(isset($ProductSupperAttribute['cws_tier_price'])) { 
			if($ProductSupperAttribute['cws_tier_price']!=""){ $SetProductData->setTierPrice($ProductSupperAttribute['cws_tier_price']); }
		}
		
		try{
			$SetProductData->save(); 
		} catch (\Exception $e) {
			array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
		}
		
		if(isset($ProductSupperAttribute['related_product_sku'])){
			if($ProductSupperAttribute['related_product_sku']!=""){
				$this->AssignReCsUpProduct($ProductSupperAttribute['related_product_sku'],$ProcuctData['sku'],'related',$ProductAttributeData['related_product_position']); 
			}
		}

		if(isset($ProductSupperAttribute['upsell_product_sku'])){
			if($ProductSupperAttribute['upsell_product_sku']!=""){ 
				$this->AssignReCsUpProduct($ProductSupperAttribute['upsell_product_sku'],$ProcuctData['sku'],'upsell',$ProductAttributeData['upsell_product_position']); 
			}
		}

		if(isset($ProductSupperAttribute['crosssell_product_sku'])){
			if($ProductSupperAttribute['crosssell_product_sku']!=""){ 
				$this->AssignReCsUpProduct($ProductSupperAttribute['crosssell_product_sku'],$ProcuctData['sku'],'crosssell',$ProductAttributeData['crosssell_product_position']);
			}
		}
		
		if($this->linkReData) {
			try{
				$obj_product=$objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->get($ProcuctData['sku']);
				$obj_product->setProductLinks($this->linkReData)->save();
				$this->linkReData = array();
			} catch (\Exception $e) {
				$message = "Requested product doesn't exist in ".$ProcuctData['sku'];
				array_push($this->_simple_error,array('txt'=>$message,'product_sku'=>$ProcuctData['sku']));
			}
		}
	  }
	 return $this->_simple_error;
	}
	
	public function AssignReCsUpProduct($Childsku,$Parentsku,$type,$position)
	{
		$Childskus = explode('|',$Childsku);
		$_position = explode('|',$position);
		
		//echo "<pre>";
		//print_r($_position); die;
		//$i = 0;
		//foreach($Childskus as $linkdata){
			//echo $i;
			//$i++;
		//}
		//die;
		$data = array();
		$i = 1;
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		foreach($Childskus as $linkdata){
			if($linkdata!="") {
				
				/*if(!empty($_position)){
					$pos = $_position[$i];
				}else{		
					$pos = 1;
				}*/
				
				$productLink1 = $objectManager->create('Magento\Catalog\Api\Data\ProductLinkInterface')
					->setSku($Parentsku)
					->setLinkedProductSku($linkdata)
					//->setPosition($pos)
					->setPosition(1)
					->setLinkType($type);
				$this->linkReData[] = $productLink1;
				$i++;
			}
		}
	}
}