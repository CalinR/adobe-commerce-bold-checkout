<?php
declare(strict_types=1);

namespace Bold\Checkout\Plugin\Product;

use Bold\Checkout\Model\Queue\Publisher\EntitySyncPublisher;
use Exception;
use Magento\Catalog\Model\Product\Website;
use Psr\Log\LoggerInterface;

/**
 * Publish product ids after add product to website.
 */
class AddWebsitePlugin
{
    public const TOPIC_NAME = 'bold.checkout.sync.products';

    /**
     * @var EntitySyncPublisher
     */
    private $publisher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EntitySyncPublisher $publisher
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntitySyncPublisher $publisher,
        LoggerInterface $logger
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * Publish product ids after add product to website.
     *
     * @param Website $subject
     * @param Website $result
     * @param array $websiteIds
     * @param array $productIds
     * @return Website
     */
    public function afterAddProducts(Website $subject, $result, $websiteIds, $productIds): Website
    {
        $websiteIds = array_map('intval', $websiteIds);
        $productIds = array_map('intval', $productIds);
        foreach ($websiteIds as $websiteId) {
            try {
                $this->publisher->publish(self::TOPIC_NAME, $websiteId, $productIds);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $result;
    }
}
