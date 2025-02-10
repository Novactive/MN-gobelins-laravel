<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

class ZetcomService
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = config('services.zetcom.base_url');
        $this->username = config('services.zetcom.username');
        $this->password = config('services.zetcom.password');
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @param array $headers
     * @return Response
     * @throws Exception
     */
    private function callEndpoint(string $method, string $endpoint, array $params = [], array $headers = [])
    {
        $headers = $headers ? : [ 'Content-Type' => 'application/xml' ];
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders($headers)
                ->send($method, $this->baseUrl . $endpoint, $params);

            if ($response->failed()) {
                Log::error('ZETCOM API Request Failed', [
                    'url' => $this->baseUrl . $endpoint,
                    'method' => $method,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('API request failed with status ' . $response->status());
            }

            return $response;
        } catch (Exception $e) {
            Log::error('ZETCOM API Exception', [
                'message' => $e->getMessage(),
                'endpoint' => $this->baseUrl . $endpoint,
                'method' => $method,
                'params' => $params,
                'headers' => $headers,
            ]);
            throw $e;
        }
    }

    /**
     * @param string $moduleName
     * @param int $moduleRecordId
     * @return string
     * @throws Exception
     */
    public function getSingleModule(string $moduleName, int $moduleRecordId)
    {
        return $this->callEndpoint('get', "/module/$moduleName/$moduleRecordId")->body();
    }

    /**
     * @param string $moduleName
     * @param $startDate
     * @return string
     * @throws Exception
     */
    public function getModifiedModules(string $moduleName, $startDate = null) {
        $startDate = $startDate ?? now()->subDay()->setTime(2, 0, 0);

        $requestXml = $this->buildSearchRequestXml(
            $moduleName,
            ['__lastModified' => ['operator' => 'greaterThanOrEqual', 'value' => $startDate->toIso8601String()]]
        );

        return $this->callEndpoint('post', "/module/$moduleName/search", ['body' => $requestXml])->body();
    }

    /**
     * @param string $moduleName
     * @return string
     * @throws Exception
     */
    public function getModuleItems(string $moduleName)
    {
        $requestXml = $this->buildSearchRequestXml($moduleName);

        return $this->callEndpoint('post', "/module/$moduleName/search", ['body' => $requestXml])->body();
    }

    public function getImage($id)
    {
        $response = $this->callEndpoint('get', "/module/Multimedia/$id/attachment", [
            'headers' => [
                'Accept' => 'application/octet-stream'
            ]
        ]);

        $fileName = null;
        if ($response->hasHeader('Content-Disposition')) {
            $contentDisposition = $response->getHeader('Content-Disposition')[0];
            if (preg_match('/filename=(.+)/', $contentDisposition, $matches)) {
                $fileName = $matches[1];
            }
        }

        $filePath = storage_path('app/public/' . $fileName);
        $isSaved= file_put_contents($filePath, $response->body());

        if ($isSaved === false || !file_exists($filePath)) {
            Log::error("L'image n'a pas pu être enregistrée sur le chemin : $filePath");
            throw new \Exception("L'image n'a pas pu être enregistrée sur le chemin : $filePath");
        }

        if (!@getimagesize($filePath)) {
            unlink($filePath);
            Log::error("Le fichier enregistré n'est pas une image valide : $filePath");
            throw new \Exception("Le fichier enregistré n'est pas une image valide : $filePath");
        }

        return $fileName;
    }

    /**
     * @param string $moduleName
     * @param array $expertConditions
     * @return string
     */
    private function buildSearchRequestXml(string $moduleName, array $expertConditions = []): string
    {
        $xmlNamespaces = [
            'xmlns' => "http://www.zetcom.com/ria/ws/module/search",
            'xmlns:xsi' => "http://www.w3.org/2001/XMLSchema-instance",
            'xsi:schemaLocation' => "http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_4.xsd"
        ];

        $xmlParts = [
            '<application ' . $this->buildXmlAttributes($xmlNamespaces) . '>',
            '  <modules>',
            "    <module name=\"$moduleName\">",
            '      <search limit="10" offset="0">',
//            '        <select>',
//            '          <field fieldPath="__id"/>',
//            '        </select>'
        ];

        if (!empty($expertConditions)) {
            $xmlParts[] = '        <expert>';
            foreach ($expertConditions as $field => $condition) {
                $xmlParts[] = "          <{$condition['operator']} fieldPath=\"$field\" operand=\"{$condition['value']}\" />";
            }
            $xmlParts[] = '        </expert>';
        }

        $xmlParts[] = implode("\n", [
            '      </search>',
            '    </module>',
            '  </modules>',
            '</application>'
        ]);

        return implode("\n", $xmlParts);
    }

    /**
     * @param array $attributes
     * @return string
     */
    private function buildXmlAttributes(array $attributes): string
    {
        return implode(' ', array_map(
            fn($key, $value) => "$key=\"$value\"",
            array_keys($attributes),
            $attributes
        ));
    }
}
