<?php
namespace Magebees\Products\Controller\Adminhtml\Export;
class Mergecsv extends \Magento\Backend\App\Action
{
	protected $_coreRegistry = null;
	protected $resultPageFactory;
	protected $_proceed_next=true;
	
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }
 
  		
	public function execute()
	{
		$filesystem = $this->_objectManager->get('Magento\Framework\Filesystem');
		$reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
		$path = $reader->getAbsolutePath('export/');
		
		$timestamp = $this->getRequest()->getParam('timestamp');
		$filename = $this->getRequest()->getParam('filename');
		$page_ctn = $this->getRequest()->getParam('page');
		$currentPage = $this->getRequest()->getParam('processPage');

		//$cws_csv_header=$path.'cws_csv_header-'.$timestamp.'.obj';
		$cws_csv_header=$path.'cws_csv_header-'.$timestamp.'.obj';
		
		$header_string_obj=file_get_contents($cws_csv_header);
		$header_template=unserialize($header_string_obj);
		$tempFile = fopen($path.$filename."-tmp-".$currentPage,"r");
		//$temp_data=fgetcsv($tempFile);
		$tmp_data=array();
		$temp_header=array();
		$_temp_first=true;
		while(!feof($tempFile))
		{
		  if($_temp_first)
		  {
			$temp_header=fgetcsv($tempFile);
			$_temp_first=false;
		  }else{
			$temp_single_record=array();
			$temp_data = fgetcsv($tempFile);
			if (is_array($temp_data) || is_object($temp_data)){
				//foreach(fgetcsv($tempFile) as $key=>$value){
				foreach($temp_data as $key=>$value){
					$temp_single_record[$temp_header[$key]]=$value;	
					//$temp_single_record[$value]=$value;	
				}
			}
			if (array_filter($temp_single_record)) {
				$tmp_data[]=$temp_single_record;
			}
		  }
		}
		fclose($tempFile);
		unlink($path.$filename."-tmp-".$currentPage);
		
		if($currentPage=='1'){
			$fp = fopen($path.$filename, 'w');
			fputcsv($fp, array_values($header_template));
		}else{
			$fp = fopen($path.$filename, 'a');
		}

		foreach ($tmp_data as $product) {
			$o_data=array_fill_keys(array_values($header_template), '');
			foreach($product as $o_key=>$o_val)
			{
				if (in_array($o_key, $header_template)) {
					$o_data[$o_key]=$o_val;
				}
			}
			fputcsv($fp, array_values($o_data));
		}					
		fclose($fp);
		if($currentPage==$page_ctn){
			$this->_proceed_next=false;
			unlink($cws_csv_header);
		}
		$currentPage++;
		$combiner_status = array('proceed_next'=>$this->_proceed_next,'timestamp'=>$timestamp,'filename'=>$filename,'page'=>$page_ctn,'processPage'=>$currentPage);
		
		$this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($combiner_status));
		return;
	}
	protected function _isAllowed(){
       return $this->_authorization->isAllowed('Magebees_Products::export');
	}
}