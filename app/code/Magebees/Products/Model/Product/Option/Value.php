<?php

namespace Magebees\Products\Model\Product\Option;

class Value extends \Magento\Catalog\Model\Product\Option\Value
{
    public function saveValues()
    {
        foreach ($this->getValues() as $value) {
            $this->setData(
                $value
            )->setData(
                'option_id',
                $this->getOption()->getId()
            )->setData(
                'store_id',
                $this->getOption()->getStoreId()
            );
			
			//CWS Code Modified
            if ($this->getData('option_type_id') == '-1') {
                //change to 0
                $this->unsetData('option_type_id');
            } else {
                $this->setId($this->getData('option_type_id'));
            }

            if ($this->getData('is_delete') == '1') {
                if ($this->getId()) {
                    $this->deleteValues($this->getId());
                }
            } else {
                $this->save();
            }
        }
        //eof foreach()
        return $this;
    }
}
