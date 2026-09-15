<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DispatchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        //$id = $this->input('id');

        return [
            'unit_type_id' => [
                'required',
            ],
            // 'transfer_reason_description' => [
            //     'required',
            // ],
            // 'observations' => [
            //     'required',
            // ],
            'delivery.address'=> [
                'required',
                'max:100',
               
            ],
            'dispatcher.identity_document_type_id'=> [
                'required_if:transport_mode_type_id,01',
            ],
            'dispatcher.number'=> [
                'required_if:transport_mode_type_id,01',
            ],
            'dispatcher.name'=> [
                'required_if:transport_mode_type_id,01',
            ],
            'driver.identity_document_type_id'=> [
                'required_if:transport_mode_type_id,02',
            ],
            'driver.number'=> [
                'required_if:transport_mode_type_id,02',
            ],
            'driver.first_name'=> [
                'required_if:transport_mode_type_id,02',
            ],
            'driver.last_name'=> [
                'required_if:transport_mode_type_id,02',
            ],
            'driver.license'=> [
                'required_if:transport_mode_type_id,02',
            ],
            'license_plate'=> [
                'required_if:transport_mode_type_id,02',
            ],
            'date_of_issue' => [
                'required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:yesterday',
            ],
            'date_of_shipping' => [
                'required', 'date_format:Y-m-d', 'after_or_equal:date_of_issue',
            ],

            'customer_id'=> [
                'required',
            ],
            'transport_mode_type_id'=> [
                'required',
            ],
            'transfer_reason_type_id'=> [
                'required',
            ],
            'origin.address'=> [
                'required',
                'max:100',
            ],

            
           
        ];
    }

    public function messages()
    {
        return [
        'transfer_reason_description.required' => 'El campo Descripción de motivo de traslado es obligatorio.',
        'observations.required' => 'El campo Observaciones es obligatorio.',
        'dispatcher.identity_document_type_id.required' => 'El campo Tipo Doc. Identidad es obligatorio.',
        'dispatcher.number.required' => 'El campo Número es obligatorio.',
        'dispatcher.name.required' => 'El campo Nombre y/o razón social es obligatorio.',
        'driver.identity_document_type_id.required' => 'El campo Tipo Doc. Identidad es obligatorio.',
        'driver.number.required' => 'El campo Número es obligatorio.',
        'driver.first_name.required_if' => 'Los nombres del conductor son obligatorios para transporte privado.',
        'driver.last_name.required_if' => 'Los apellidos del conductor son obligatorios para transporte privado.',
        'driver.license.required_if' => 'La licencia del conductor es obligatoria para transporte privado.',
        'license_plate.required' => 'El campo Número de placa del vehiculo es obligatorio.',
        ];
    }
}
