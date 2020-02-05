<?php

namespace Shops\GiftCoupon\Observer;
use Magento\Framework\Event\ObserverInterface;

	use Exception;
	use Psr\Log\LoggerInterface;
	use Magento\SalesRule\Api\Data\RuleInterface;
	use Magento\SalesRule\Api\Data\CouponInterface;
	use Magento\Framework\Exception\InputException;
	use Magento\SalesRule\Api\RuleRepositoryInterface;
	use Magento\Framework\Exception\LocalizedException;
	use Magento\SalesRule\Api\CouponRepositoryInterface;
	use Magento\Framework\Exception\NoSuchEntityException;
	use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
	use Magento\Framework\Math\Random;

class CreateCouponGift implements ObserverInterface
{
	
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CouponRepositoryInterface
     */
    protected $couponRepository;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var CouponInterface
     */
    protected $coupon;
	
	protected $mathRandom;
	
	
    public function __construct(
        CouponRepositoryInterface $couponRepository,
        RuleRepositoryInterface $ruleRepository,
        RuleInterfaceFactory $rule,
        CouponInterface $coupon,
        LoggerInterface $logger,
		Random $mathRandom
    ) {
        $this->couponRepository = $couponRepository;
        $this->ruleRepository = $ruleRepository;
        $this->rule = $rule;
        $this->coupon = $coupon;
        $this->logger = $logger;
        $this->mathRandom = $mathRandom;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		
		
		if($this->orderHasGift($observer))
			$this->createRule();
		
    }
	
	 /**
     * Create Rule
     *
     * @return void
     */
    public function createRule()
    {
		$newRule = $this->rule->create();
        $newRule->setName("Gift Discount")
            ->setDescription("Gift Discount")
            ->setIsAdvanced(true)
            ->setStopRulesProcessing(false)
            ->setCustomerGroupIds([0, 1, 2])
            ->setWebsiteIds([1])
            ->setIsRss(1)
            ->setUsesPerCoupon(1)
            ->setUsesPerCustomer(1)
            ->setDiscountStep(0)
            ->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON)
            ->setSimpleAction(RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT_FOR_CART)
            ->setDiscountAmount(20)
            ->setIsActive(true);

        try {
            $ruleCreate = $this->ruleRepository->save($newRule);
            //If rule generated, Create new Coupon by rule id
            if ($ruleCreate->getRuleId()) {
                $this->createCoupon($ruleCreate->getRuleId());
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
        
    }

    /**
     * Create Coupon by Rule id.
     *
     * @param int $ruleId
     *
     * @return int|null
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createCoupon(int $ruleId) {
        /** @var CouponInterface $coupon */
        $coupon = $this->coupon;
        $coupon->setCode($this->generateCoupon())
            ->setIsPrimary(1)
            ->setRuleId($ruleId);

        /** @var CouponRepositoryInterface $couponRepository */
        $coupon = $this->couponRepository->save($coupon);
        return $coupon->getCouponId();
    }
	
	public function orderHasGift($observer)
	{
		$result = $observer->getEvent()->getResult();
		$method_instance = $observer->getEvent()->getMethodInstance();
		$quote = $observer->getEvent()->getQuote();
		$items= $quote->getAllVisibleItems();
		foreach ($items as $item) {
            $type = $item->getProductType();
            $name = $item->getName();
			if ($type == 'virtual' && $name == "Gif") {
				return true;
			}
		}
		return false;
		
	}
	
	public function generateCoupon()
    {
		$length = 5;
        $chars = \Magento\Framework\Math\Random::CHARS_LOWERS
            . \Magento\Framework\Math\Random::CHARS_UPPERS
            . \Magento\Framework\Math\Random::CHARS_DIGITS;

        return $password = $this->mathRandom->getRandomString($length, $chars);
    }
		

    
}