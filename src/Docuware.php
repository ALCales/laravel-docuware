<?php

namespace ALCales\Docuware;

use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Docuware
{
    const CACHE_COOKIE_KEY = 'docuware_cookie';
    const STORAGE_PATH = DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'docuware' . DIRECTORY_SEPARATOR;

    /**
     * @var string
     */
    private $urlRoot;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $cookie;

    public function __construct(string $urlRoot = null, string $user = null, string $password = null)
    {
        if (isset($urlRoot, $user, $password)) {
            $this->urlRoot = $urlRoot;
            $this->user = $user;
            $this->password = $password;
        } else {
            $this->setDefaultCredentials();
        }

        $this->getCookie();
    }

    protected function setDefaultCredentials()
    {
        $this->urlRoot = config('docuware.url_root');
        $this->user = config('docuware.user');
        $this->password = config('docuware.password');
    }

    protected function login(): bool
    {
        $this->logout();

        $response = Http::asForm()
            ->withHeaders([
                'Accept' => 'application/json'
            ])->post($this->urlRoot . '/Account/Logon', [
                'LicenseType' => '',
                'Password' => $this->password,
                'RedirectToMyselfInCaseOfError' => 'false',
                'RememberMe' => 'false',
                'UserName' => $this->user,
            ]);

        $this->checkResponse($response);

        $this->setCookie($response->cookies());

        return $response->successful();
    }

    public function logout(): bool
    {
        $response = Http::withHeaders([
            'Cookie' => $this->cookie,
        ])->get($this->urlRoot . '/Account/Logoff');

        $this->checkResponse($response);

        $this->deleteCookie();

        return $response->successful();
    }

    protected function setCookie(CookieJar $cookie)
    {
        $cookies = $cookie->toArray();
        foreach ($cookies as $cookie) {
            if (empty($cookie['Value'])) {
                continue;
            }
            $this->cookie .= $cookie['Name'] . "=" . $cookie['Value'] . "; ";
        }

        Cache::put(self::CACHE_COOKIE_KEY, $this->cookie, now()->addDays(1));
    }

    protected function getCookie()
    {
        $this->cookie = Cache::get(self::CACHE_COOKIE_KEY);

        if (empty($this->cookie)) {
            $this->login();
        }
    }

    protected function deleteCookie()
    {
        Cache::forget(self::CACHE_COOKIE_KEY);
        $this->cookie = null;
    }

    protected function checkResponse(Response $response)
    {
        if ($response->status() == 401) {
            $this->deleteCookie();
        }

        if ($response->failed()) {
            throw new \Exception('Request failed. Status: ' . $response->status() . ' Body: ' . $response->body());
        }
    }

    /**
     * Obtiene una lista de todos los documentos localizados en $fileCabinetId
     * @param string $fileCabinetId
     * @return array|null
     */
    public function getDocumentsList(string $fileCabinetId): ?array
    {
        $response = Http::withHeaders([
            'Cookie' => $this->cookie,
            'Accept' => 'application/json'
        ])->get($this->urlRoot . '/FileCabinets/' . $fileCabinetId . '/Documents');

        return $response->json();
    }

    /**
     * Obtiene la lista de documentos filtrados por la $query y $fileCabinetId pasados.
     * @param string $fileCabinetId
     * @param string $query
     * @return array|null
     * @throws \Exception
     */
    public function getDocumentsListWithFilter(string $fileCabinetId, string $query): ?array
    {
        $url = $this->urlRoot . '/FileCabinets/' . $fileCabinetId . '/Documents?q=' . $query;

        $response = Http::withHeaders([
            'Cookie' => $this->cookie,
            'Accept' => 'application/json'
        ])->get($url);

        $this->checkResponse($response);

        return $response->json();
    }

    /**
     * Almacena en el servidor el documento con el id y archivador indicados.
     * Si no se especifica la ruta se almacenará en la ruta de por defecto: STORAGE_PATH
     * @param string $fileCabinetId
     * @param int $idDocument
     * @param string $storagePath
     * @return string : filename in storage
     * @throws \Exception
     */
    public function downloadDocument(string $fileCabinetId, int $idDocument, string $storagePath = null): ?string
    {
        $url = $this->urlRoot . '/FileCabinets/' . $fileCabinetId . "/Documents/$idDocument/FileDownload?targetFileType=Auto&keepAnnotations=false";

        $storagePath = $storagePath ?? storage_path(self::STORAGE_PATH);
        $filename = $idDocument . '-' . Carbon::now()->format('Ymd') . '.pdf';

        $response = Http::withHeaders([
            'Cookie' => $this->cookie,
        ])->sink($storagePath . $filename)->get($url);

        $this->checkResponse($response);

        return $filename;
    }

    /**
     * Actualiza los campos de un documento
     * @param string $fileCabinetId
     * @param int $idDocument
     * @param array<DocuwareField> $fields
     * @return bool
     * @throws \Exception
     */
    public function updateIndexValues(string $fileCabinetId, int $idDocument, array $fields): bool
    {
        $url = $this->urlRoot . '/FileCabinets/' . $fileCabinetId . '/Documents/' . $idDocument . '/Fields';

        // Convertir objetos Field a array
        $fieldsArray = [];
        foreach ($fields as $field) {
            $fieldsArray[] = get_object_vars($field);
        }

        // Formato requerido por Docuware
        $fields = ["Field" => $fieldsArray];

        $response = Http::withHeaders([
            'Cookie' => $this->cookie,
        ])
            ->withBody(json_encode($fields), 'application/json')
            ->put($url);

        $this->checkResponse($response);

        return $response->successful();
    }

}
