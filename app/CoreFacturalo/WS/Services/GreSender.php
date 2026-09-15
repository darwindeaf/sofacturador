<?php

namespace App\CoreFacturalo\WS\Services;

use App\CoreFacturalo\WS\Reader\DomCdrReader;
use App\CoreFacturalo\WS\Response\Error;
use App\CoreFacturalo\WS\Response\GreResult;
use App\CoreFacturalo\WS\Zip\ZipFileDecompress;
use App\CoreFacturalo\WS\Zip\ZipFly;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Cache;

class GreSender
{
    protected $company;
    protected $http;
    protected $compressor;
    protected $decompressor;
    protected $cdrReader;

    public function __construct(Company $company, ClientInterface $http = null)
    {
        $this->company = $company;
        $this->http = $http ?: new Client();
        $this->compressor = new ZipFly();
        $this->decompressor = new ZipFileDecompress();
        $this->cdrReader = new DomCdrReader();
    }

    public function send($filename, $content)
    {
        $result = new GreResult();

        try {
            $this->validateCredentials();
            $this->validateFilename($filename);

            $zip = $this->compressor->compress($filename.'.xml', $content);
            $response = $this->request('POST', 'comprobantes/'.$filename, [
                'json' => [
                    'archivo' => [
                        'nomArchivo' => $filename.'.zip',
                        'arcGreZip' => base64_encode($zip),
                        'hashZip' => hash('sha256', $zip),
                    ],
                ],
            ]);

            if (empty($response['numTicket'])) {
                throw new \RuntimeException('SUNAT no devolvió el número de ticket GRE.');
            }

            $result
                ->setTicket($response['numTicket'])
                ->setReceptionDate(isset($response['fecRecepcion']) ? $response['fecRecepcion'] : null)
                ->setRawResponse($response)
                ->setSuccess(true);
        } catch (\Exception $e) {
            $result->setError($this->errorFromException($e));
        }

        return $result;
    }

    public function status($ticket)
    {
        $result = (new GreResult())->setTicket($ticket);

        try {
            $this->validateCredentials();
            $response = $this->request('GET', 'comprobantes/envios/'.rawurlencode($ticket));
            $status = isset($response['codRespuesta']) ? (string) $response['codRespuesta'] : null;

            if ($status === null) {
                throw new \RuntimeException('SUNAT no devolvió el estado del ticket GRE.');
            }

            $result
                ->setStatusCode($status)
                ->setRawResponse($response)
                ->setSuccess(true);

            if (!empty($response['arcCdr']) && isset($response['indCdrGenerado']) && (string) $response['indCdrGenerado'] === '1') {
                $cdrZip = base64_decode($response['arcCdr'], true);
                if ($cdrZip === false) {
                    throw new \RuntimeException('SUNAT devolvió un CDR GRE inválido.');
                }

                $result
                    ->setCdrZip($cdrZip)
                    ->setCdrResponse($this->extractCdr($cdrZip));
            }

            if ($status === '99' && !empty($response['error'])) {
                $result->setError(new Error(
                    isset($response['error']['numError']) ? $response['error']['numError'] : '99',
                    isset($response['error']['desError']) ? $response['error']['desError'] : 'SUNAT rechazó la GRE.'
                ));
            }
        } catch (\Exception $e) {
            $result->setSuccess(false)->setError($this->errorFromException($e));
        }

        return $result;
    }

    protected function request($method, $path, array $options = [])
    {
        $options['headers'] = array_merge(isset($options['headers']) ? $options['headers'] : [], [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->getToken(),
        ]);
        $options['http_errors'] = false;
        $options['timeout'] = config('services.sunat_gre.timeout', 30);
        $options['connect_timeout'] = config('services.sunat_gre.connect_timeout', 10);

        $url = rtrim(config('services.sunat_gre.cpe_url'), '/').'/'.ltrim($path, '/');
        $response = $this->http->request($method, $url, $options);

        return $this->decodeResponse($response->getStatusCode(), (string) $response->getBody());
    }

    protected function getToken()
    {
        $cacheKey = 'sunat_gre_token:'.sha1($this->company->gre_client_id.'|'.$this->company->soap_username);
        $cached = Cache::get($cacheKey);
        if (!empty($cached)) {
            return $cached;
        }

        $url = rtrim(config('services.sunat_gre.security_url'), '/').'/'.rawurlencode($this->company->gre_client_id).'/oauth2/token/';
        $response = $this->http->request('POST', $url, [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => [
                'grant_type' => 'password',
                'scope' => config('services.sunat_gre.scope'),
                'client_id' => $this->company->gre_client_id,
                'client_secret' => $this->company->gre_client_secret,
                'username' => $this->company->soap_username,
                'password' => $this->company->soap_password,
            ],
            'http_errors' => false,
            'timeout' => config('services.sunat_gre.timeout', 30),
            'connect_timeout' => config('services.sunat_gre.connect_timeout', 10),
        ]);

        $data = $this->decodeResponse($response->getStatusCode(), (string) $response->getBody());
        if (empty($data['access_token'])) {
            throw new \RuntimeException('SUNAT no devolvió el token de acceso GRE.');
        }

        $seconds = isset($data['expires_in']) ? max(60, ((int) $data['expires_in']) - 60) : 3540;
        Cache::put($cacheKey, $data['access_token'], Carbon::now()->addSeconds($seconds));

        return $data['access_token'];
    }

    protected function decodeResponse($status, $body)
    {
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Respuesta no válida de la API GRE de SUNAT (HTTP '.$status.').');
        }

        if ($status < 200 || $status >= 300) {
            $code = isset($data['cod']) ? $data['cod'] : (isset($data['error']) && is_string($data['error']) ? $data['error'] : 'HTTP_'.$status);
            $message = isset($data['msg']) ? $data['msg'] : (isset($data['error_description']) ? $data['error_description'] : 'Error HTTP '.$status.' al comunicarse con SUNAT.');
            if (!empty($data['errors']) && is_array($data['errors'])) {
                $details = [];
                foreach ($data['errors'] as $error) {
                    if (!is_array($error)) {
                        $details[] = (string) $error;
                        continue;
                    }
                    $errorCode = isset($error['cod']) ? $error['cod'] : (isset($error['codError']) ? $error['codError'] : null);
                    $errorMessage = isset($error['msg']) ? $error['msg'] : (isset($error['desError']) ? $error['desError'] : null);
                    $details[] = trim(($errorCode ? $errorCode.': ' : '').$errorMessage);
                }
                $message .= ' '.implode(' | ', array_filter($details));
            }

            throw new GreServiceException((string) $code, trim($message));
        }

        return $data;
    }

    protected function extractCdr($zip)
    {
        $files = $this->decompressor->decompress($zip, function ($filename) {
            return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'xml';
        });

        if (count($files) === 0) {
            throw new \RuntimeException('El CDR GRE de SUNAT no contiene un XML.');
        }

        return $this->cdrReader->getCdrResponse($files[0]['content']);
    }

    protected function validateCredentials()
    {
        foreach (['gre_client_id', 'gre_client_secret', 'soap_username', 'soap_password'] as $field) {
            if (empty($this->company->{$field})) {
                throw new \RuntimeException('Falta configurar '.$field.' para el envío GRE a SUNAT.');
            }
        }
    }

    protected function validateFilename($filename)
    {
        if (!preg_match('/^\d{11}-09-T[A-Z0-9]{3}-\d{1,8}$/', $filename)) {
            throw new \InvalidArgumentException('El nombre de la GRE Remitente no cumple el formato RUC-09-T###-número.');
        }
    }

    protected function errorFromException(\Exception $e)
    {
        if ($e instanceof GreServiceException) {
            return new Error($e->getSunatCode(), $e->getMessage());
        }

        return new Error('API_GRE', $e->getMessage());
    }
}
