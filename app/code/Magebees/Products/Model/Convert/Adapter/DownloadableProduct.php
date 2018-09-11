<?php

namespace Magebees\Products\Model\Convert\Adapter;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\App\ResourceConnection;

class DownloadableProduct{

	protected $_filesystem;		
	protected $_objectManager;
	protected $linkReData=array();
	protected $_simple_error=array();
	
    public function __construct(
		ResourceConnection $resource,
		\Magento\Catalog\Model\ProductFactory $ProductFactory,
		Filesystem $filesystem,
		\Magento\Catalog\Model\Product $Product,
		\Magento\Downloadable\Model\Product\Type $DownloadableProductType
    ) {
		 $this->_resource = $resource;
		 $this->_objectManager = $ProductFactory;
		 $this->_filesystem = $filesystem;
		 $this->Product = $Product;
		 $this->DownloadableProductType = $DownloadableProductType;

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
	
	protected function getConnection($data){
		$this->connection = $this->_resource->getConnection($data);
		return $this->connection;
	}
	
	public function DownloadableProductData($ProcuctData,$ProductAttributeData,$ProductImageGallery,$ProductStockdata,$ProductSupperAttribute,$ProductCustomOption){
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
		if(isset($ProductAttributeData['special_from_date'])) { $SetProductData->setSpecialFromDate($ProductAttributeData['special_from_date']); }
		if(isset($ProductAttributeData['news_from_date'])) { $SetProductData->setNewsFromDate($ProductAttributeData['news_from_date']); }
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
		if(!isset($ProductStockdata['qty']) || $ProductStockdata['qty'] == "" || $ProductStockdata['qty'] == null){
			unset($ProductStockdata['qty']);
		}
		$SetProductData->setStockData($ProductStockdata);
		if(isset($ProductSupperAttribute['cws_tier_price'])) { 
			if($ProductSupperAttribute['cws_tier_price']!=""){ $SetProductData->setTierPrice($ProductSupperAttribute['cws_tier_price']); }
		}

		 if (isset( $ProductSupperAttribute['downloadable_product_options'] ) && $ProductSupperAttribute['downloadable_product_options'] != "") {
				if(isset($ProductAttributeData['link_title']) && $ProductAttributeData['link_title'] != ""){
					$SetProductData->setLinksTitle($ProductAttributeData['link_title']);
				}else{
					$SetProductData->setLinksTitle("Download");
				}
				if(isset($ProductAttributeData['sample_title']) && $ProductAttributeData['sample_title'] != ""){
					$SetProductData->setSamplesTitle($ProductAttributeData['sample_title']);
				}else{
					$SetProductData->setSamplesTitle("Samples");
				}
				$main_option_array = array();
				$main_temp_array=array();
				$filearrayforimport = array();
			  	$filenameforsamplearrayforimport = array();
				//$SetProductData->setLinksTitle("Download");
				$SetProductData->setLinksPurchasedSeparately($ProductSupperAttribute['link_can_purchase_separately']);
				try{
					$SetProductData->save();
				}catch(\Exception $e){
					array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku']));
				}
				// Download Sample 
				if($ProductSupperAttribute['downloadable_product_samples'] != ""){
				$downloadable_product_samples = explode('|',$ProductSupperAttribute['downloadable_product_samples']);
				foreach (array_filter($downloadable_product_samples) as $sample) {
					$sampledata=explode(";",$sample);	
					$sampledata=explode(",",$sampledata[0]);
					
					if(isset($sampledata[3])){
						$SampleSortOrder = $sampledata[3];
					}else{
						$SampleSortOrder = 0;
					};
					$basic_field=array(
							'product_id' => $SetProductData->getId(),
							'sort_order' => $SampleSortOrder,
							'sample_type' => $sampledata[1],
							'title' => $sampledata[0],
							'store_id' => 0,
							'website_id' => $SetProductData->getStore()->getWebsiteId(),
					);
					
					$sampleimagename=ltrim($sampledata[2], '/');
					$samplefile = array();
					$_samplefilePath=$sampleimagename;
					$samplefile[] = array(
						'file' => $_samplefilePath,
						'name' => $sampleimagename,
						'status' => 'new'
					);
					if($sampledata[1]=='file'){								
						$basic_field['sample_file']=json_encode($samplefile);
						$basic_field['sample_url']='';									
					}else{
						$basic_field['sample_file']='';
						$basic_field['sample_url']=$sampleimagename;
					}
					
						$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
						$linkModel = $objectManager->create('Magento\Downloadable\Model\Sample')->setData($basic_field);
						$filePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('import');

						if($sampledata[1]=='file'){
							if (!file_exists($filePath.'/'.$sampleimagename)){
							   $d_sku=$SetProductData->getSku();	
							   $SetProductData->delete();
							   $message='File Does Not Exist. SKU: '.$d_sku;
							   array_push($this->_simple_error,array('txt'=>$message,'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
							   return true;
							}

							$filePath1 = "/import";
							$basePath =  $objectManager->create('Magento\Downloadable\Model\Sample')->getBasePath();
							$sampleFileName = $objectManager->create('Magento\Downloadable\Helper\File')->moveFileFromTmp($filePath1,$basePath,$samplefile);

							try{
								$linkModel->setSampleFile($sampleFileName)->save();	
							}catch(\Exception $e){
								array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
							}

						}else{
							try{
								$linkModel->save();	
							}catch(\Exception $e){
								array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
							}
							
						}
					}
				}
				// End
				// Links Data
				$downloadable_product_main_data = explode('|',$ProductSupperAttribute['downloadable_product_options']);
				foreach (array_filter($downloadable_product_main_data) as $single) {
					$single_row=explode(";",$single);
					$linkdata=$single_row[0];	
					$linkdata=explode(",",$linkdata);		
					$sampledata=$single_row[1];		
					$sampledata=explode(",",$sampledata);
						
						$basic_field=array(
								'product_id' => $SetProductData->getId(),
								'sort_order' => 0,
								'number_of_downloads' => $linkdata[2],
								'is_shareable' => $linkdata[3],
								'link_type' => $linkdata[4],
								'sample_type' => $sampledata[0],
								'use_default_title' => false,
								'title' => $linkdata[0],
								'default_price' => 0,
								'price' => $linkdata[1],
								'store_id' => 0,
								'website_id' => $SetProductData->getStore()->getWebsiteId(),
						);
						
								$linkimagename=ltrim($linkdata[5], '/');	
								$sampleimagename=ltrim($sampledata[1], '/');
								$linkfile = array();
								$samplefile = array();
								$_highfilePath =$linkimagename;
								$_samplefilePath=$sampleimagename;
								
								$samplefile[] = array(
										'file' => $_samplefilePath,
										'name' => $sampleimagename,
										'status' => "new"
								);

								$linkfile[] = array(
										'file' => $_highfilePath,
										'name' => $linkimagename,                
										'status' => "new"
								);
								
								if($linkdata[4]=='file'){								
									$basic_field['link_file']=json_encode($linkfile);
									$basic_field['link_url']='';									
								}else{
									$basic_field['link_file']='';
									$basic_field['link_url']=$linkimagename;
								}
								
								if($sampledata[0]=='file'){								
									$basic_field['sample_file']=json_encode($samplefile);
									$basic_field['sample_url']='';									
								}else{
									$basic_field['sample_file']='';
									$basic_field['sample_url']=$sampleimagename;
								}
								$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
								$linkModel = $objectManager->create('Magento\Downloadable\Model\Link')->setData($basic_field);
								$filePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('import');
								
								if($linkdata[4]=='file' || $linkdata[4]==''){
									if (!file_exists($filePath.'/'.$linkimagename)){
									   $d_sku=$SetProductData->getSku();	
									   $SetProductData->delete();
									   $message='File Does Not Exist. SKU: '.$d_sku;
									   array_push($this->_simple_error,array('txt'=>$message,'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
									   return true;
									}
									$filePath1 = "/import";
									$basePath =  $objectManager->create('Magento\Downloadable\Model\Link')->getBasePath();
									$linkFileName = $objectManager->create('Magento\Downloadable\Helper\File')->moveFileFromTmp($filePath1,$basePath,$linkfile); 
									
									try{
										$linkModel->setLinkFile($linkFileName)->save();	
									}catch(\Exception $e){
										array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
									}
								}else{
									try{
										$linkModel->save();
									}catch(\Exception $e){
										array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
									}
								}
								//echo "hi";
								//die;
								if($sampledata[0]=='file'){									
									if (!file_exists($filePath.'/'.$sampleimagename)) {
									   $d_sku=$SetProductData->getSku();	
									   $SetProductData->delete();
									   $message='Sample File Does Not Exist. SKU: '.$d_sku;
									   array_push($this->_simple_error,array('txt'=>$message,'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
									   return true;
									}
									$filePath1 = "/import";
									$basePath =  $objectManager->create('Magento\Downloadable\Model\Link')->getBaseSamplePath();
									$sampleFileName = $objectManager->create('Magento\Downloadable\Helper\File')->moveFileFromTmp($filePath1,$basePath,$samplefile);
									try{
										$linkModel->setSampleFile($sampleFileName)->save();											
									}catch(\Exception $e){
										array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
									}
								}else{
									try{
										$linkModel->save();
									}catch(\Exception $e){
										array_push($this->_simple_error,array('txt'=>$e->getMessage(),'product_sku'=>$ProcuctData['sku'],'error_level'=>1));
									}
								}			
				}
		 }
			 /* This code is for Add Custom Options */
				if(!empty($ProductCustomOption)){
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
			$obj_product=$objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->get($ProcuctData['sku']);
			try{
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
	
	protected function userCSVDataAsArray( $data )
	{
		return explode( ',', str_replace( " ", " ", $data ) );
	} 
	
	public function TitleOfDownloadableProduct($SetProductData){
		$connection = $this->getConnection('core_write');
		$DownloadableLinkData = $SetProductData->getDownloadableData('link');
		foreach($DownloadableLinkData as $LinkData){
			if(!empty($LinkData)){
				$LinkTitle = $LinkData['title'];
				if($LinkTitle != ""){
					$connection->beginTransaction();
					$_fields = array();
					$_fields['store_id']    =  "0";
					$where = $connection->quoteInto('title =?', $LinkTitle);  
					$connection->update('downloadable_link_title', $_fields, $where);  
					$connection->commit(); 
				}
			}
		}
		$DownloadableSampleData = $SetProductData->getDownloadableData('sample');
		if(!empty($DownloadableSampleData)){
			foreach($DownloadableSampleData as $SampleData){
				if(!empty($SampleData)){
					$SampleTitle = $SampleData['title'];
					if($SampleTitle != ""){
						$connection->beginTransaction();
						$_fields = array();
						$_fields['store_id']    =  "0";
						$where = $connection->quoteInto('title =?', $SampleTitle);  
						$connection->update('downloadable_sample_title', $_fields, $where);  
						$connection->commit(); 
					}
				}
			}
		}		
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
					//->setPosition($_position[$i])
					->setPosition(1)
					->setLinkType($type);
				$this->linkReData[] = $productLink1;
				$i++;
			}
		}
	}
}