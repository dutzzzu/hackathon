<?php

class RGA_Form extends Zend_Form
{
    /**
     * Makes elements placeholder attribute translatable
     * @override Zend_Form::setConfig
     */
    public function setConfig(Zend_Config $config)
    {
        $this->addPrefixPath('AARPForm_Element_', APPLICATION_PATH . '/forms/elements', 'element');
        $this->addElementPrefixPath('AARPValidator_', APPLICATION_PATH . '/validators', 'validate');
        $options = $this->setOptions($config->toArray());

        foreach($this->getElements() as $key => $element) {
            $placeholder = $element->getAttrib('placeholder');
            if(isset($placeholder))
            {
                $this->$key->setAttrib('placeholder', $this->getView()->translate($placeholder));
            }
        }
        
        return $options;
    }
}
