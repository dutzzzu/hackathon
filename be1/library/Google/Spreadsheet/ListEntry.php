<?php
/**
 * Copyright 2013 Asim Liaquat
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!class_exists('Google_Client')) {
    require_once dirname(__FILE__) . '/../autoload.php';
}

/**
 * List Entry
 *
 * @package    Google
 * @subpackage Spreadsheet
 * @author     Asim Liaquat <asimlqt22@gmail.com>
 */
class Google_Spreadsheet_ListEntry
{
    /**
     * The xml representation of this list entry
     * 
     * @var \SimpleXMLElement
     */
    protected $xml;

    /**
     * The data for this list entry
     * 
     * @var array
     */
    protected $data;

    /**
     * Constructor
     * 
     * @param \SimpleXMLElement $xml
     * @param array             $data
     */
    public function __construct($xml, $data)
    {
        $this->xml = $xml;
        $this->data = $data;
    }

    /**
     * Get the raw XML
     * 
     * @return int
     */
    public function getXml()
    {
        return $this->xml;
    }
    
    /**
     * Get the values of this list entry
     * 
     * @return array
     */
    public function getValues()
    {
        return $this->data;
    }

    /**
     * Update this entry
     * 
     * @param array $values
     */
    public function update($values)
    {        
        $entry = '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:gsx="http://schemas.google.com/spreadsheets/2006/extended">';
        $entry .= '<id>'.$this->xml->id->__toString().'</id>';

        foreach($values as $colName => $value) {
            $entry .= sprintf(
                '<gsx:%s><![CDATA[%s]]></gsx:%s>',
                $colName,
                $value,
                $colName
            );
        }

        $entry .= '</entry>';

        Google_Spreadsheet_ServiceRequestFactory::getInstance()->put($this->getEditUrl(), $entry);
    }

    /**
     * Delete the current entry.
     */
    public function delete()
    {
        Google_Spreadsheet_ServiceRequestFactory::getInstance()->delete($this->getEditUrl());
    }
    
    /**
     * Get the edit url
     * 
     * @return string
     */
    public function getEditUrl()
    {
        return Google_Spreadsheet_Util::getLinkHref($this->xml, 'edit');
    }
}