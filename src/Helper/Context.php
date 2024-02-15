<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class Context
{
    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        protected ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @return AdapterInterface
     */
    public function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * @return ResourceConnection
     */
    public function getResourceConnection(): ResourceConnection
    {
        return $this->resourceConnection;
    }
}
