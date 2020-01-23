<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TransferCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function toArray($request)
    {
        return $this->collection->transform(function($row, $key) {
            return [
                'id' => $row->id,
                'description' => optional($row->item)->description,
                'quantity' => round($row->quantity, 1),
                'warehouse' => $row->warehouse->description,
                'warehouse_destination' => $row->warehouse_destination->description,
                'created_at' => $row->created_at->format('Y-m-d H:i:s'),
                'inventory' => $row->inventory->transform(function($o){
                    return [
                        'id' => $o->item->id,
                        'description' => $o->item->description,
                        'quantity' => $o->quantity
                    ];
                })
            ];
        });
    }
}