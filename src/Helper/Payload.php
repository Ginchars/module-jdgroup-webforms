<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Helper;

use Vaimo\JdgroupIntegrations\Helper\CustomerHelper;
use Magento\Framework\Exception\LocalizedException;

use function array_replace_recursive, strtoupper, substr;

class Payload
{
    const DEFAULT_PARTNER_NUMBER = '0000000000';
    const DEFAULT_COUNTRY = 'ZA';
    const DEFAULT_EXPORT_PAYLOAD = [
        'PartnerDetails' => [
            'PartnerNumber' => self::DEFAULT_PARTNER_NUMBER,
            'Title' => '',
            'Initials' => '',
            'FirstName' => '',
            'SurName' => '',
            'IdNumber' => '',
            'IdType' => 'ZSAID',
            'IdCountry' => self::DEFAULT_COUNTRY,
            'CitizenShip' => self::DEFAULT_COUNTRY,
            'Gender' => '',
            'VatNumber' => '',
            'CompanyName' => '',
            'CoRegNumber' => '',
            'RelationshipTo' => ''
        ],
        'PartnerContactDetail' => [
            'HomePhone' => '',
            'WorkPhone' => '',
            'CellPhone' => '',
            'AlternativeNumber' => '',
            'EMail' => '',
            'AlternativeEMail' => ''
        ],
        'PartnerAddress' => [
            'AddressType' => 'ZDELIVERY',
            'Building' => '',
            'ComplexName' => '',
            'StreetName' => '',
            'StreetNumber' => '',
            'Suburb' => '',
            'City' => '',
            'Province' => '',
            'PostalCode' => '',
            'Country' => self::DEFAULT_COUNTRY,
            'Longitude' => '',
            'Latitude' => ''
        ],
        'MarketingAttributes' => [
            [
                'MarketingConsent' => 'No',
                'MarketingCorrMethod' => 'Email'
            ],
            [
                'MarketingConsent' => 'No',
                'MarketingCorrMethod' => 'SMS'
            ]
        ],
        'ReferenceDetails' => [
            'Site' => ''
        ]
    ];

    public function __construct(
        protected Data $dataHelper,
        protected CustomerHelper $customerHelper
    ) {
    }

    /**
     * @param array $preparedFormData
     *
     * @return array
     * @throws LocalizedException
     */
    public function generateExportPayloadData(array $preparedFormData = []): array
    {
        $payloadData = array_replace_recursive(self::DEFAULT_EXPORT_PAYLOAD, $preparedFormData);
        $payloadData['MarketingAttributes'] = self::DEFAULT_EXPORT_PAYLOAD['MarketingAttributes'];

        if (isset($preparedFormData['MarketingAttributes'])
            && $preparedFormData['MarketingAttributes']['MarketingConsent'] !== ""
        ) {
            foreach ($payloadData['MarketingAttributes'] as $key => $marketingAttribute) {
                $payloadData['MarketingAttributes'][$key]['MarketingConsent'] = 'Yes';
            }
        }

        $this->updatePayloadData($payloadData);

        if (!isset($preparedFormData['PartnerAddress'])) {
            unset($payloadData['PartnerAddress']);
        }

        return $payloadData;
    }

    /**
     * @param array $payloadData
     *
     * @return void
     * @throws LocalizedException
     */
    public function updatePayloadData(array &$payloadData = []): void
    {
        // PartnerDetails update
        $idNumber = $payloadData['PartnerDetails']['IdNumber'];
        $gender = $this->customerHelper->getCustomerGender($idNumber);
        $title = $gender === "M" ? 'Mr' : 'Ms';
        $initials = strtoupper(
            substr($payloadData['PartnerDetails']['FirstName'], 0, 1)
        );
        $payloadData['PartnerDetails']['Title'] = $title;
        $payloadData['PartnerDetails']['Initials'] = $initials;
        $payloadData['PartnerDetails']['Gender'] = $gender;

        //Partner Contact Details update
        $streetName = $payloadData['PartnerAddress']['StreetName'];
        $region = $payloadData['PartnerAddress']['Province'];
        $streetNumber = $this->dataHelper->getStreetNumberFromStreetAddress($streetName);
        $regionCode = $this->dataHelper->getRegionCodeFromRegionName($region);
        $payloadData['PartnerAddress']['StreetNumber'] = $streetNumber;
        $payloadData['PartnerAddress']['Province'] = $regionCode;
    }
}
