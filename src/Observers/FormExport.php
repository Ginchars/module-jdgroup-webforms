<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Observers;

use Vaimo\JdgroupWebforms\Helper\Export;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class FormExport implements ObserverInterface
{
    /**
     * @param Export $export
     */
    public function __construct(
        protected Export $export
    ) {
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $resultObject = $observer->getEvent()
            ->getResult();

        if ($resultObject) {
            $this->export->exportFormData((int)$resultObject->getFormId(), $resultObject->getData());
        }
    }
}
