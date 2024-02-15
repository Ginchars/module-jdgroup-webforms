<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Helper;

use Vaimo\JdgroupIntegrations\Model\Publishers\BasePublisher;
use Vaimo\JdgroupIntegrations\Observers\Customer\CustomerPublisher;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;

use function explode;

class Export
{
    const EXPORT_AVAILABLE = 'enable_sap_export';
    const EXPORT_FIELD_MAPPING = 'sap_export_payload_template';
    const FORM_TABLE = 'mm_webforms_form';

    /**
     * @param Context $context
     * @param Payload $payload
     * @param BasePublisher $publisher
     */
    public function __construct(
        protected Context $context,
        protected Payload $payload,
        protected BasePublisher $publisher,
        protected Json $json
    ) {
    }

    /**
     * @param int $formId
     *
     * @return bool
     */
    public function isExportEnabled(int $formId = 0): bool
    {
        $isEnabled = false;

        if ($formId) {
            $select = $this->context->getConnection()->select()
                ->from(self::FORM_TABLE, self::EXPORT_AVAILABLE)
                ->where('form_id = ?', $formId);

            $isEnabled = (bool)$this->context->getConnection()->fetchOne($select);
        }

        return $isEnabled;
    }

    /**
     * @param int $formId
     *
     * @return array
     */
    public function getFormExportFieldMapping(int $formId = 0): array
    {
        $formExportMapping = [];

        if ($formId) {
            $select = $this->context->getConnection()->select()
                ->from(self::FORM_TABLE, self::EXPORT_FIELD_MAPPING)
                ->where('form_id = ?', $formId);

            $formMapping = (string)$this->context->getConnection()->fetchOne($select);

            if ($formMapping !== "") {
                $formMapping = $this->json->unserialize($formMapping);

                foreach ($formMapping as $mappingData) {
                    $mapParts = explode(".", $mappingData['payload_field']);
                    $formExportMapping[$mapParts[0]][$mapParts[1]] =
                        $mappingData['form_field'];
                }
            }
        }

        return $formExportMapping;
    }

    /**
     * @param int $formId
     * @param array $formData
     *
     * @return array
     */
    public function prepareExportDataFromFormData(int $formId = 0, array $formData = []): array
    {
        $exportFieldMapping = $this->getFormExportFieldMapping($formId);

        foreach ($exportFieldMapping as $section => $fields) {
            foreach ($fields as $fieldKey => $fieldMapValue) {
                $exportFieldMapping[$section][$fieldKey] =
                    $formData['field_' . $fieldMapValue] ?? '';
            }
        }

        return $exportFieldMapping;
    }

    /**
     * @param int $formId
     * @param array $formData
     *
     * @return array
     * @throws LocalizedException
     */
    public function getExportData(int $formId = 0, array $formData = []): array
    {
        $preparedFormData = $this->prepareExportDataFromFormData($formId, $formData);

        return $this->payload->generateExportPayloadData($preparedFormData);
    }

    /**
     * @param int $formId
     * @param array $formData
     *
     * @return void
     * @throws LocalizedException
     */
    public function exportFormData(int $formId = 0, array $formData = []): void
    {
        if ($this->isExportEnabled($formId)) {
            $exportPayload = $this->getExportData($formId, $formData);

            $this->publisher->publish(CustomerPublisher::CUSTOMER_EXPORT_QUEUE, $exportPayload);
        }
    }
}
