<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

class XmlDataProcessor
{
    protected $moduleNamspace;

    public function __construct()
    {
        $this->moduleNamspace = config('services.zetcom.module_namespace');
    }

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

    public function processObjectsData($moduleXml)
    {
        $moduleItems = $this->parseXml($moduleXml);
        $objects = [];

        foreach ($moduleItems as $key => $item) {
            $item->registerXPathNamespace('zetcom', $this->moduleNamspace);

            $isInvNumber = $this->isInvNumber(
                $this->extractValue($key, $item,'//zetcom:vocabularyReference[@name="DenominationVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue')
            );

            $objects[] = [
                'id' => $this->extractValue($key, $item, '//zetcom:systemField[@name="__id"]/zetcom:value'),
                'inventory_id' => $this->extractValue($key, $item, '//zetcom:systemField[@name="__id"]/zetcom:value'),
                'inventory_root' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="Part1Txt"]/zetcom:value'),
                'inventory_number' => $isInvNumber ? $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="Part2Txt"]/zetcom:value') : 0,
                'inventory_suffix' => $isInvNumber ? $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="Part3Txt"]/zetcom:value') : 0,
                'inventory_suffix2' => $isInvNumber ? $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="Part4Txt"]/zetcom:value') : 0,
                'height_or_thickness' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="HeightNum"]/zetcom:value'),
                'length_or_diameter' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="WidthNum"]/zetcom:value'),
                'depth_or_width' => $this->extractValue($key,$item,'//zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DepthNum"]/zetcom:value'),
                'conception_year' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem/zetcom:virtualField[@name="PreviewFRVrt"]/zetcom:value'),
                'acquisition_origin' => $this->extractValue($key,$item,'//zetcom:vocabularyReference[@name="ObjAcquisitionMethodVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'acquisition_date' => $this->extractValue($key,$item,'//zetcom:dataField[@name="ObjAcquisitionDateDat"]/zetcom:value'),
                'listed_as_historic_monument' => $this->extractValue($key,$item, '//zetcom:vocabularyReference[@name="ObjClaTypeVoc"]/zetcom:vocabularyReferenceItem/@name') == "Monuments historiques",
                'listed_on' => null,
                'category' => $this->extractValue($key, $item,'//zetcom:vocabularyReference[@name="ObjCategoryVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'denomination' => $this->extractValue($key, $item, '//zetcom:vocabularyReference[@name="ObjClassificationVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'title_or_designation' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjObjectTitleGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="TitleTxt"]/zetcom:value'),
                'period_id' => $this->extractValue($key, $item, '//zetcom:vocabularyReference[@name="ObjPeriodVoc"]/zetcom:vocabularyReferenceItem/@name')
                    ?: $this->extractValue($key, $item,'//zetcom:vocabularyReference[@name="PeriodVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'product_type_id' => null, //get it using the mapping
                'description' => $this->extractValue($key, $item, '//zetcom:repeatableGroup[@name="ObjCurrentDescriptionGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DescriptionClb"]/zetcom:value'),
                'bibliography' => $this->extractValue($key, $item,'//zetcom:moduleReference[@name="ObjLiteratureRef"]/zetcom:moduleReferenceItem/zetcom:formattedValue') . " " .
                    $this->extractValue($key, $item,'//zetcom:dataField[@name="ObjLiteratureClb"]/zetcom:value'),
                'created_at' => $this->extractValue($key, $item,'//zetcom:systemField[@name="__created"]/zetcom:value'),
                'updated_at' => $this->extractValue($key, $item,'//zetcom:systemField[@name="__lastModified"]/zetcom:value'),
                'style_id' => $this->extractValue($key, $item,'//zetcom:vocabularyReference[@name="ObjStyleVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'production_origin_id' => $this->extractValue($key, $item,'//zetcom:vocabularyReference[@name="ObjCategoryVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'is_published' => null,
                'publication_code' => $this->extractValue($key, $item,'//zetcom:vocabularyReference[@name="ObjInternetVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'legacy_inventory_number' => null,
                'entry_mode_id' => $this->extractValue($key, $item,'//zetcom:vocabularyReference[@name="ObjAcquisitionMethodVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'legacy_updated_on' => null,
                'deleted_at' => null,
                'history' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="ObjHistoryGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="HistoryClb"]/zetcom:value'),
                'author' => $this->extractValue($key, $item, '//zetcom:moduleReference[@name="ObjPerAssociationRef"]/zetcom:moduleReferenceItem/@uuid'),
                'images' => $this->getImages($item,'./zetcom:moduleReference[@name="ObjMultimediaRef"]/zetcom:moduleReferenceItem/@uuid'),
            ];
        }

        return $objects;
    }

    public function processPersonsData($moduleXml)
    {
        $moduleItems = $this->parseXml($moduleXml);
        $persons = [];
        foreach ($moduleItems as $key => $item) {
            $item->registerXPathNamespace('zetcom', $this->moduleNamspace);
            $personType = $this->extractValue($key, $item, '//zetcom:vocabularyReference[@name="PerTypeVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue');
            $attrs = $item->attributes();

            $persons[] = [
                'id' => (int) $attrs['id'],
                'legacy_id' => (int) $attrs['id'],
                'name' => $personType == 'Artist' ?
                    trim($this->extractValue($key, $item,'//zetcom:dataField[@name="PerForeNameTxt"]/zetcom:value') . ' ' .
                        $this->extractValue($key, $item,'//zetcom:dataField[@name="PerSurNameTxt"]/zetcom:value')) :
                    $this->extractValue($key, $item,'//zetcom:dataField[@name="PerNameTxt"]/zetcom:value'),
                'created_at' => $this->extractValue($key, $item,'//zetcom:systemField[@name="__created"]/zetcom:value'),
                'updated_at' => $this->extractValue($key, $item,'//zetcom:systemField[@name="__lastModified"]/zetcom:value'),
                'first_name' => $personType == 'Artist' ?
                    $this->extractValue($key, $item,'//zetcom:dataField[@name="PerForeNameTxt"]/zetcom:value') : null,
                'last_name' => $personType == 'Artist' ?
                    $this->extractValue($key, $item,'//zetcom:dataField[@name="PerSurNameTxt"]/zetcom:value') :
                    $this->extractValue($key, $item,'//zetcom:dataField[@name="PerNameTxt"]/zetcom:value'),
                'date_of_birth' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateFromDat"]/zetcom:value'),
                'year_of_birth' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateFromDat"]/zetcom:value'),
                'date_of_death' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateToDat"]/zetcom:value'),
                'year_of_death' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateToDat"]/zetcom:value'),
                'occupation' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="PerFunctionsGrp"]/zetcom:repeatableGroupItem/zetcom:vocabularyReference[@name="TypeVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'birthplace' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="PlaceBirthTxt"]/zetcom:value'),
                'deathplace' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="PerDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="PlaceDeathTxt"]/zetcom:value'),
                'isni_uri' => $this->extractValue($key, $item,'//zetcom:repeatableGroup[@name="PerURLGrp"]/zetcom:repeatableGroupItem[zetcom:vocabularyReference[@name="TypeVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue="ISNI"]/zetcom:dataField[@name="AddressTxt"]/zetcom:value'),
                'biography' => $this->extractValue($key, $item,'//zetcom:dataField[@name="PerNotesClb"]/zetcom:value')
            ];
        }

        return $persons;
    }

    private function extractValue($key, $xml, $xpath) {
        $result = $xml->xpath($xpath);
        return $result && isset($result[$key]) ? (string)$result[$key] : null;
    }

    private function isInvNumber($objInvNumber) {

        if ($objInvNumber === "Accession number") {
            return true;
        }
        return false;
    }

    private function getImages($xml, $xpath)
    {
        $items = $xml->xpath($xpath);
        $images = [];

        foreach ($items as $item) {
            $images[] = (int)$item['uuid'];
        }

        return $images;
    }
}