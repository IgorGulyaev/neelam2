<?php
namespace Magebees\Products\Model;

class Validator extends \Magento\Framework\Model\AbstractModel
{
	protected $_validation_status=false;
	protected $request;

	public function __construct(\Magento\Framework\App\Request\Http $request) {
		$this->request = $request;
	}
	public function setProfilerData()
	{
		$data=$this->getConverter();
		$row=array();
		foreach($data['data'] as $d)
		{
			$temp_row=array();
			$temp_row['product_data']=serialize($d);
			$temp_row['validate']=$this->_validation_status;
			$row[]=$temp_row;				
										
			if(count($row)>5 || $d === end($data['data']))
			{
				try{
					$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
					$model = $_objectManager->create('\Magebees\Products\Model\ResourceModel\Profiler');
					$model->insertMultipleProduct($row);
				}catch(\Exception $e){
					//echo $e->getMessage(); 
				}					
				$row=array();
			}
		}
		return $data['url'];
  }
  
	public function getConverter()
	{
		$importfiletype = $this->request->getParam('importfiletype');
		$this->setValidationStatus();
		$validationBehavior = $this->request->getParam('validationBehavior');
		$files = $this->request->getParam('files');
		$pointer = $this->request->getParam('pointer');
		
		switch($importfiletype){
			default:
				$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$model = $_objectManager->create('\Magebees\Products\Model\Validator\Csv');
				return $model->parse($importfiletype,$validationBehavior,$files,$pointer);
				break;
		}
	}
	public function setValidationStatus(){
		$validate = $this->request->getParam('validationBehavior');
		if($validate == 'skip'){
			$this->_validation_status=true;
		}
	}
}