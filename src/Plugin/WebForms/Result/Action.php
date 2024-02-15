<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Plugin\WebForms\Result;

use MageMe\WebForms\Ui\Component\Result\Listing\Column\Action as Subject;
use MageMe\WebForms\Api\Data\ResultInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\AuthorizationInterface;
use Vaimo\JdgroupWebforms\Helper\{Export, Context};

class Action
{
    /**
     * @param Export $export
     * @param Context $context
     * @param UrlInterface $url
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        protected Export $export,
        protected Context $context,
        protected UrlInterface $url,
        protected AuthorizationInterface $authorization
    ) {
    }

    /**
     * @param Subject $subject
     * @param array $result
     * @param $item
     *
     * @return array
     */
    public function afterPrepareItem(Subject $subject, array $result, $item): array
    {
        if ($this->export->isExportEnabled((int)$item[ResultInterface::FORM_ID])
            && $this->authorization->isAllowed('MageMe_WebForms::export_form_data')
        ) {
            $result[$subject->getData('name')]['download_export'] = [
                'href' => $this->url->getUrl(
                    'jdgwebforms/export/dataexport',
                    [
                        ResultInterface::ID => $result[ResultInterface::ID],
                        ResultInterface::FORM_ID => $result[ResultInterface::FORM_ID],
                        'download' => 'true'
                    ]
                ),
                'label' => __('Fetch customer export'),
                'hidden' => false,
                'sortOrder' => 99
            ];
            $result[$subject->getData('name')]['customer_export'] = [
                'href' => $this->url->getUrl(
                    'jdgwebforms/export/dataexport',
                    [
                        ResultInterface::ID => $result[ResultInterface::ID],
                        ResultInterface::FORM_ID => $result[ResultInterface::FORM_ID],
                        'download' => 'false'
                    ]
                ),
                'label' => __('Export customer data'),
                'hidden' => false,
                'sortOrder' => 100
            ];
        }

        return $result;
    }
}
