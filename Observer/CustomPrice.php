<?php

namespace Shops\GiftCoupon\Observer;


use Magento\Framework\Event\ObserverInterface;



class CustomPrice implements ObserverInterface
{
    protected $objectManager;

    public function __construct(
                                \Magento\Framework\ObjectManagerInterface $objectManager)  
								{
									$this->objectManager = $objectManager;
									}



    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $items = $cart->getQuote()->getAllItems();
        foreach ($items as $item) {
            $type = $item->getProductType();
            $name = $item->getName();
			$qty = $item->getQty();

            if ($type == 'virtual' && $name == "Gif") {
                $_customOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                foreach ($_customOptions['options'] as $_option) {
                    if ($_option['label'] == 'Amount') 
                        $price = $_option['value'];
                }
                    $item->setCustomPrice($qty*$price);
                    $item->setOriginalCustomPrice($price);
                    $item->getProduct()->setIsSuperMode(true);
            }


        }
    }


}

 
