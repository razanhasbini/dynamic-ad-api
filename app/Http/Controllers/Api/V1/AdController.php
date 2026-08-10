<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateAd;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdController extends Controller
{
    public function store(
        StoreAdRequest $request,
        CreateAd $createAd
    ) {
        $ad = $createAd->execute(
            $request->user(),
            $request->validated()
        );

        $ad->load([
            'category',
            'fieldValues.categoryField',
            'fieldValues.option',
        ]);

        return (new AdResource($ad))
            ->response()
            ->setStatusCode(201);
    }

    public function myAds(): AnonymousResourceCollection
    {
        $ads = Ad::where('user_id', auth()->id())
            ->with([
                'category',
                'fieldValues.categoryField',
                'fieldValues.option',
            ])
            ->latest()
            ->paginate(15);

        return AdResource::collection($ads);
    }

    public function show(Ad $ad): AdResource
    {
        $ad->load([
            'category',
            'fieldValues.categoryField',
            'fieldValues.option',
        ]);

        return new AdResource($ad);
    }
}
