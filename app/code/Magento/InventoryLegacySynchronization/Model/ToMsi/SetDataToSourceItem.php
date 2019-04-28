<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLegacySynchronization\Model\ToMsi;

use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryLegacySynchronization\Model\GetDefaultSourceItemsBySkus;
use Magento\InventoryLegacySynchronization\Model\GetLegacyStockItemsByProductIds;
use Magento\InventoryLegacySynchronization\Model\ResourceModel\UpdateSourceItemsData;

/**
 * Copy legacy stock item information to MSI source items
 */
class SetDataToSourceItem
{
    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var \Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var \Magento\InventoryCatalog\Model\GetDefaultSourceItemBySku
     */
    private $getDefaultSourceItemBySku;

    /**
     * @var \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var \Magento\InventoryApi\Api\SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    public function __construct(
        DefaultSourceProviderInterface $defaultSourceProvider,
        \Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface $getSkusByProductIds,
        \Magento\InventoryCatalog\Model\GetDefaultSourceItemBySku $getDefaultSourceItemBySku,
        \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory $sourceItemFactory,
        \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemsSave
    ) {
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getDefaultSourceItemBySku = $getDefaultSourceItemBySku;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    /**
     * @param array $legacyItemsData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function execute(array $legacyItemsData): void
    {
        // Temporary solution to optimize memory usage.
        $legacyItemData = $legacyItemsData[0];
        $legacyItemProductId = $legacyItemData[StockItemInterface::PRODUCT_ID];

        $productSku = $this->getSkusByProductIds->execute([$legacyItemProductId])[$legacyItemProductId];
        $sourceItem = $this->getDefaultSourceItemBySku->execute($productSku);

        if ($sourceItem === null) {
            /** @var SourceItemInterface $sourceItem */
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSourceCode($this->defaultSourceProvider->getCode());
            $sourceItem->setSku($productSku);
        }

        $sourceItem->setQuantity((float)$legacyItemData[StockItemInterface::QTY]);
        $sourceItem->setStatus((int)$legacyItemData[StockItemInterface::IS_IN_STOCK]);

        $this->sourceItemsSave->execute([$sourceItem]);
    }
}
