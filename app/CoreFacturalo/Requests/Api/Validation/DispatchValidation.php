<?php

namespace App\CoreFacturalo\Requests\Api\Validation;

use App\Models\Tenant\Item;
use Exception;
use Illuminate\Support\Facades\Validator;

class DispatchValidation
{
    public static function validation($inputs)
    {
        Validator::make($inputs, [
            'document_type_id' => 'required|in:09',
            'series' => ['required', 'regex:/^T[A-Z0-9]{3}$/'],
            'date_of_issue' => 'required|date_format:Y-m-d|before_or_equal:today|after_or_equal:yesterday',
            'time_of_issue' => 'required|date_format:H:i:s',
            'date_of_shipping' => 'required|date_format:Y-m-d|after_or_equal:date_of_issue',
            'transport_mode_type_id' => 'required|in:01,02',
            'transfer_reason_type_id' => 'required',
            'unit_type_id' => 'required',
            'total_weight' => 'required|numeric|min:0.001',
            'origin.location_id' => 'required|digits:6',
            'origin.address' => 'required|string|max:100',
            'delivery.location_id' => 'required|digits:6',
            'delivery.address' => 'required|string|max:100',
            'dispatcher.identity_document_type_id' => 'required_if:transport_mode_type_id,01',
            'dispatcher.number' => 'required_if:transport_mode_type_id,01',
            'dispatcher.name' => 'required_if:transport_mode_type_id,01',
            'driver.identity_document_type_id' => 'required_if:transport_mode_type_id,02',
            'driver.number' => 'required_if:transport_mode_type_id,02',
            'driver.first_name' => 'required_if:transport_mode_type_id,02',
            'driver.last_name' => 'required_if:transport_mode_type_id,02',
            'driver.license' => 'required_if:transport_mode_type_id,02',
            'license_plate' => 'required_if:transport_mode_type_id,02',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required',
            'items.*.unit_type_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.0000000001',
        ])->validate();

        $inputs['establishment_id'] = Functions::establishment($inputs['establishment']);
        unset($inputs['establishment']);

        Functions::validateSeries($inputs);

        $inputs['customer_id'] = Functions::person($inputs['customer'], 'customers');
        unset($inputs['customer']);

        $inputs['items'] = self::items($inputs['items']);

        return $inputs;
    }

    private static function items($inputs)
    {
        $items = [];
        foreach ($inputs as $row)
        {

            $id = Functions::item2($row);

            /*$item = Item::where('internal_id', $row['internal_id'])->first();

            if(!$item) {
                //throw new Exception("El código interno {$row['internal_id']} no fue encontrado.");
            }
            else{
                $id = $item->id;
            }*/

            $items[] = [
                'item_id' => $id,
                'quantity' => $row['quantity']
            ];
        }

        return $items;
    }
}
