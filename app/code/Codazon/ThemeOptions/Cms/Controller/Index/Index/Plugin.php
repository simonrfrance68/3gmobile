<?php
namespace Codazon\ThemeOptions\Cms\Controller\Index\Index;
class Plugin
{
    protected $action;
    protected $scopeConfig;
    protected $helperPage;
    protected $resultForwardFactory;
	public function __construct(
        \Magento\Cms\Controller\Index\Index $action,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Cms\Helper\Page $helperPage
    ) {
        $this->action = $action;
        $this->scopeConfig = $scopeConfig;
        $this->helperPage = $helperPage;
        $this->resultForwardFactory = $resultForwardFactory;
    }
    
    public function aroundExecute($subject, $procede)
    {
    	$pageId = $this->scopeConfig->getValue(
            \Magento\Cms\Helper\Page::XML_PATH_HOME_PAGE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES
        );

        $resultPage = $this->helperPage->prepareResultPage($subject, $pageId);
        if (!$resultPage) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('defaultIndex');
            return $resultForward;
        }
        return $resultPage;
    	//$result = $procede($coreRoute);
    	
		
    	//return $result;
    }
}
