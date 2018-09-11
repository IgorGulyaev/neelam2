<?php
namespace Magebees\Products\Block\Adminhtml\Export\Edit\Tab;
class Export extends \Magento\Backend\Block\Widget\Form\Generic
{
	
	public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = array()
    ) {
		$this->setTemplate('Magebees_Products::export.phtml');
        parent::__construct($context, $registry, $formFactory, $data);		
	}
	
	public function getAttributeSet()
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $coll = $objectManager->create('\Magento\Catalog\Model\Product\AttributeSet\Options');
		return $coll->toOptionArray();
	}
	
	public function getStoreData()
    {
		$storedata = \Magento\Framework\App\ObjectManager::getInstance();
		$model_store = $storedata->create('Magento\Store\Model\System\Store');
		$store_info	= $model_store->getStoreValuesForForm(false, true);
		return $store_info;
	}
	
	

    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }	
}