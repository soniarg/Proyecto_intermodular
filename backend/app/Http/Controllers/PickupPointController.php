<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PickupPointController extends Controller
{
    public function index()
    {
        $seller_id = auth()->user()->sellerProfile->seller_id;
        return PickupPoint::where('seller_id', auth()->id())->get();
    }

    public function store(Request $request)
    {
        $seller_id = auth()->user()->sellerProfile->seller_id;
        return PickupPoint::create([
            ...$request->validate([
                'address'=>'required|string',
                'latitude'=>'required|numeric',
                'longitude'=>'required|numeric',
            ]),
            'seller_id' => auth()->id()
        ]);
    }

    public function update(Request $request, PickupPoint $PickupPoint)
    {
        $pickupPoint->update($request->only(['address','latitude','longitude']));
        return $pickupPoint;
    }

    public function destroy(PickupPoint $pickupPoint)
    {
        $pickupPoint->delete();
        return response()->noContent();
    }
}
