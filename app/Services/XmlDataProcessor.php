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
     * Get the module namespace
     *
     * @return string
     */
    public function getModuleNamespace(): string
    {
        return $this->moduleNamspace;
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

        foreach ($moduleItems as $item) {

            $inventoryRoot = $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem[zetcom:vocabularyReference[@name="DenominationVoc"]/zetcom:vocabularyReferenceItem[@name="objInvNumber"] and zetcom:dataField[@name="Part2Txt"]]/zetcom:dataField[@name="Part1Txt"]/zetcom:value');
            $diffusion = $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjInternetVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue');
            $dimensions = [
                'WidthNum' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem[zetcom:dataField[@name="SortLnu"]/zetcom:value="1"]/zetcom:dataField[@name="WidthNum"]/zetcom:value'),
                'HeightNum' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem[zetcom:dataField[@name="SortLnu"]/zetcom:value="1"]/zetcom:dataField[@name="HeightNum"]/zetcom:value'),
                'DepthNum' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem[zetcom:dataField[@name="SortLnu"]/zetcom:value="1"]/zetcom:dataField[@name="DepthNum"]/zetcom:value')
            ];

            preg_match(
                '/\((.*?)\)/',
                $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDimAllGrp"]/zetcom:repeatableGroupItem/zetcom:moduleReference[@name="TypeDimRef"]/zetcom:moduleReferenceItem/zetcom:formattedValue'),
                $matches
            );

            $objects[] = [
                'id' => $this->extractValue($item, './zetcom:systemField[@name="__id"]/zetcom:value'),
                'inventory_root' => $inventoryRoot,
                'inventory_number' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem[zetcom:vocabularyReference[@name="DenominationVoc"]/zetcom:vocabularyReferenceItem[@name="objInvNumber"]]/zetcom:dataField[@name="Part2Txt"]/zetcom:value') ?: 0,
                'inventory_suffix' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem[zetcom:vocabularyReference[@name="DenominationVoc"]/zetcom:vocabularyReferenceItem[@name="objInvNumber"]]/zetcom:dataField[@name="Part3Txt"]/zetcom:value') ?: 0,
                'inventory_suffix2' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem[zetcom:vocabularyReference[@name="DenominationVoc"]/zetcom:vocabularyReferenceItem[@name="objInvNumber"]]/zetcom:dataField[@name="Part4Txt"]/zetcom:value') ?: 0,
                'height_or_thickness' => $dimensions['HeightNum'],
                'length_or_diameter' => $dimensions['WidthNum'],
                'depth_or_width' => $dimensions['DepthNum'],
                'conception_year' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem[zetcom:dataField[@name="SortLnu"]/zetcom:value="1"]/zetcom:virtualField[@name="PreviewVrt"]/zetcom:value'),
                'acquisition_origin' => $diffusion === 'Publiable + description + historique + origine détail' ? $this->extractValue($item,'./zetcom:dataField[@name="ObjAcquisitionNotesClb"]/zetcom:value') : '',
                'acquisition_date' => $this->formatDatesInString($this->extractValue($item,'./zetcom:dataField[@name="ObjAcquisitionDateDat"]/zetcom:value')) ,
                'listed_as_historic_monument' => $this->extractValue($item, './zetcom:vocabularyReference[@name="ObjClaTypeVoc"]/zetcom:vocabularyReferenceItem/@name') == "Monuments historiques",
                'category' => $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjCategoryVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'denomination' => $this->extractValue($item, './zetcom:vocabularyReference[@name="ObjClassificationVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'title_or_designation' => implode("\n", $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjObjectTitleGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="TitleTxt"]/zetcom:value', true) ?? []),
                'period_legacy_id' => $this->extractValue($item, './/zetcom:vocabularyReference[@name="ObjPeriodVoc"]/zetcom:vocabularyReferenceItem/@name')
                    ?: $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem/zetcom:vocabularyReference[@name="PeriodVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'period_name' => $this->extractValue($item, './/zetcom:vocabularyReference[@name="ObjPeriodVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue')
                    ?: $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem/zetcom:vocabularyReference[@name="PeriodVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'period_start_year' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateFromTxt"]/zetcom:value'),
                'period_end_year' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjDateGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DateToTxt"]/zetcom:value'),
                'product_type' => $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjCategoryOnlineVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'description' => stripos($diffusion, 'description') !== false ? $this->extractValue($item, './zetcom:repeatableGroup[@name="ObjCurrentDescriptionGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="DescriptionClb"]/zetcom:value') : '',
                'obj_literature_ref' => $this->extractLiteratureReferencesWithPages($item),
                'obj_literature_clb' => $this->extractValue($item,'./zetcom:dataField[@name="ObjLiteratureClb"]/zetcom:value'),
                'created_at' => $this->extractValue($item,'./zetcom:systemField[@name="__created"]/zetcom:value'),
                'updated_at' => $this->extractValue($item,'./zetcom:systemField[@name="__lastModified"]/zetcom:value'),
                'style_legacy_id' => $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjStyleVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'style_name' => $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjStyleVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'production_origin' => $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjCategoryVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'is_publishable' => $this->isPublishable(
                    $diffusion,
                    $this->extractValue($item, './zetcom:systemField[@name="__orgUnit"]/zetcom:value'),
                    $inventoryRoot
                ),
                'publication_code' => null,
                'legacy_inventory_number' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjObjectNumberGrp"]/zetcom:repeatableGroupItem[zetcom:vocabularyReference[@name="DenominationVoc"]/zetcom:vocabularyReferenceItem[@name="old"]]/zetcom:virtualField[@name="NumberVrt"]/zetcom:value') ."\n" .
                    implode("\n", $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjInscriptionGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="TransliterationClb"]/zetcom:value', true) ?? []),
                'entry_mode_legacy_id' => $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjAcquisitionMethodVoc"]/zetcom:vocabularyReferenceItem/@name'),
                'entry_mode_name' => $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjAcquisitionMethodVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'history' => stripos($diffusion, 'historique') !== false ? $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjHistoryGrp"]/zetcom:repeatableGroupItem/zetcom:dataField[@name="HistoryClb"]/zetcom:value') : null,
                'mat_tech' => $this->extractValue($item,'./zetcom:repeatableGroup[@name="ObjMaterialTechniqueGrp"]/zetcom:repeatableGroupItem/zetcom:vocabularyReference[@name="MatTechVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue', true) ?? [],
                'obj_garn' => $this->extractValue($item,'./zetcom:vocabularyReference[@name="ObjGarnVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue'),
                'conservation' => $this->extractValue($item, './zetcom:moduleReference[@name="ObjConservationRef"]/zetcom:moduleReferenceItem/@moduleItemId', true) ?? [],
                'obj_new_trim_dpl' => $this->extractValue($item,'./zetcom:virtualField[@name="ObjNewTrimVrt"]/zetcom:value'),
                'authors' => $this->extractValue($item, './zetcom:moduleReference[@name="ObjPerAssociationRef"]/zetcom:moduleReferenceItem/@moduleItemId', true) ?? [],
                'images' => $this->extractValue($item,'./zetcom:moduleReference[@name="ObjMultimediaRef"]/zetcom:moduleReferenceItem/@moduleItemId', true) ?? [],
                'dim_order' => $matches[1] ?? "Height x Width x Depth"
            ];
        }

        return $objects;
    }

    /**
     * @param $moduleXml
     * @return array
     */
    public function processPersonData($moduleXml, int $productId)
    {
        $parsedXml = $this->parseXml($moduleXml);
        
        if (empty($parsedXml)) {
            Log::error("Aucun élément XML trouvé pour processPersonData avec productId: $productId");
            return null;
        }
        
        $PersonItem = $parsedXml[0];

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
            'biography' => $this->extractValue($PersonItem,'//zetcom:dataField[@name="PerNotesClb"]/zetcom:value'),
            'right_type' => $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerRightsGrp"]/zetcom:repeatableGroupItem/zetcom:vocabularyReference[@name="TypeVoc" and @id="'.$productId.'"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue')
                ?? $this->extractValue($PersonItem,'//zetcom:repeatableGroup[@name="PerRightsGrp"]/zetcom:repeatableGroupItem/zetcom:vocabularyReference[@name="TypeVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue')
        ];
    }

    /**
     * @param $moduleXml
     * @return array
     */
    public function processMultimediaData($moduleXml): array
    {
        $parsedXml = $this->parseXml($moduleXml);
        
        if (empty($parsedXml)) {
            Log::error("Aucun élément XML trouvé pour processMultimediaData");
            return [];
        }
        
        $multimediaItem = $parsedXml[0];
        $communication = $this->extractValue($multimediaItem, '//zetcom:vocabularyReference[@name="MulCommunicationVoc"]/zetcom:vocabularyReferenceItem/zetcom:formattedValue');

        return [
            'photographer' => $this->extractValue($multimediaItem, '//zetcom:dataField[@name="MulPhotocreditTxt"]/zetcom:value') ?? null,
            'is_poster' => $this->extractValue($multimediaItem, '//zetcom:dataField[@name="ThumbnailBoo"]/zetcom:value'),
            'is_prime_quality' => $communication === 'Qualité publication',
            'is_documentation_quality' => $communication === 'Documentaire',
            'has_marking' => $communication === 'Marquages',
            'update_date' => $this->extractValue($multimediaItem, '//zetcom:dataField[@name="MulDateTxt"]/zetcom:value'),
        ];
    }

    /**
     * @param $moduleXml
     * @return array|string|null
     */
    public function processConservationData($moduleXml)
    {
        $parsedXml = $this->parseXml($moduleXml);
        
        if (empty($parsedXml)) {
            Log::error("Aucun élément XML trouvé pour processConservationData");
            return null;
        }
        
        $multimediaItem = $parsedXml[0];

        return $this->extractValue($multimediaItem, '//zetcom:vocabularyReference[@name="ConCoveringVoc"]/zetcom:vocabularyReferenceItem/@name') ?? null;
    }


    /**
     * @param $xml
     * @param $xpath
     * @param int $key
     * @param bool $isMultiVal
     * @return array|string|null
     */
    private function extractValue($xml, $xpath, bool $isMultiVal = false) {
        $xml->registerXPathNamespace('zetcom', $this->moduleNamspace);
        $results = $xml->xpath($xpath);
        if ($results && $isMultiVal) {
            $values = [];
            foreach ($results as $result) {
                $values[] = (string)$result;
            }
            return array_unique($values);
        }
        return $results && isset($results[0]) ? (string)$results[0] : null;
    }

    /**
     * @param $diffusion
     * @param $domain
     * @param $invRoot
     * @return bool
     */
    private function isPublishable($diffusion, $orgUnit, $invRoot)
    {
        if (stripos($diffusion, 'Non publiable') !== false || $diffusion === 'À définir') {
            return false;
        }

        $isCollectionMN = ($orgUnit === 'ObjectOrgUnit');

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
        $parsedXml = $this->parseXml($moduleXml);
        
        if (empty($parsedXml)) {
            Log::error("Aucun élément XML trouvé pour isImagePublishable");
            return false;
        }
        
        $multimediaItem = $parsedXml[0];
        $mulInternetVoc = $this->extractValue($multimediaItem, '//zetcom:vocabularyReference[@name="MulInternetVoc"]/zetcom:vocabularyReferenceItem/@name');

        if ($mulInternetVoc == "yes") {
            return true;
        }

        return false;
    }

    /**
     * @param $moduleXml
     * @return array|string
     */
    public function getLiteratureItem($moduleXml)
    {
        $parsedXml = $this->parseXml($moduleXml);
        
        if (empty($parsedXml)) {
            Log::error("Aucun élément XML trouvé pour getLiteratureItem");
            return "";
        }
        
        $LiteratureItem = $parsedXml[0];

        return $this->extractValue($LiteratureItem, '//zetcom:dataField[@name="LitCitationClb"]/zetcom:value') ?? "";
    }

    /**
     * Extract literature references with their corresponding page references
     * @param \SimpleXMLElement $item
     * @return array
     */
    private function extractLiteratureReferencesWithPages($item)
    {
        $literatureRefs = [];
        // Get all moduleReferenceItem elements for ObjLiteratureRef
        $moduleRefItems = $item->xpath('./zetcom:moduleReference[@name="ObjLiteratureRef"]/zetcom:moduleReferenceItem');
        
        foreach ($moduleRefItems as $moduleRefItem) {
            $moduleItemId = (string)$moduleRefItem['moduleItemId'];
            $pageRefTxt = $this->extractValue($moduleRefItem, './zetcom:dataField[@name="PageRefTxt"]/zetcom:value');
            
            $literatureRefs[] = [
                'id' => $moduleItemId,
                'page' => $pageRefTxt
            ];
        }

        return $literatureRefs;
    }
}