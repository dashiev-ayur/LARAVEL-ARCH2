<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Orgs\UseCases\CreateOrgUseCase;
use App\Application\Orgs\UseCases\DeleteOrgUseCase;
use App\Application\Orgs\UseCases\ListOrgsUseCase;
use App\Application\Orgs\UseCases\UpdateOrgUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orgs\IndexOrgRequest;
use App\Http\Requests\Orgs\StoreOrgRequest;
use App\Http\Requests\Orgs\UpdateOrgRequest;
use App\Http\Resources\OrgResource;
use App\Models\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OrgController extends Controller
{
    public function index(IndexOrgRequest $request, ListOrgsUseCase $useCase): JsonResponse
    {
        return OrgResource::collection(
            $useCase->execute($request->validated()),
        )->response();
    }

    public function store(StoreOrgRequest $request, CreateOrgUseCase $useCase): JsonResponse
    {
        $org = $useCase->execute($request->validated());

        return (new OrgResource($org))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('orgs.show', $org, absolute: false));
    }

    public function show(Org $org): JsonResponse
    {
        return (new OrgResource($org))->response();
    }

    public function update(UpdateOrgRequest $request, Org $org, UpdateOrgUseCase $useCase): JsonResponse
    {
        return (new OrgResource($useCase->execute($org, $request->validated())))->response();
    }

    public function destroy(Org $org, DeleteOrgUseCase $useCase): Response
    {
        $useCase->execute($org);

        return response()->noContent();
    }
}
