<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Ui\Component;

use Magento\Framework\Data\OptionSourceInterface;
use Vaimo\JdgroupWebforms\Helper\Payload;

class PayloadFields implements OptionSourceInterface
{
    /**
     * @var \array[][]
     */
    protected array $options;

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if (empty($this->options)) {
            $this->options[] = [
                'value' => 0,
                'label' => __('-- Select payload field --')
            ];
            foreach (Payload::DEFAULT_EXPORT_PAYLOAD as $group => $fields) {
                $groupValues = [];

                // phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
                foreach ($fields as $field => $value) {
                    if ($group !== "MarketingAttributes") {
                        $groupValues[] = [
                            'value' => $group . '.' . $field,
                            'label' => $group . ' | ' . $field
                        ];
                    }
                }

                if ($group !== "MarketingAttributes") {
                    $this->options[] = [
                        'label' => $group,
                        'value' => $groupValues
                    ];
                }
            }
            //special case for marketing attributes
            $this->options[] = [
                'label' => 'MarketingAttributes',
                'value' => [
                    [
                        'value' => 'MarketingAttributes.MarketingConsent',
                        'label' => 'MarketingAttributes | MarketingConsent'
                    ]
                ]
            ];
        }

        return $this->options;
    }
}
