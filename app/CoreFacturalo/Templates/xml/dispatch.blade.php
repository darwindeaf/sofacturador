{!! '<?xml version="1.0" encoding="utf-8" standalone="no"?>' !!}
<DespatchAdvice xmlns="urn:oasis:names:specification:ubl:schema:xsd:DespatchAdvice-2"
                xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
                xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent/>
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>{{ $document->series }}-{{ $document->number }}</cbc:ID>
    <cbc:IssueDate>{{ $document->date_of_issue->format('Y-m-d') }}</cbc:IssueDate>
    <cbc:IssueTime>{{ $document->time_of_issue }}</cbc:IssueTime>
    <cbc:DespatchAdviceTypeCode listAgencyName="PE:SUNAT"
                                listName="Tipo de Documento"
                                listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">{{ $document->document_type_id }}</cbc:DespatchAdviceTypeCode>
    @if($document->observations)
    <cbc:Note><![CDATA[{{ $document->observations }}]]></cbc:Note>
    @endif
    @if($document->related)
    <cac:AdditionalDocumentReference>
        <cbc:ID>{{ $document->related->number }}</cbc:ID>
        <cbc:DocumentTypeCode listAgencyName="PE:SUNAT"
                              listName="Documento relacionado al transporte"
                              listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo61">{{ $document->related->document_type_id }}</cbc:DocumentTypeCode>
    </cac:AdditionalDocumentReference>
    @endif
    <cac:Signature>
        <cbc:ID>{{ config('configuration.signature_uri') }}</cbc:ID>
        <cbc:Note>{{ config('configuration.signature_note') }}</cbc:Note>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>{{ $company->number }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[{{ $company->trade_name }}]]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#{{ config('configuration.signature_uri') }}</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>
    <cac:DespatchSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="6"
                        schemeName="Documento de Identidad"
                        schemeAgencyName="PE:SUNAT"
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">{{ $company->number }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[{{ $company->name }}]]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:DespatchSupplierParty>
    <cac:DeliveryCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="{{ $document->customer->identity_document_type_id }}"
                        schemeName="Documento de Identidad"
                        schemeAgencyName="PE:SUNAT"
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">{{ $document->customer->number }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[{{ $document->customer->name }}]]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:DeliveryCustomerParty>
    <cac:Shipment>
        <cbc:ID>SUNAT_Envio</cbc:ID>
        <cbc:HandlingCode listAgencyName="PE:SUNAT"
                          listName="Motivo de traslado"
                          listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo20">{{ $document->transfer_reason_type_id }}</cbc:HandlingCode>
        @if($document->transfer_reason_description)
        <cbc:HandlingInstructions>{{ $document->transfer_reason_description }}</cbc:HandlingInstructions>
        @endif
        <cbc:GrossWeightMeasure unitCode="{{ $document->unit_type_id }}">{{ number_format($document->total_weight, 3, '.', '') }}</cbc:GrossWeightMeasure>
        @if($document->packages_number)
        <cbc:TotalTransportHandlingUnitQuantity>{{ $document->packages_number }}</cbc:TotalTransportHandlingUnitQuantity>
        @endif
        @if($document->transshipment_indicator)
        <cbc:SpecialInstructions>SUNAT_Envio_IndicadorTransbordoProgramado</cbc:SpecialInstructions>
        @endif
        <cac:ShipmentStage>
            <cbc:TransportModeCode listName="Modalidad de traslado"
                                   listAgencyName="PE:SUNAT"
                                   listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo18">{{ $document->transport_mode_type_id }}</cbc:TransportModeCode>
            <cac:TransitPeriod>
                <cbc:StartDate>{{ $document->date_of_shipping->format('Y-m-d') }}</cbc:StartDate>
            </cac:TransitPeriod>
            @if($document->transport_mode_type_id == '01' && $document->dispatcher)
            @php($dispatcher = $document->dispatcher)
            <cac:CarrierParty>
                <cac:PartyIdentification>
                    <cbc:ID schemeID="6"
                            schemeName="Documento de Identidad"
                            schemeAgencyName="PE:SUNAT"
                            schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">{{ $dispatcher->number }}</cbc:ID>
                </cac:PartyIdentification>
                <cac:PartyLegalEntity>
                    <cbc:RegistrationName><![CDATA[{{ $dispatcher->name }}]]></cbc:RegistrationName>
                    @if(!empty($dispatcher->mtc_registration_number))
                    <cbc:CompanyID>{{ $dispatcher->mtc_registration_number }}</cbc:CompanyID>
                    @endif
                </cac:PartyLegalEntity>
            </cac:CarrierParty>
            @endif
            @if($document->transport_mode_type_id == '02' && $document->driver)
            @php($driver = $document->driver)
            <cac:DriverPerson>
                <cbc:ID schemeID="{{ $driver->identity_document_type_id }}"
                        schemeName="Documento de Identidad"
                        schemeAgencyName="PE:SUNAT"
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">{{ $driver->number }}</cbc:ID>
                <cbc:FirstName>{{ $driver->first_name }}</cbc:FirstName>
                <cbc:FamilyName>{{ $driver->last_name }}</cbc:FamilyName>
                <cbc:JobTitle>{{ !empty($driver->job_title) ? $driver->job_title : 'Principal' }}</cbc:JobTitle>
                <cac:IdentityDocumentReference>
                    <cbc:ID>{{ $driver->license }}</cbc:ID>
                </cac:IdentityDocumentReference>
            </cac:DriverPerson>
            @endif
        </cac:ShipmentStage>
        <cac:Delivery>
            <cac:DeliveryAddress>
                <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos">{{ $document->delivery->location_id }}</cbc:ID>
                <cac:AddressLine>
                    <cbc:Line>{{ $document->delivery->address }}</cbc:Line>
                </cac:AddressLine>
            </cac:DeliveryAddress>
            <cac:Despatch>
                <cac:DespatchAddress>
                    <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos">{{ $document->origin->location_id }}</cbc:ID>
                    <cac:AddressLine>
                        <cbc:Line>{{ $document->origin->address }}</cbc:Line>
                    </cac:AddressLine>
                </cac:DespatchAddress>
            </cac:Despatch>
        </cac:Delivery>
        @if($document->container_number)
        <cac:TransportHandlingUnit>
            <cac:Package>
                <cbc:ID>1</cbc:ID>
                <cbc:TraceID>{{ $document->container_number }}</cbc:TraceID>
            </cac:Package>
        </cac:TransportHandlingUnit>
        @endif
        @if($document->transport_mode_type_id == '02' && $document->license_plate)
        <cac:TransportHandlingUnit>
            <cac:TransportEquipment>
                <cbc:ID>{{ strtoupper(str_replace(['-', ' '], '', $document->license_plate)) }}</cbc:ID>
                @if($document->secondary_license_plates && $document->secondary_license_plates->semitrailer)
                <cac:AttachedTransportEquipment>
                    <cbc:ID>{{ strtoupper(str_replace(['-', ' '], '', $document->secondary_license_plates->semitrailer)) }}</cbc:ID>
                </cac:AttachedTransportEquipment>
                @endif
            </cac:TransportEquipment>
        </cac:TransportHandlingUnit>
        @endif
        @if($document->port_code)
        <cac:FirstArrivalPortLocation>
            <cbc:ID schemeAgencyName="PE:SUNAT"
                    schemeName="Puertos"
                    schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo63">{{ $document->port_code }}</cbc:ID>
            <cbc:LocationTypeCode>1</cbc:LocationTypeCode>
        </cac:FirstArrivalPortLocation>
        @endif
    </cac:Shipment>
    @foreach($document->items as $row)
    <cac:DespatchLine>
        <cbc:ID>{{ $loop->iteration }}</cbc:ID>
        <cbc:DeliveredQuantity unitCode="{{ $row->item->unit_type_id }}">{{ $row->quantity }}</cbc:DeliveredQuantity>
        <cac:OrderLineReference>
            <cbc:LineID>{{ $loop->iteration }}</cbc:LineID>
        </cac:OrderLineReference>
        <cac:Item>
            <cbc:Description><![CDATA[{{ $row->item->description }}]]></cbc:Description>
            @if($row->item->internal_id)
            <cac:SellersItemIdentification>
                <cbc:ID>{{ $row->item->internal_id }}</cbc:ID>
            </cac:SellersItemIdentification>
            @endif
            @if($row->item->item_code)
            <cac:CommodityClassification>
                <cbc:ItemClassificationCode listID="UNSPSC"
                                            listAgencyName="GS1 US"
                                            listName="Item Classification">{{ $row->item->item_code }}</cbc:ItemClassificationCode>
            </cac:CommodityClassification>
            @endif
        </cac:Item>
    </cac:DespatchLine>
    @endforeach
</DespatchAdvice>
