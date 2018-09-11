<?php
namespace Magebees\Products\Controller\Adminhtml\Import;
class Validaterecord extends \Magento\Backend\App\Action
{
	protected $registry = null;
	protected $resultPageFactory;
	
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        parent::__construct($context);
    }

	public function execute()
	{
		$direction=$this->getRequest()->getParam('direction');
		$timestamp = $this->getRequest()->getParam('timestamp');
		$behavior = $this->getRequest()->getParam('behavior');
		$imagelocation = $this->getRequest()->getParam('imagelocation');
		if(isset($imagelocation) && $imagelocation != ""){
			$catalogSession = $this->_objectManager->create('Magento\Catalog\Model\Session');
			$catalogSession->setMyvalue($imagelocation);
		}
		$url_reditect = "";
		if($direction=='Validated'){
			$this->_objectManager->create('\Magebees\Products\Model\ResourceModel\Importlog')->truncate();
			if($this->getRequest()->getParam('validationBehavior')=='skip'){
				return;
			}
			$max = ini_get('max_execution_time')/15; 
			$collection = $this->_objectManager->create('\Magebees\Products\Model\Profiler')->getCollection()->addFieldToFilter('validate','0');
			if($max<10)
			{
				$collection->getSelect()->limit(5);
			}else{
				$collection->getSelect()->limit(15);
			}
			$next_flag=true;
			$error_ctn=0;
			foreach($collection as $d)
			{
				$error = $this->_objectManager->create("\Magebees\Products\Model\Validator\Validate\Importvalidator")->runValidator($d->getData(),$timestamp,$behavior);
				if(count($error['error'])!=0){
					//$error_ctn++;
				}
				$d->setBypassImport($error['bypass']);
				$d->setValidate(true);
				$d->save();
			}
			$url_reditect = '';
			if(count($collection)==0){
				$vardir = \Magento\Framework\App\ObjectManager::getInstance();
				$filesystem = $vardir->get('Magento\Framework\Filesystem');
				$reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
				$flagDir = $reader->getAbsolutePath("import/").'cws_product_import_flag_validator_do_not_delete-'.$timestamp.'.flag';
				
				if(file_exists($flagDir)){
					unlink($flagDir);
				}
				$next_flag=false;
				$url_reditect = $this->getUrl('products/import/index',array('active_tab'=>'validationlog','show_import_button'=>'true','behavior'=>$this->getRequest()->getParam('behavior'),'validationBehavior'=>$this->getRequest()->getParam('validationBehavior')));
			}
			
			//Parag added
			$count = count($this->_objectManager->create('\Magebees\Products\Model\Profiler')->getCollection());
			$imported_products=count($collection);
			$this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode(array('count'=>$count,'next'=>$next_flag,'imported'=>$imported_products,'error'=>$error_ctn,'url'=>$url_reditect)));
		
		}
		else if ($direction=='Imported')
		{
			//$this->_objectManager->create('\Magebees\Products\Model\ResourceModel\Importlog')->truncate();//Added 17-01-2016
			$max = ini_get('max_execution_time')/15;
			$this->registry->register('cws_import_mode', true);
			$collection = $this->_objectManager->create('\Magebees\Products\Model\Profiler')->getCollection()->addFieldToFilter('validate','1')->addFieldToFilter('imported','0');
			//$count1 = $collection->count();
			if($max<5)
			{
				$collection->getSelect()->limit(5);
			}else{
				$collection->getSelect()->limit(10);
			}
			
			$next_flag=true;
			$error_ctn=0;
			if(count($collection)==0){
				$next_flag=false;
				$this->deleteImportFlag();
				$url_reditect=$this->getUrl('*/*/index',array('active_tab'=>'importlog','show_import_alert_box'=>'true'));
			}else{	
				$this->createImportFlagIfNoExist();
			}
			$importproduct_adapter = $this->_objectManager->create('\Magebees\Products\Model\Convert\Adapter\Importproducts');
			
			foreach($collection as $d){
 				if($d->getBypassImport())
				{	
					$error_ctn++;
					$product_data=unserialize($d->getProductData());
					$error_model = $this->_objectManager->create('\Magebees\Products\Model\Importlog');
					$error_model->setErrorInformation('Product SKU: '.$product_data['sku'].' not imported due to major error.');
					$error_model->setErrorType(1);
					$error_model->setProductSku($product_data['sku']);
					$error_model->save();
					$d->setImported(true);
					$d->save();
					continue;
				} 
				
				$error = $importproduct_adapter->runImport($d->getData());
				if(count($error)!=0)
				{
					$error_ctn++;
				}
				$d->delete();
			}
			//'count1'=>$count1,
			$this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode(array('next'=>$next_flag,'imported'=>count($collection),'error'=>$error_ctn,'url'=>$url_reditect)));
		}
	}
	
	public function createImportFlagIfNoExist()
	{
		$timestamp = $this->getRequest()->getParam('timestamp');		
		$vardir = \Magento\Framework\App\ObjectManager::getInstance();
		$filesystem = $vardir->get('Magento\Framework\Filesystem');
		$reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
		$flagDir = $reader->getAbsolutePath("import/".'cws_product_import_flag_do_not_delete-'.$timestamp.'.flag');
		
		if(file_exists($flagDir))
		{
			$flag_data = file_get_contents($flagDir);
			$load_product_related_info=unserialize($flag_data);
			if($load_product_related_info['product_type']!==NULL){
				$this->registry->register('product_type', $load_product_related_info['product_type']);
			}else{
				$this->loadProductType();
			}
			
			if($load_product_related_info['product_attribute_set']!==NULL){
				$this->registry->register('product_attribute_set', $load_product_related_info['product_attribute_set']);
			}else{
				$this->loadAttributeSet();
			}

			if($load_product_related_info['store']!==NULL && $load_product_related_info['store_code']!==NULL){
				$this->registry->register('store_code', $load_product_related_info['store_code']);
				$this->registry->register('store', $load_product_related_info['store']);
			}else{
				$this->loadStores();						
			}

			if($load_product_related_info['load_basic_param']!==NULL){
				$this->registry->register('load_basic_param', $load_product_related_info['load_basic_param']);
				
			}else{
				$this->initBasicParam();			
			}
			
		}else{
				$flag_file = fopen($flagDir, "w");
				$this->loadAttributeSet();
				$this->loadProductType();
				$this->loadStores();		
				$this->initBasicParam();
				$data = array();
				$data['product_type'] = $this->registry->registry('product_type');
				$data['product_attribute_set'] = $this->registry->registry('product_attribute_set');
				$data['store_code'] = $this->registry->registry('store_code');
				$data['store'] = $this->registry->registry('store');
				$data['load_basic_param'] = $this->registry->registry('load_basic_param');
				fwrite($flag_file, serialize($data));
				fclose($flag_file);
				$this->_objectManager->create('\Magebees\Products\Model\ResourceModel\Importlog')->truncate();
		}
	}
	
	public function loadProductType()
	{
		$_productTypes = array();
		$options = $this->_objectManager->create('\Magento\Catalog\Model\Product\Type')->getOptionArray();
		foreach ($options as $k => $v) {
			$_productTypes[$k] = $k;
		}
		$this->registry->register('product_type',$_productTypes);
	}
	public function loadAttributeSet(){
		$_productAttributeSets = array();
		$collection = $this->_objectManager->create('\Magento\Catalog\Model\Product\AttributeSet\Options')->toOptionArray();
		
		foreach ($collection as $key => $set) {
			$_productAttributeSets[$set['label']] = $set['value'];
			//$_productAttributeSets[$set->getAttributeSetName()] = $set->getId();
		}
		$this->registry->register('product_attribute_set',$_productAttributeSets);
	}
	public function loadStores()
	{
		$obj = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $obj->get('Magento\Store\Model\StoreManagerInterface');
		$_stores = $storeManager->getStores(true, true);
		$this->registry->register('store_code',$_stores);
        foreach ($_stores as $code => $store) {
          $_storesIdCode[$store->getId()] = $code;
        }
		$this->registry->register('store',$_storesIdCode);
	}
	public function initBasicParam()
	{
		$this->registry->register('load_basic_param',$this->getAttributeValue());		
	}
	public function getAttributeValue()
	{
		return unserialize('a:5:{s:28:"_inventoryFieldsProductTypes";a:6:{s:6:"simple";a:16:{i:0;s:3:"qty";i:1;s:7:"min_qty";i:2;s:18:"use_config_min_qty";i:3;s:14:"is_qty_decimal";i:4;s:18:"is_decimal_divided";i:5;s:10:"backorders";i:6;s:21:"use_config_backorders";i:7;s:12:"min_sale_qty";i:8;s:23:"use_config_min_sale_qty";i:9;s:12:"max_sale_qty";i:10;s:23:"use_config_max_sale_qty";i:11;s:11:"is_in_stock";i:12;s:16:"notify_stock_qty";i:13;s:27:"use_config_notify_stock_qty";i:14;s:12:"manage_stock";i:15;s:23:"use_config_manage_stock";}s:7:"virtual";a:16:{i:0;s:3:"qty";i:1;s:7:"min_qty";i:2;s:18:"use_config_min_qty";i:3;s:14:"is_qty_decimal";i:4;s:18:"is_decimal_divided";i:5;s:10:"backorders";i:6;s:21:"use_config_backorders";i:7;s:12:"min_sale_qty";i:8;s:23:"use_config_min_sale_qty";i:9;s:12:"max_sale_qty";i:10;s:23:"use_config_max_sale_qty";i:11;s:11:"is_in_stock";i:12;s:16:"notify_stock_qty";i:13;s:27:"use_config_notify_stock_qty";i:14;s:12:"manage_stock";i:15;s:23:"use_config_manage_stock";}s:12:"downloadable";a:15:{i:0;s:3:"qty";i:1;s:7:"min_qty";i:2;s:18:"use_config_min_qty";i:3;s:14:"is_qty_decimal";i:4;s:10:"backorders";i:5;s:21:"use_config_backorders";i:6;s:12:"min_sale_qty";i:7;s:23:"use_config_min_sale_qty";i:8;s:12:"max_sale_qty";i:9;s:23:"use_config_max_sale_qty";i:10;s:11:"is_in_stock";i:11;s:16:"notify_stock_qty";i:12;s:27:"use_config_notify_stock_qty";i:13;s:12:"manage_stock";i:14;s:23:"use_config_manage_stock";}s:12:"configurable";a:3:{i:0;s:11:"is_in_stock";i:1;s:12:"manage_stock";i:2;s:23:"use_config_manage_stock";}s:7:"grouped";a:3:{i:0;s:11:"is_in_stock";i:1;s:12:"manage_stock";i:2;s:23:"use_config_manage_stock";}s:6:"bundle";a:3:{i:0;s:11:"is_in_stock";i:1;s:12:"manage_stock";i:2;s:23:"use_config_manage_stock";}}s:16:"_inventoryFields";a:16:{i:0;s:3:"qty";i:1;s:7:"min_qty";i:2;s:18:"use_config_min_qty";i:3;s:14:"is_qty_decimal";i:4;s:18:"is_decimal_divided";i:5;s:10:"backorders";i:6;s:21:"use_config_backorders";i:7;s:12:"min_sale_qty";i:8;s:23:"use_config_min_sale_qty";i:9;s:12:"max_sale_qty";i:10;s:23:"use_config_max_sale_qty";i:11;s:11:"is_in_stock";i:12;s:16:"notify_stock_qty";i:13;s:27:"use_config_notify_stock_qty";i:14;s:12:"manage_stock";i:15;s:23:"use_config_manage_stock";}s:15:"_requiredFields";a:6:{i:0;s:4:"name";i:1;s:11:"description";i:2;s:17:"short_description";i:3;s:6:"weight";i:4;s:5:"price";i:5;s:12:"tax_class_id";}s:13:"_ignoreFields";a:7:{i:0;s:13:"attribute_set";i:1;s:4:"type";i:2;s:12:"category_ids";i:3;s:9:"entity_id";i:4;s:16:"attribute_set_id";i:5;s:7:"type_id";i:6;s:16:"required_options";}s:9:"_toNumber";a:4:{i:0;s:3:"qty";i:1;s:7:"min_qty";i:2;s:12:"min_sale_qty";i:3;s:12:"max_sale_qty";}}');
	}
	public function deleteImportFlag()
	{
		$timestamp = $this->getRequest()->getParam('timestamp');			
		$vardir = \Magento\Framework\App\ObjectManager::getInstance();
		$filesystem = $vardir->get('Magento\Framework\Filesystem');
		$reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
		$flagDir = $reader->getAbsolutePath("import/".'cws_product_import_flag_do_not_delete-'.$timestamp.'.flag');
		
		if(file_exists($flagDir)){
				unlink($flagDir);
		}
	}

	protected function _isAllowed(){
       return $this->_authorization->isAllowed('Magebees_Products::import');
	}
}