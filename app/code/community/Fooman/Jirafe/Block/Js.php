<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @package     Fooman_Jirafe
 * @copyright   Copyright (c) 2010 Jirafe Inc (http://www.jirafe.com)
 * @copyright   Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Fooman_Jirafe_Block_Js extends Mage_Core_Block_Template
{
    const VISITOR_ALL       = 'A';
    const VISITOR_BROWSERS  = 'B';
    const VISITOR_ENGAGED   = 'C';
    const VISITOR_READY2BUY = 'D';
    const VISITOR_CUSTOMER  = 'E';
    
    const PAGE_PRODUCT  = 1;
    const PAGE_CATEGORY = 2;
    
    public $pageLevel = self::VISITOR_BROWSERS;
    public $pageType;

    /**
     * Set default template
     *
     */
    protected function _construct()
    {
        $this->setTemplate('fooman/jirafe/js.phtml');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function getSiteId()
    {
        return Mage::helper('foomanjirafe')->getStoreConfig('site_id', Mage::app()->getStore()->getId());
    }

    public function getBaseURL()
    {
        return Mage::getModel('foomanjirafe/jirafe')->getPiwikBaseUrl();
    }
    
    public function getProduct()
    {
        $product = Mage::registry('product');
        $aCategories = array();
        foreach ($product->getCategoryIds() as $id) {
            $category = Mage::getModel('catalog/category')->load($id);
            $aCategories[] = $this->getCategory($category);
        }
        return array(
            'sku'        => $product->getSku(),
            'name'       => $product->getName(),
            'categories' => $aCategories,
        );
    }
    
    public function getCategory($category = null)
    {
        $aCategories = array();
        if (!isset($category)) {
            $category = Mage::registry('current_category');
        }
        foreach ($category->getPathIds() as $k => $id) {
            // Skip null and root
            if ($k > 1) {
                $category = Mage::getModel('catalog/category')->load($id);
                $aCategories[] = $category->getName();
            }
        }
        return join('/', $aCategories);
    }
    
    public function setJirafePageLevel($level)
    {
        if (strlen($level) > 1) {
            // Maybe type is a class constant name?
            $level = constant(__CLASS__.'::'.$level);
        }
        if (!empty($level) && $level > $this->pageLevel) {
            $this->pageLevel = $level;
        }
    }
    
    public function getJirafePageLevel()
    {
        $level = $this->_getSession()->getJirafePageLevel();
        if (!empty($level) && $level > $level->pageLevel) {
            // Override page type with session data
            $this->pageLevel = $level;
            // Clear session variable
            $this->_getSession()->setJirafePageLevel(null);
        }
        
        return $this->pageLevel;
    }
    
    public function setJirafePageType($type)
    {
        $type = constant(__CLASS__.'::'.$type);
        if (!empty($type)) {
            $this->pageType = $type;
        }
    }
    
    public function getTrackingCode()
    {
        $aData = array(
            'siteId'    => $this->getSiteId(),
            'pageLevel' => $this->getJirafePageLevel(),
            'baseUrl'   => $this->getBaseURL(),
        );
        
        switch ($this->pageType) {
            case self::PAGE_PRODUCT:
                $aData['product']  = $this->getProduct();
                break;
            case self::PAGE_CATEGORY:
                $aData['category'] = $this->getCategory();
                break;
        }
        
        $jirafeJson = json_encode($aData);
    
        return <<<EOF
<!-- Jirafe:START -->
<script type="text/javascript">
(function(j){
    jirafe = j;
    var d = document,
        g = d.createElement('script'),
        s = d.getElementsByTagName('script')[0];
    g.type = 'text/javascript';
    g.defer = g.async = true;
    g.src = d.location.protocol + '//' + j.baseUrl + 'jirafe.js';
    s.parentNode.insertBefore(g, s);
})({$jirafeJson});
</script>
<!-- Jirafe:END -->

EOF;
    }
    
}
