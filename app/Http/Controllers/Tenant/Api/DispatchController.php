<?php
namespace App\Http\Controllers\Tenant\Api;

use App\CoreFacturalo\Facturalo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Dispatch;

class DispatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('input.request:dispatch,api', ['only' => ['store']]);
    }

    public function store(Request $request)
    {
        
        $request->validate([
            'document_type_id' => 'required|in:09',
            'date_of_issue' => 'required|date_format:Y-m-d|before_or_equal:today|after_or_equal:yesterday',
            'date_of_shipping' => 'required|date_format:Y-m-d|after_or_equal:date_of_issue',
            'transport_mode_type_id' => 'required|in:01,02',
            'delivery.address' => 'required|max:100',
            'origin.address' => 'required|max:100',
            'dispatcher.number' => 'required_if:transport_mode_type_id,01',
            'dispatcher.name' => 'required_if:transport_mode_type_id,01',
            'driver.identity_document_type_id' => 'required_if:transport_mode_type_id,02',
            'driver.number' => 'required_if:transport_mode_type_id,02',
            'driver.first_name' => 'required_if:transport_mode_type_id,02',
            'driver.last_name' => 'required_if:transport_mode_type_id,02',
            'driver.license' => 'required_if:transport_mode_type_id,02',
            'license_plate' => 'required_if:transport_mode_type_id,02',
        ]);

        $fact = DB::connection('tenant')->transaction(function () use($request) {
            $facturalo = new Facturalo();
            $facturalo->save($request->all());
            $facturalo->createXmlUnsigned();
            $facturalo->signXmlUnsigned();
            $facturalo->createPdf();
            $facturalo->sendEmail();
            return $facturalo;
        });

        $fact->senderXmlSignedBill();

        $document = $fact->getDocument();
        $response = $fact->getResponse();

        return [
            'success' => true,
            'data' => [
                'number' => $document->number_full,
                'filename' => $document->filename,
                'external_id' => $document->external_id,
                'ticket' => $document->gre_ticket,
                'status' => $document->gre_status,
            ],
            'links' => [
                'xml' => $document->download_external_xml,
                'pdf' => $document->download_external_pdf,
                'cdr' => $document->has_cdr ? $document->download_external_cdr : null,
            ],
            'response' => array_except($response, 'sent')
        ];
    }

    public function status($externalId)
    {
        $document = Dispatch::where('external_id', $externalId)->firstOrFail();
        $facturalo = new Facturalo();
        $facturalo->setDocument($document);
        $facturalo->setType('dispatch');
        $facturalo->statusDispatch();
        $document = $facturalo->getDocument()->fresh();

        return [
            'success' => true,
            'data' => [
                'number' => $document->number_full,
                'filename' => $document->filename,
                'external_id' => $document->external_id,
                'ticket' => $document->gre_ticket,
                'status' => $document->gre_status,
            ],
            'links' => [
                'xml' => $document->download_external_xml,
                'pdf' => $document->download_external_pdf,
                'cdr' => $document->has_cdr ? $document->download_external_cdr : null,
            ],
            'response' => array_except($facturalo->getResponse(), 'sent'),
        ];
    }
}
