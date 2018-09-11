<?php
namespace Magebees\Products\Block\Adminhtml\Import\Edit\Tab;

class Validationlog extends \Magento\Backend\Block\Widget\Grid\Extended
{
	protected $request;
	protected $_validationlogFactory;
	protected $_flag=false;
	protected $_count=0;
	
	public function __construct(
        \Magento\Backend\Block\Template\Context $context,
		\Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
		\Magento\Framework\App\Request\Http $request,
		\Magebees\Products\Model\ValidationlogFactory $validationlogFactory,
        array $data = array()
    ) {
		$this->request = $request;
		$this->_validationlogFactory = $validationlogFactory;
		parent::__construct($context, $backendHelper, $data); //working
	}
	
	protected function _construct()	{
		parent::_construct();
		$this->setId('productImportGrid');
		$this->setDefaultSort('id');
		$this->setDefaultDir('DESC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
	}
	
	protected function _prepareCollection()	{
		$collection = $this->_validationlogFactory->create()->getCollection();
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() 
	{
		 $this->addColumn(
            'log_id',
            [
                'header' => __('Log ID'),
				'width'     => 5,
				'align'     => 'right',
				'sortable'  => true,
				'index'     => 'log_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

		$this->addColumn(
            'product_sku',
            [
                'header' => __('Product SKU'),
				'width'     => 5,
				'align'     => 'right',
				'sortable'  => true,
				'index'     => 'product_sku',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

		$this->addColumn(
            'error_information',
            [
                'header' => __('Error'),
				'width'     => 5,
				'align'     => 'right',
				'sortable'  => true,
				'index'     => 'error_information',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
		
		$this->addColumn(
            'error_type',
            [
                'header' => __('Error Level'),
				'width'     => 5,
				'align'     => 'right',
				'sortable'  => true,
				'index'     => 'error_type',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
				'type'      => 'options',
				'options'   => array('0'=>'Minor','1'=>'Major'),
				'frame_callback' => array($this, 'decorateStatus')
            ]
        );
		//$this->addExportType('*/*/exportValidationCsv',Mage::helper('adminhtml')->__('CSV'));
        return parent::_prepareColumns();
	}

	public function decorateStatus($value, $row, $column, $isExport)
	{
		if ($value=='Major') {
			$cell = '<span class="grid-severity-critical"><span>'.$value.'</span></span>';
		} else {
			$cell = '<span class="grid-severity-minor"><span>'.$value.'</span></span>';
		}
		return $cell;
	}
	
	public function getGridUrl()
	{
		return $this->getUrl('*/import/validgrid', ['_current' => true]);
	}
	
	public function getMainButtonsHtml()
	{
		$html = '';
		if($this->getFilterVisibility()){
			$html.= $this->getResetFilterButtonHtml();
			$html.= $this->getSearchButtonHtml();
		}
		return $html;
	}
	
	/*public function _toHtml()
	{
		$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		if($this->request->getParam('isAjax')!=true){
			$error_info = '<div id="messages"><div class="messages"><div class="message"><div data-ui-id="messages-message-success"><b>Minor Error: </b> This error is just for information purpose, it can not cause import issue.<br/><b>Major Error: </b> This error is require modification in your file or magento store. It may be cause issue. </div></div></div></div>';
		}

		if($this->_flag && $this->request->getParam('isAjax')!=true)
		{
			$validationlog = $_objectManager->create('\Magebees\Products\Model\ResourceModel\Validationlog\Collection');
			$_total_error_count=$validationlog->getSize();
			$cust_html = '<ul class="messages">
				<li class="notice-msg">
					<ul>
						<li><span>Please fix errors and re-upload file or simply press "Import" button to skip rows with errors&nbsp;&nbsp;'.$this->getImportButtonHtml().'</span></li>
						<li><span>Checked Products: '.$this->request->getParam('totalRecords').', invalid Products: '.$this->request->getParam('countOfError').', total errors:'.$_total_error_count.' </span></li>
					</ul>
				</li>
			</ul>';
		}
		if(isset($cust_html)){
			return $error_info.$cust_html.parent::_toHtml();
		}else{
			return $error_info.parent::_toHtml();
		}
	}*/
	public function getImportButtonHtml()
    {
        return $this->getChildHtml('import_button');
    }
	protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
	
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
