<?php
namespace Magebees\Products\Controller\Adminhtml\Export;
class Exportrun extends \Magento\Backend\App\Action
{
	protected $_coreRegistry = null;
	protected $resultPageFactory;
	protected $request;
	protected $_storeManager;
	protected $_configurable;
	protected $date;
	
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
		\Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Stdlib\DateTime\DateTime $date,
		\Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->request = $request; 
		$this->_storeManager = $storeManager;
		$this->_configurable = $configurable;
		$this->date = $date;

        parent::__construct($context);
    }
 
  		
	public function execute()
	{
		$page = $this->getRequest()->getParam('page');
		$store_export_id = $this->getRequest()->getParam('store_id');
		$attr_id = $this->getRequest()->getParam('attr_id');
		$export_for = $this->getRequest()->getParam('export_for');
		$timestamp = $this->getRequest()->getParam('timestamp');
		$type_id = $this->getRequest()->getParam('type_id');
		$status_id = $this->getRequest()->getParam('status_dropdown');
		$visibility_id = $this->getRequest()->getParam('visibility_dropdown');
		$cat_ids = $this->getRequest()->getParam('categoriesSelect');
		
		$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$export_file_name = $this->_objectManager->create('\Magebees\Products\Model\Exportproduct')->getProductExportFile($page,$store_export_id,$attr_id,$export_for,$timestamp,$type_id,$status_id,$visibility_id,$cat_ids);
		if($export_file_name['proceed_next']==false){
			$exportedfile = $this->_objectManager->create('\Magebees\Products\Model\Exportfile');
			$exportedfile->setFileName($export_file_name['filename']);
			$exportedfile->setExportedFileDateTimes($this->date->timestamp());
			$exportedfile->save();
		}
		
		$this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($export_file_name));
		return;
	}

	protected function _isAllowed(){
       return $this->_authorization->isAllowed('Magebees_Products::export');
	}
}