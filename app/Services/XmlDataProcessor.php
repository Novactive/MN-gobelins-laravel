<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\Translation\t;

class XmlDataProcessor
{
    protected $moduleNamspace;


    public function __construct()
    {
        $this->moduleNamspace = config('services.zetcom.module_namespace');
    }

    /**
     * @param $itemData
     * @return array|false|\SimpleXMLElement[]|null
     */
    public function parseXml($itemData)
    {
        try {
            $parsedXml = new \SimpleXMLElement($itemData);
            $parsedXml->registerXPathNamespace('zetcom', $this->moduleNamspace);
            return $parsedXml->xpath('//zetcom:moduleItem');
        } catch (\Exception $e) {
            Log::error('Erreur lors du parsing XML: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param $moduleXml
     * @return array
     */
    public function processObjectsData($moduleXml)
    {
        $moduleItems = $this->parseXml($moduleXml);
        $objects = [];

        foreach ($moduleItems as $key => $item) {

            $isInvNumber = $this->isInvNumber(
                $this->extractValue($item,'//zetcom:vocabularyReference[@name="DenominationVoc"]/zetcom:vocabularyReferenceItem//@name')
            );
            $inventoryRoot = $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="Part1Txt"]/zetcom:value', $key);
            $diffusion = $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjInternetVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', $key);
            $dimensions = [
                'WidthNum' => $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="WidthNum"]/zetcom:value', $key),
                'HeightNum' => $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="HeightNum"]/zetcom:value', $key),
                'DepthNum' => $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DepthNum"]/zetcom:value', $key)
            ];

            $objects[] = [
                'id' => $this->extractValue($item, '//zetcom:systemField[@name="__id"]/zetcom:value', $key),
                'inventory_id' => $this->extractValue($item, '//zetcom:systemField[@name="__id"]/zetcom:value', $key),
                'inventory_root' => $inventoryRoot,
                'inventory_number' => $isInvNumber ? $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="Part2Txt"]/zetcom:value', $key) : 0,
                'inventory_suffix' => $isInvNumber ? $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="Part3Txt"]/zetcom:value', $key) : 0,
                'inventory_suffix2' => $isInvNumber ? $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="Part4Txt"]/zetcom:value', $key) : 0,
                'height_or_thickness' => $dimensions['HeightNum'],
                'length_or_diameter' => $dimensions['WidthNum'],
                'depth_or_width' => $dimensions['DepthNum'],
                'conception_year' => $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem/zetcom:virtualField[@name="PreviewVrt"]/zetcom:value', $key),
                'acquisition_origin' => $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjAcquisitionMethodVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', $key),
                'acquisition_date' => $this->formatDatesInString($this->extractValue($item,'//zetcom:dataField[@name="ObjAcquisitionDateDat"]/zetcom:value', $key)),
                'listed_as_historic_monument' => $this->extractValue($item, '//zetcom:vocabularyReference[@name="ObjClaTypeVoc"]/zetcom:vocabularyReferenceItem/@name', $key) == "Monuments historiques",
                'listed_on' => null,
                'category' => $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjCategoryVoc"]/zetcom:vocabularyReferenceItem/@name', $key),
                'denomination' => $this->extractValue($item, '//zetcom:vocabularyReference[@name="ObjClassificationVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', $key),
                'title_or_designation' => $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjObjectTitleGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="TitleTxt"]/zetcom:value', $key),
                'period_legacy_id' => $this->extractValue($item, '//zetcom:vocabularyReference[@name="ObjPeriodVoc"]/zetcom:vocabularyReferenceItem/@name', $key)
                    ?: $this->extractValue($item,'//zetcom:vocabularyReference[@name="PeriodVoc"]/zetcom:vocabularyReferenceItem/@name', $key),
                'period_name' => $this->extractValue($item, '//zetcom:vocabularyReference[@name="ObjPeriodVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', $key)
                    ?: $this->extractValue($item,'//zetcom:vocabularyReference[@name="PeriodVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', $key),
                'period_start_year' => $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateFromTxt"]/zetcom:value', $key),
                'period_end_year' => $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateToTxt"]/zetcom:value', $key),
                'product_type' => $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjCategoryOnlineVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', $key),
                'description' => $this->extractValue($item, '//zetcom:repeatableGroup[@name="ObjCurrentDescriptionGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DescriptionClb"]/zetcom:value', $key),
                'bibliography' => $this->extractValue($item,'//zetcom:moduleReference[@name="ObjLiteratureRef"]/zetcom:moduleReferenceItem/zetcom:formattedValue', $key) . "\n" .
                    $this->extractValue($item,'//zetcom:dataField[@name="ObjLiteratureClb"]/zetcom:value', $key),
                'created_at' => $this->extractValue($item,'//zetcom:systemField[@name="__created"]/zetcom:value', $key),
                'updated_at' => $this->extractValue($item,'//zetcom:systemField[@name="__lastModified"]/zetcom:value', $key),
                'style_legacy_id' => $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjStyleVoc"]/zetcom:vocabularyReferenceItem/@name', $key),
                'style_name' => $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjStyleVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', $key),
                'production_origin' => $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjCategoryVoc"]/zetcom:vocabularyReferenceItem/@name', $key),
                'is_publishable' => $this->isPublishable(
                    $diffusion,
                    '',
                    $inventoryRoot
                ),
                'publication_code' => null,
                'legacy_inventory_number' => null,
                'entry_mode_legacy_id' => $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjAcquisitionMethodVoc"]/zetcom:vocabularyReferenceItem/@name', $key),
                'entry_mode_name' => $this->extractValue($item,'//zetcom:vocabularyReference[@name="ObjAcquisitionMethodVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', $key),
                'legacy_updated_on' => null,
                'deleted_at' => null,
                'history' => stripos($diffusion, 'historique') !== false ? $this->extractValue($item,'//zetcom:repeatableGroup[@name="ObjHistoryGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="HistoryClb"]/zetcom:value', $key) : null,
                'authors' => $this->getMultiple($item, './zetcom:moduleReference[@name="ObjPerAssociationRef"]/zetcom:moduleReferenceItem/@uuid'),
                'images' => $this->getMultiple($item,'./zetcom:moduleReference[@name="ObjMultimediaRef"]/zetcom:moduleReferenceItem/@uuid'),
            ];
        }

        return $objects;
    }

    /**
     * @param $moduleXml
     * @return array
     */
    public function processPersonData($moduleXml)
    {
        $PersonItem = $this->parseXml($moduleXml)[0];

        $PersonItem->registerXPathNamespace('zetcom', $this->moduleNamspace);
        $personType = $this->extractValue($PersonItem, '//zetcom:vocabularyReference[@name="PerTypeVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue');
        $attrs = $PersonItem->attributes();

        return [
            'id' => (int) $attrs['id'],
            'legacy_id' => $this->extractValue($PersonItem,'//zetcom:dataField[@name="PerDataTransferTxt"]/zetcom:value'),
            'name' => $this->formatDatesInString($this->extractValue($PersonItem,'//zetcom:virtualField[@name="PerPersonVrt"]/zetcom:value')),
            'created_at' => $this->extractValue($PersonItem,'//zetcom:systemField[@name="__created"]/zetcom:value'),
            'updated_at' => $this->extractValue($PersonItem,'//zetcom:systemField[@name="__lastModified"]/zetcom:value'),
            'first_name' => $personType == 'Artist' ?
                $this->extractValue($PersonItem,'//zetcom:dataField[@name="PerForeNameTxt"]/zetcom:value') : null,
            'last_name' => $personType == 'Artist' ?
                $this->extractValue($PersonItem,'//zetcom:dataField[@name="PerSurNameTxt"]/zetcom:value') :
                $this->extractValue($PersonItem,'//zetcom:dataField[@name="PerNameTxt"]/zetcom:value'),
            'date_of_birth' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateFromDat"]/zetcom:value'),
            'year_of_birth' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateFromDat"]/zetcom:value'),
            'date_of_death' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateToDat"]/zetcom:value'),
            'year_of_death' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateToDat"]/zetcom:value'),
            'occupation' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerFunctionsGrp"]/zetcom:repeatableGroupItem/zetcom:vocabularyReference[@name="TypeVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
            'birthplace' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="PlaceBirthTxt"]/zetcom:value'),
            'deathplace' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="PlaceDeathTxt"]/zetcom:value'),
            'isni_uri' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerURLGrp"]/zetcom:repeatableGroupItem[zetcom:vocabularyReference[@name="TypeVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue="ISNI"]/zetcom:dataField[@name="AddressTxt"]/zetcom:value'),
            'biography' => $this->extractValue($PersonItem,'//zetcom:dataField[@name="PerNotesClb"]/zetcom:value')
        ];
    }

    /**
     * @param $xml
     * @param $xpath
     * @param $key
     * @return string|null
     */
    private function extractValue($xml, $xpath,$key = 0) {
        $xml->registerXPathNamespace('zetcom', $this->moduleNamspace);
        $result = $xml->xpath($xpath);
        return $result && isset($result[$key]) ? (string)$result[$key] : null;
    }

    /**
     * @param $objInvNumber
     * @return bool
     */
    private function isInvNumber($objInvNumber) {

        if ($objInvNumber === "objInvNumber") {
            return true;
        }
        return false;
    }

    /**
     * @param $xml
     * @param $xpath
     * @return array
     */
    private function getMultiple($xml, $xpath)
    {
        $items = $xml->xpath($xpath);
        $images = [];

        foreach ($items as $item) {
            $images[] = (int)$item['uuid'];
        }

        return $images;
    }

    /**
     * @param $diffusion
     * @param $domain
     * @param $invRoot
     * @return bool
     */
    private function isPublishable($diffusion, $domain, $invRoot)
    {
        if (stripos($diffusion, 'Publiable') === false) {
            return false;
        }

        $isCollectionMN = ($domain === 'collection du MN');

        $isIA = (strpos($invRoot, 'IA') === 0);

        return $isCollectionMN || $isIA;

    }

    /**
     * @param string|null $text
     * @return string
     */
    private function formatDatesInString(?string $text): string
    {
        return preg_replace_callback('/\b(\d{2})\/(\d{2})\/(\d{4})\b/', function ($matches) {
            $day = $matches[1];
            $month = (int) $matches[2];
            $year = $matches[3];

            $months = [
                1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
                5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
                9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
            ];

            return "$day " . $months[$month] . " $year";
        }, $text);
    }


    /**
     * @param $moduleXml
     * @return bool
     */
    public function isImagePublishable($moduleXml) {

        $multimediaItem = $this->parseXml($moduleXml)[0];
        $mulInternetVoc = $this->extractValue($multimediaItem, '//zetcom:vocabularyReference[@name="MulInternetVoc"]/zetcom:vocabularyReferenceItem/@name');

        if ($mulInternetVoc == "yes") {
            return true;
        }

        return false;
    }

    /**
     * @param $moduleXml
     * @return string|null
     */
    public function getPhotographer($moduleXml)
    {
        $multimediaItem = $this->parseXml($moduleXml)[0];

        return $this->extractValue($multimediaItem, '//zetcom:dataField[@name="MulPhotocreditTxt"]/zetcom:value') ?? null;
    }
}