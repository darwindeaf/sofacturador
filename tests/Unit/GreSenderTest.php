<?php

namespace Tests\Unit;

use App\CoreFacturalo\WS\Services\GreSender;
use App\Models\Tenant\Company;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GreSenderTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        Cache::flush();
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'services.sunat_gre.security_url' => 'https://api-seguridad.sunat.gob.pe/v1/clientessol',
            'services.sunat_gre.cpe_url' => 'https://api-cpe.sunat.gob.pe/v1/contribuyente/gem',
            'services.sunat_gre.scope' => 'https://api-cpe.sunat.gob.pe',
        ]);
    }

    public function test_it_authenticates_and_sends_the_signed_gre_zip()
    {
        $history = [];
        $handler = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'token-test', 'expires_in' => 3600])),
            new Response(200, [], json_encode(['numTicket' => '12345678-1234-1234-1234-123456789012'])),
        ]);
        $stack = HandlerStack::create($handler);
        $stack->push(Middleware::history($history));
        $sender = new GreSender($this->company(), new Client(['handler' => $stack]));

        $result = $sender->send('20123456789-09-T001-1', '<DespatchAdvice/>');

        $this->assertTrue($result->isSuccess());
        $this->assertSame('12345678-1234-1234-1234-123456789012', $result->getTicket());
        $this->assertCount(2, $history);
        $this->assertSame('/v1/clientessol/client-id/oauth2/token/', $history[0]['request']->getUri()->getPath());
        $this->assertStringContainsString('username=20123456789USUARIO', (string) $history[0]['request']->getBody());

        $request = $history[1]['request'];
        $this->assertSame('/v1/contribuyente/gem/comprobantes/20123456789-09-T001-1', $request->getUri()->getPath());
        $this->assertSame('Bearer token-test', $request->getHeaderLine('Authorization'));
        $payload = json_decode((string) $request->getBody(), true);
        $zip = base64_decode($payload['archivo']['arcGreZip'], true);
        $this->assertSame('20123456789-09-T001-1.zip', $payload['archivo']['nomArchivo']);
        $this->assertSame(hash('sha256', $zip), $payload['archivo']['hashZip']);
    }

    public function test_it_returns_pending_status_for_code_98()
    {
        $handler = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'token-test', 'expires_in' => 3600])),
            new Response(200, [], json_encode(['codRespuesta' => '98'])),
        ]);
        $sender = new GreSender($this->company(), new Client(['handler' => HandlerStack::create($handler)]));

        $result = $sender->status('12345678-1234-1234-1234-123456789012');

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->isPending());
        $this->assertSame('98', $result->getStatusCode());
    }

    public function test_it_preserves_the_oauth_error_returned_by_sunat()
    {
        $handler = new MockHandler([
            new Response(401, [], json_encode([
                'error' => 'access_denied',
                'error_description' => 'Error en la autenticacion del usuario.',
            ])),
        ]);
        $sender = new GreSender($this->company(), new Client(['handler' => HandlerStack::create($handler)]));

        $result = $sender->send('20123456789-09-T001-1', '<DespatchAdvice/>');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('access_denied', $result->getError()->getCode());
        $this->assertSame('Error en la autenticacion del usuario.', $result->getError()->getMessage());
    }

    private function company()
    {
        return (new Company())->forceFill([
            'number' => '20123456789',
            'gre_client_id' => 'client-id',
            'gre_client_secret' => 'client-secret',
            'soap_username' => '20123456789USUARIO',
            'soap_password' => 'clave-sol',
        ]);
    }
}
