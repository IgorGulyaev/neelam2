<?php
namespace Magebees\Products\Model\Convert\Adapter;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
 
class BundleProduct{

	protected $_filesystem;		
	protected $_objectManager;
	protected $_simple_error=array();
	protected $linkReData=array();
	
    public function __construct(
		\Magento\Catalog\Model\ProductFactory $ProductFactory,
		Filesystem $filesystem,
		\Magento\Framework\Registry $registry,
		\Magento\Catalog\Model\Product $Product
    ) {
		 $this->_objectManager = $ProductFactory;
		 $this->_filesystem = $filesystem;
		 $this->registry = $registry;
		 $this->Product = $Product;
    }
	
	public function addImage($imageName, $columnName, $imageArray = array()) {
		if($imageName=="") { return $imageArray; }
		
		if($columnName == "media_gallery") {
			$galleryData = explode('|', $imageName);
			foreach( $galleryData as $gallery_img ) {
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
	
	public function BundleProductData($ProcuctData,$ProductAttributeData,$ProductImageGallery,$ProductStockdata,$ProductSupperAttribute,$ProductCustomOption){
		
	$allowUpdateOnly = false;
	if($productIdupdate = $this->Product->loadByAttribute('sku', $ProcuctData['sku'])) {
		$SetProductData = $productIdupdate;
		$new = false;
	} else {
		$SetProductData = $this->_objectManager->create();
		$new = true;
	}
	
	if ($allowUpdateOnly == false) {
		
		$imagePath = "/import";
		if($this->Product->loadByAttribute('sku', $ProcuctData['sku'])) {
			$SetProductData = $this->Product->loadByAttribute('sku', $ProcuctData['sku']);
		} else {
			$SetProductData = $this->_objectManager->create();
		}
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
		if(isset($ProcuctData['category_ids'])) { $SetProductData->setCategoryIds($ProcuctData['category_ids']); }
		if(isset($ProcuctData['status'])) { $SetProductData->setStatus($ProcuctData['status']); }
		if(isset($ProcuctData['weight'])) { $SetProductData->setWeight($ProcuctData['weight']); }
		if(isset($ProcuctData['price'])) { $SetProductData->setPrice($ProcuctData['price']); }
		if(isset($ProcuctData['visibility'])) { $SetProductData->setVisibility($ProcuctData['visibility']); }
		if(isset($ProcuctData['tax_class_id'])) { $SetProductData->setTaxClassId($ProcuctData['tax_class_id']); }
		if(isset($ProcuctData['special_price'])) { $SetProductData->setSpecialPrice($ProcuctData['special_price']); }
		if(isset($ProcuctData['description'])) { $SetProductData->setDescription($ProcuctData['description']); }
		if(isset($ProcuctData['short_description'])) { $SetProductData->setShortDescription($ProcuctData['short_description']); }
		
		// Sets the Start Date
		if(isset($ProductAttributeData['special_from_date'])) { $SetProductData->setSpecialFromDate($ProductAttributeData['special_from_date']); }
		if(isset($ProductAttributeData['news_from_date'])) { $SetProductData->setNewsFromDate($ProductAttributeData['news_from_date']); }
		
		// Sets the End Date
		if(isset($ProductAttributeData['special_to_date'])) { $SetProductData->setSpecialToDate($ProductAttributeData['special_to_date']); }
		if(isset($ProductAttributeData['news_to_date'])) { $SetProductData->setNewsToDate($ProductAttributeData['news_to_date']); }
		
		$SetProductData->addData($ProductAttributeData);
		
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
			
			if(!isset($ProductStockdata['qty']) || $ProductStockdata['qty'] == "" || $ProductStockdata['qty'] == null){
				unset($ProductStockdata['qty']);
			}
			$SetProductData->setStockData($ProductStockdata);
			/* This code is for Add Custom Options */
			if(!empty($ProductCustomOption) && $ProductAttributeData['price_type'] != "dynamic"){
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$SetProductData->unsetOptions();
				$SetProductData->save();
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
			}
			/* End of Add Custom Options code */
			
			$SetProductData->save();
		} catch (\Exception $e) {
			array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
		}
		
		if (isset( $ProductSupperAttribute['bundle_product_options'] ) && $ProductSupperAttribute['bundle_product_options'] != "" ) {
			try {
				if(isset($ProductAttributeData['price_type'])){
					$SetProductData->setPriceType($this->getPriceType($ProductAttributeData['price_type']));
				}else{
					$SetProductData->setPriceType($this->getPriceType(1));
				}
				
				if(isset($ProductAttributeData['price_view'])){
					$SetProductData->setPriceView($this->getPriceView($ProductAttributeData['price_view']));
				}else{
					$SetProductData->setPriceView($this->getPriceView(0));
				}
				if(isset($ProductAttributeData['shipment_type'])){
					$SetProductData->setShipmentType($this->getShipmentType($ProductAttributeData['shipment_type']));
				}else{
					$SetProductData->setShipmentType($this->getShipmentType(1));
				}
				if(isset($ProductAttributeData['weight']) && $ProductAttributeData['weight']!=''){$SetProductData->setWeightType(1);}
			} catch (\Exception $e) {
				array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
			}
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$productRepository = $objectManager->create('\Magento\Catalog\Api\ProductRepositoryInterface');
			$product = $productRepository->get($ProcuctData['sku'], true);
			
			$option_str = $ProductAttributeData['bundle_product_options'];
			$single_bundle_option=explode("|",$option_str);
			$optionRawData = array();
			$selectionRawData = array();
			for($z=0;$z<count($single_bundle_option);$z++)
			{
				$single_bundle_option_data=explode(":",$single_bundle_option[$z]);			
				$single_bundle_option_title_value=explode(",",$single_bundle_option_data[0]);
					$optionRawData[$z] = array(
					  'title' => $single_bundle_option_title_value[0],
					  'required' => $single_bundle_option_title_value[2],
					  'type' => $single_bundle_option_title_value[1],
					  'position' => $single_bundle_option_title_value[3],
					  'delete' => '',
					);
					
				$single_bundle_option_selection_value=explode("!",$single_bundle_option_data[1]);
				foreach($single_bundle_option_selection_value as $singleBundleOptionData){	
					$d = explode(',',$singleBundleOptionData);
					$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
					$product_id = $objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($d[0]);
					if($product_id){
						$ptype = "";
						$pprice = "";
						if(isset($d[5])){$ptype = $d[5];}else{$ptype = '';}
						if(isset($d[4])){$pprice = $d[4];}else{$pprice = '';}
						$selectionRawData[$z][] = array(
						  'sku' => $d[0],
						  'qty' => $d[1],
						  'position' => $d[3],
						  'is_default' => '',
						  'price_type' => $ptype,
						  'price' => $pprice,
						  'can_change_quantity' => $d[2],
						  'delete' => ''
						);	
					}else{
						$message='Product does not exist. SKU :'.$d[0];
						array_push($this->_simple_error,array('txt'=>$message,'product_sku'=>$ProcuctData['sku']));
						return $this->_simple_error;
					}
				}
				$optionRawData[$z]['product_links'] = $selectionRawData[$z];
			}
			$options = array();
			
			foreach ($optionRawData as $key => $optionData) {
				if (!(bool)$optionData['delete']) {
					$option = $objectManager->create('Magento\Bundle\Api\Data\OptionInterfaceFactory')->create(['data' => $optionData]);
					$option->setSku($product->getSku());
					$option->setOptionId(null);
						$links = array();
						foreach ($optionData['product_links'] as $linkData) {
							if (!(bool)$linkData['delete']) 
							{
								$link = $objectManager->create('Magento\Bundle\Api\Data\LinkInterfaceFactory')->create(['data' => $linkData]);
								$linkProduct = $productRepository->get($linkData['sku']);
								$link->setSku($linkProduct->getSku());
								$links[] = $link;
							}
						}
						$option->setProductLinks($links);
						$options[] = $option;
				}
			}
			
			try {
			$extension = $product->getExtensionAttributes();
			$extension->setBundleProductOptions($options);
			$productRepository->save($product, true);
			} catch (\Exception $e) {
				array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
			}
		}
		
		try {
			$SetProductData->save();
		} catch (\Exception $e) {
			array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
		}
		
		if(isset($ProductSupperAttribute['cws_tier_price'])) { 
			if($ProductSupperAttribute['cws_tier_price']!=""){ $SetProductData->setTierPrice($ProductSupperAttribute['cws_tier_price']); }
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
		$data = array();
		$i = 0;
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		foreach($Childskus as $linkdata){
			if($linkdata!="") {
				
				/*if(!empty($position)){
					$pos = $_position[$i];
				}else{		
					$pos = 1;
				}*/
				
				$productLink1 = $objectManager->create('Magento\Catalog\Api\Data\ProductLinkInterface')
					->setSku($Parentsku)
					->setLinkedProductSku($linkdata)
					->setPosition(1)
					->setLinkType($type);
				//$linkReData[] = $productLink1;
				$this->linkReData[] = $productLink1;
				$i++;
			}
		}
	}
	public function getPriceView($txt){
		if(strtolower($txt) == 'price range'){
			return '0';
		}else{
			return '1';
		}
	} 

	public function getPriceType($txt){
		if(strtolower($txt) == 'fixed'){
			return '1';
		}else{
			return '0';
		}
	} 
	public function getShipmentType($txt){
		if(strtolower($txt) == 'separately'){
			return '1';
		}else{
			return '0';
		}
	} 
}