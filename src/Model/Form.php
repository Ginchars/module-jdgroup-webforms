<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Model;

use MageMe\WebForms\Model\Form as BaseForm;

use function json_encode, json_decode, is_array;

class Form extends BaseForm
{
    /**
     * @return Form
     */
    public function beforeSave(): BaseForm
    {
        if (is_array($this->getData('sap_export_payload_template'))) {
            $this->setData(
                'sap_export_payload_template',
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
                json_encode($this->getData('sap_export_payload_template'))
            );
        }

        return parent::beforeSave();
    }

    /**
     * @return Form
     */
    public function afterLoad(): BaseForm
    {
        try {
            $this->setData(
                'sap_export_payload_template',
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
                json_decode((string)$this->getData('sap_export_payload_template'), true)
            );
        } catch (\Exception $e) {
            unset($e);
            $this->setData('sap_export_payload_template', null);
        }

        return parent::afterLoad();
    }
}
