<?php
declare(strict_types=1);

namespace Bold\Checkout\Observer;

use Bold\Checkout\Api\Http\ClientInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Place order on Bold side observer.
 */
class ProcessOrderObserver implements ObserverInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param Session $checkoutSession
     * @param ClientInterface $client
     */
    public function __construct(Session $checkoutSession, ClientInterface $client)
    {
        $this->checkoutSession = $checkoutSession;
        $this->client = $client;
    }

    /**
     * Place order on Bold side.
     *
     * @param Observer $observer
     * @return void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $boldCheckoutData = $this->checkoutSession->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            return;
        }
        if ($order->getPayment()->getMethod() !== 'bold') {
            return;
        }
        $websiteId = (int)$order->getStore()->getWebsiteId();
        $taxes = $this->client->post($websiteId, 'taxes', []);
        if ($taxes->getErrors()) {
            throw new LocalizedException(__('Unable to set taxes.'));
        }
        if ($order->getDiscountAmount()) {
            $discount = $this->client->post($websiteId, 'discounts', ['code' => 'Discount']);
            if ($discount->getErrors()) {
                throw new LocalizedException(__('Unable to set discounts.'));
            }
        }
        $boldOrder = $this->client->post($websiteId, 'process_order', []);
        if ($boldOrder->getErrors()) {
            throw new LocalizedException(__('Could not process order.'));
        }
    }
}
