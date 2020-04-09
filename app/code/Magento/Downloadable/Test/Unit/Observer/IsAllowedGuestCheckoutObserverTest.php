<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Downloadable\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Downloadable\Model\Product\Type;
use Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory;
use Magento\Downloadable\Observer\IsAllowedGuestCheckoutObserver;
use Magento\Framework\App\Config;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IsAllowedGuestCheckoutObserverTest extends TestCase
{
    /** @var IsAllowedGuestCheckoutObserver */
    private $isAllowedGuestCheckoutObserver;

    /**
     * @var MockObject|Config
     */
    private $scopeConfig;

    /**
     * @var MockObject|DataObject
     */
    private $resultMock;

    /**
     * @var MockObject|Event
     */
    private $eventMock;

    /**
     * @var MockObject|Observer
     */
    private $observerMock;

    /**
     * @var MockObject|DataObject
     */
    private $storeMock;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSetFlag', 'getValue'])
            ->getMock();

        $this->resultMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['setIsAllowed'])
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getResult', 'getQuote', 'getOrder'])
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->isAllowedGuestCheckoutObserver = (new ObjectManagerHelper($this))->getObject(
            IsAllowedGuestCheckoutObserver::class,
            [
                'scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     *
     * @dataProvider dataProviderForTestisAllowedGuestCheckoutConfigSetToTrue
     *
     * @param $productType
     * @param $isAllowed
     */
    public function testIsAllowedGuestCheckoutConfigSetToTrue($productType, $isAllowed): void
    {
        if ($isAllowed) {
            $this->resultMock->expects($this->at(0))
                ->method('setIsAllowed')
                ->with(false);
        }

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeId'])
            ->getMock();

        $product->expects($this->once())
            ->method('getTypeId')
            ->willReturn($productType);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();

        $item->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMock();

        $quote->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item]);

        $this->eventMock->expects($this->once())
            ->method('getStore')
            ->will($this->returnValue($this->storeMock));

        $this->eventMock->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($this->resultMock));

        $this->eventMock->expects($this->once())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $this->scopeConfig->expects($this->any())
            ->method('isSetFlag')
            ->with(
                'catalog/downloadable/disable_guest_checkout',
                ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->willReturn(true);

        $this->observerMock->expects($this->exactly(3))
            ->method('getEvent')
            ->will($this->returnValue($this->eventMock));

        $this->assertInstanceOf(
            IsAllowedGuestCheckoutObserver::class,
            $this->isAllowedGuestCheckoutObserver->execute($this->observerMock)
        );
    }

    /**
     * @return array
     */
    public function dataProviderForTestisAllowedGuestCheckoutConfigSetToTrue(): array
    {
        return [
            1 => [Type::TYPE_DOWNLOADABLE, true],
            2 => ['unknown', false],
        ];
    }

    public function testIsAllowedGuestCheckoutConfigSetToFalse(): void
    {
        $storeCode = 1;

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeId'])
            ->getMock();

        $product->expects($this->once())
            ->method('getTypeId')
            ->willReturn(Type::TYPE_DOWNLOADABLE);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();

        $item->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMock();

        $quote->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item]);

        $this->eventMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeCode);

        $this->eventMock->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($this->resultMock));

        $this->eventMock->expects($this->once())
            ->method('getQuote')
            ->will($this->returnValue($quote));

        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'catalog/downloadable/disable_guest_checkout',
                ScopeInterface::SCOPE_STORE,
                $storeCode
            )
            ->willReturn(false);

        $this->observerMock->expects($this->exactly(3))
            ->method('getEvent')
            ->will($this->returnValue($this->eventMock));

        $this->assertInstanceOf(
            IsAllowedGuestCheckoutObserver::class,
            $this->isAllowedGuestCheckoutObserver->execute($this->observerMock)
        );
    }
}
