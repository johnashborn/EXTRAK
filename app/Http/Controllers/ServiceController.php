<?php

namespace App\Http\Controllers;

use App\Actions\Services\CreateService;
use App\Actions\Services\DiscardDraft;
use App\Actions\Services\PublishService;
use App\Actions\Services\UpdateService;
use App\Enums\DocumentKind;
use App\Enums\ServiceState;
use App\Http\Requests\SaveServiceRequest;
use App\Models\Barangay;
use App\Models\BeneficiarySector;
use App\Models\Category;
use App\Models\Municipality;
use App\Models\Service;
use App\Services\ApiResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Adding, editing, publishing and discarding records. Every rule is checked here on the server;
 * the upload script only adds a progress bar, and a plain form post works the same way.
 */
class ServiceController extends Controller
{
    public function create(Request $request): View
    {
        Gate::authorize('create', Service::class);

        $service = new Service(['campus_id' => $request->user()->campus_id, 'state' => ServiceState::Draft]);

        return view('services.form', $this->formData($service));
    }

    public function store(SaveServiceRequest $request, CreateService $create, PublishService $publish): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Service::class);

        $service = $create->handle(
            $request->user(),
            $request->fields(),
            $request->reportFile(),
            $request->reportCopy(),
            $request->attachments(),
        );

        if ($request->intent() === 'publish') {
            Gate::authorize('publish', $service);
            $publish->handle($request->user(), $service);
        }

        return $this->saved($request, $service, $request->intent() === 'publish' ? 'Record published.' : 'Draft saved.');
    }

    public function show(Service $service): View
    {
        Gate::authorize('view', $service);

        // Stub for the record page; Task 8 builds the full page.
        return view('services.show', [
            'service' => $service->load(['documents' => fn ($q) => $q->orderBy('id')]),
        ]);
    }

    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        return view('services.form', $this->formData($service->load(['documents', 'barangays', 'sectors'])));
    }

    public function update(SaveServiceRequest $request, Service $service, UpdateService $update, PublishService $publish): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $service);

        $attachments = $request->attachments();
        if ($report = $request->reportFile()) {
            $attachments[] = ['file' => $report, 'kind' => DocumentKind::AccomplishmentReport, 'copy_type' => $request->reportCopy()];
        }

        $wasDraft = $service->state === ServiceState::Draft;
        $update->handle($request->user(), $service, $request->fields(), $attachments, $request->removeDocumentIds());

        if ($wasDraft && $request->intent() === 'publish') {
            Gate::authorize('publish', $service);
            $publish->handle($request->user(), $service);

            return $this->saved($request, $service, 'Record published.');
        }

        return $this->saved($request, $service, $wasDraft ? 'Draft saved.' : 'Changes saved.');
    }

    public function publish(Request $request, Service $service, PublishService $publish): JsonResponse|RedirectResponse
    {
        Gate::authorize('publish', $service);

        $publish->handle($request->user(), $service);

        return $this->saved($request, $service, 'Record published.');
    }

    public function destroy(Request $request, Service $service, DiscardDraft $discard): JsonResponse|RedirectResponse
    {
        Gate::authorize('discard', $service);

        $discard->handle($request->user(), $service);

        if ($request->expectsJson()) {
            return ApiResponse::ok(['redirect' => route('dashboard')]);
        }

        return redirect()->route('dashboard')->with('status', 'Draft discarded.');
    }

    private function saved(Request $request, Service $service, string $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return ApiResponse::ok(['redirect' => route('services.show', $service)]);
        }

        return redirect()->route('services.show', $service)->with('status', $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Service $service): array
    {
        return [
            'service' => $service,
            'categories' => Category::orderBy('name')->get(),
            'municipalities' => Municipality::orderBy('name')->get(),
            'barangays' => Barangay::orderBy('name')->get()->groupBy('municipality_id'),
            'sectors' => BeneficiarySector::orderBy('position')->orderBy('name')->get(),
            'kinds' => array_filter(DocumentKind::cases(), fn ($kind) => $kind !== DocumentKind::AccomplishmentReport),
        ];
    }
}
