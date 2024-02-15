<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Ui\Component;

use Vaimo\JdgroupWebforms\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

class FormFields implements OptionSourceInterface
{
    /**
     * @var \array[][]
     */
    protected array $options;

    /**
     * @param RequestInterface $request
     * @param Context $context
     */
    public function __construct(
        protected RequestInterface $request,
        protected Context $context
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if (empty($this->options)) {
            $this->options[] = [
                'value' => '0',
                'label' => __('-- Please Select Field --')
            ];
            $formId = (int)$this->request->getParam('form_id', 0);
            $fieldList = $this->getFormFieldList($formId);

            foreach ($fieldList as $item) {
                $this->options[] = [
                    'value' => $item['field_id'],
                    'label' => $item['name']
                ];
            }
        }

        return $this->options;
    }

    /**
     * @param int $formId
     *
     * @return array
     */
    protected function getFormFieldList(int $formId): array
    {
        $select = $this->context->getConnection()->select()
            ->from('mm_webforms_field', ['field_id', 'name', 'code'])
            ->where('form_id = ?', $formId)
            ->order('position ASC');

        return $this->context->getConnection()->fetchAll($select);
    }
}
