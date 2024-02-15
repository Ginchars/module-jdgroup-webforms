<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Helper;

use function preg_match, array_shift, iconv, preg_replace, str_replace, trim;

class Data
{
    public function __construct(
        protected Context $context
    ) {
    }

    /**
     * @param string $regionName
     * @param string $countryCode
     *
     * @return string
     */
    public function getRegionCodeFromRegionName(string $regionName = "", string $countryCode = "ZA"): string
    {
        $select = $this->context->getConnection()->select()
            ->from('directory_country_region', 'code')
            ->where('country_id = ?', $countryCode)
            ->where('default_name = ?', $regionName);

        return (string)$this->context->getConnection()->fetchOne($select);
    }

    /**
     * @param string $streetAddress
     *
     * @return string
     */
    public function getStreetNumberFromStreetAddress(string $streetAddress = ""): string
    {
        $streetNumber = "";

        if (preg_match('/(\d+)/', $streetAddress, $matches)) {
            $streetNumber = array_shift($matches);
        }

        return $streetNumber;
    }

    /**
     * @param string $value
     * @param bool $latinOnly
     * @param bool $removeSpaces
     * @param string $regEx
     *
     * @return string
     */
    public function cleanValue(
        string $value = "",
        bool $latinOnly = true,
        bool $removeSpaces = false,
        string $regEx = '/[^a-zA-Z0-9\s\-]/'
    ): string {
        if ($latinOnly) {
            $value = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $value);
        }

        $cleanValue = preg_replace($regEx, "", trim($value));

        if ($removeSpaces) {
            $cleanValue = str_replace(" ", "", (string)$cleanValue);
        }

        return $cleanValue;
    }
}
