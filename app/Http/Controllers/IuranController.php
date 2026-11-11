<?php

namespace App\Http\Controllers;

use App\Models\Iuran;
use App\Models\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class IuranController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->successResponse(
            data: Iuran::query()
                ->with('proofFiles')
                ->latest()
                ->paginate(
                    page: $data['page'] ?? 1,
                    perPage: $data['per_page'] ?? 15,
                )
                ->getCollection(),
            responseMessage: 'Successfully retrieved iurans',
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedData($request);

        $iuran = Iuran::create([
            'user_id' => $request->user()->id,
            'amount' => $validated['amount'],
            'status' => $validated['status'] ?? 'pending',
            'due_date' => $validated['due_date'],
        ]);

        $this->attachProofFile($request, $iuran);

        return $this->successResponse(
            data: $iuran->load('proofFiles'),
            responseMessage: 'Successfully created iuran',
            statusCode: 201,
        );
    }

    public function show(Iuran $iuran): JsonResponse
    {
        return $this->successResponse(
            data: $iuran->load('proofFiles'),
            responseMessage: 'Successfully retrieved iuran',
        );
    }

    public function update(Request $request, Iuran $iuran): JsonResponse
    {
        $validated = $this->validatedData($request, $iuran);

        $iuran->update($validated);

        if ($request->has('proof_file_id')) {
            $this->attachProofFile($request, $iuran);
        }

        return $this->successResponse(
            data: $iuran->fresh()->load('proofFiles'),
            responseMessage: 'Successfully updated iuran',
        );
    }

    public function destroy(Iuran $iuran): JsonResponse
    {
        $proofFile = $iuran->proofFiles;

        $iuran->delete();

        if ($proofFile !== null) {
            Storage::disk($proofFile->disk)->delete($proofFile->path);
            $proofFile->delete();
        }

        return $this->successResponse(
            responseMessage: 'Successfully deleted iuran',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Iuran $iuran = null): array
    {
        $required = $iuran === null ? 'required' : 'sometimes';

        return $request->validate([
            'amount' => [$required, 'numeric', 'min:0'],
            'status' => [$iuran === null ? 'sometimes' : 'sometimes', Rule::in([
                'pending',
                'paid',
                'overdue',
            ])],
            'due_date' => [$required, 'date'],
            'proof_file_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('uploaded_files', 'id')
                    ->where('user_id', $request->user()->id),
            ],
        ]);
    }

    private function attachProofFile(Request $request, Iuran $iuran): void
    {
        if (! $request->has('proof_file_id')) {
            return;
        }

        if ($request->input('proof_file_id') === null) {
            $iuran->proofFiles()->update([
                'fileable_id' => null,
                'fileable_type' => null,
            ]);

            return;
        }

        $proofFile = UploadedFile::query()
            ->where('id', $request->input('proof_file_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $iuran->proofFiles()->save($proofFile);
    }
}