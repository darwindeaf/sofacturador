<?php

namespace Tests\Unit;

use App\CoreFacturalo\Template;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class GreDispatchTemplateTest extends TestCase
{
    public function test_it_renders_the_gre_remitente_ubl_21_structure()
    {
        $company = (object) [
            'number' => '20123456789',
            'name' => 'EMPRESA DE PRUEBA SAC',
            'trade_name' => 'EMPRESA DE PRUEBA',
        ];
        $item = (object) [
            'description' => 'PRODUCTO DE PRUEBA',
            'internal_id' => 'P001',
            'item_code' => null,
            'unit_type_id' => 'NIU',
        ];
        $document = (object) [
            'series' => 'T001',
            'number' => 1,
            'date_of_issue' => Carbon::create(2026, 8, 25),
            'time_of_issue' => '10:30:00',
            'document_type_id' => '09',
            'observations' => null,
            'related' => null,
            'customer' => (object) [
                'identity_document_type_id' => '6',
                'number' => '20987654321',
                'name' => 'CLIENTE SAC',
            ],
            'transfer_reason_type_id' => '01',
            'transfer_reason_description' => null,
            'unit_type_id' => 'KGM',
            'total_weight' => 10,
            'packages_number' => 1,
            'transshipment_indicator' => false,
            'transport_mode_type_id' => '02',
            'date_of_shipping' => Carbon::create(2026, 8, 25),
            'dispatcher' => null,
            'driver' => (object) [
                'identity_document_type_id' => '1',
                'number' => '12345678',
                'first_name' => 'JUAN',
                'last_name' => 'PEREZ',
                'job_title' => 'Principal',
                'license' => 'Q12345678',
            ],
            'delivery' => (object) ['location_id' => '150101', 'address' => 'PUNTO DE LLEGADA'],
            'origin' => (object) ['location_id' => '150102', 'address' => 'PUNTO DE PARTIDA'],
            'container_number' => null,
            'license_plate' => 'ABC-123',
            'secondary_license_plates' => null,
            'port_code' => null,
            'items' => collect([(object) ['item' => $item, 'quantity' => 2]]),
        ];

        $xml = (new Template())->xml('dispatch', $company, $document);
        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        $this->assertSame('2.1', $xpath->evaluate('string(/*/cbc:UBLVersionID)'));
        $this->assertSame('2.0', $xpath->evaluate('string(/*/cbc:CustomizationID)'));
        $this->assertSame('150101', $xpath->evaluate('string(//cac:DeliveryAddress/cbc:ID)'));
        $this->assertSame('Q12345678', $xpath->evaluate('string(//cac:DriverPerson/cac:IdentityDocumentReference/cbc:ID)'));
        $this->assertSame('ABC123', $xpath->evaluate('string(//cac:TransportEquipment/cbc:ID)'));

        $xsd = getenv('SUNAT_GRE_XSD');
        if ($xsd) {
            $extensions = $dom->getElementsByTagNameNS(
                'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2',
                'UBLExtensions'
            );
            $extensions->item(0)->parentNode->removeChild($extensions->item(0));
            $this->assertTrue($dom->schemaValidate($xsd));
        }
    }
}
