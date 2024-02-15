<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Controller\Adminhtml\Export;

use Magento\Backend\App\{Action\Context, Action};
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Vaimo\JdgroupWebforms\Helper\Export;
use MageMe\WebForms\Api\ResultRepositoryInterface;

use function print_r;

class Dataexport extends Action
{
    public const ADMIN_RESOURCE = 'MageMe_WebForms::export_form_data';

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Export $export
     * @param ResultRepositoryInterface $resultRepository
     */
    public function __construct(
        Context $context,
        protected FileFactory $fileFactory,
        protected Export $export,
        protected ResultRepositoryInterface $resultRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
     */
    public function execute()
    {
        $resultId = (int)$this->getRequest()->getParam('result_id', 0);
        $formId = (int)$this->getRequest()->getParam('form_id', 0);
        $isDownload = (string)$this->getRequest()->getParam('download', 'false');
        $isDownload = !($isDownload === "false");

        if ($resultId && $formId) {
            try {
                $results = $this->resultRepository->getById($resultId);
                if ($isDownload) {
                    $exportData = $this->export->getExportData((int)$results->getFormId(), $results->getData());
                    $fileName = $results->getFormId() . '_' . $results->getId() . '_payload.txt';

                    return $this->fileFactory->create($fileName, [
                        'type' => 'string',
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                        'value' => print_r($exportData, true),
                        'rm' => true
                    ]);
                }

                $this->export->exportFormData((int)$results->getFormId(), $results->getData());
                $this->messageManager->addSuccessMessage(
                    __('Exported form: %1, result: %2', $results->getForm()->getName(), $results->getId())
                );
            } catch (\Exception $e) {
                $this->messageManager
                    ->addErrorMessage(__('Error while getting export data %1', $e->getMessage()));
            }
        }

        return $this->_redirect('webforms/result/index', ['form_id' => $formId]);
    }
}
