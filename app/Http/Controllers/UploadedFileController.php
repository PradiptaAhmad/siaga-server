<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadedFileController extends Controller
{
    public function storeImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $image = $request->file('image');
        $path  = $image->store('images', 'local');

        $uploadedFile = UploadedFile::create([
            'user_id'       => $request->user()->id,
            'disk'          => 'local',
            'path'          => $path,
            'original_name' => $image->getClientOriginalName(),
            'mime_type'     => $image->getMimeType(),
            'size'          => $image->getSize(),
        ]);

        return $this->successResponse(
            data: $uploadedFile,
            responseMessage: 'Successfully uploaded image',
            statusCode: 201,
        );
    }

    public function download(Request $request, UploadedFile $uploadedFile)
    {
        if ($uploadedFile->user_id != $request->user()->id) {
            return $this->errorResponse(
                message: 'You are not authorized to download this file.',
                code: 403,
            );
        }

        if (! Storage::disk($uploadedFile->disk)->exists($uploadedFile->path)) {
            return $this->errorResponse(
                message: 'The requested file does not exist.',
                code: 404,
            );
        }

        return response()->streamDownload(
            callback: function () use ($uploadedFile) {
                echo Storage::disk($uploadedFile->disk)->get($uploadedFile->path);
            },
            name: $uploadedFile->original_name,
            headers: [
                'Content-Type' => $uploadedFile->mime_type,
            ],
        );
    }

    public function destroy(Request $request, UploadedFile $uploadedFile): JsonResponse
    {
        if ($uploadedFile->user_id != $request->user()->id) {
            return $this->errorResponse(
                message: 'You are not authorized to delete this file.',
                code: 403,
            );
        }

        Storage::disk($uploadedFile->disk)->delete($uploadedFile->path);
        $uploadedFile->delete();

        return $this->successResponse(
            responseMessage: 'Successfully deleted image',
        );
    }
}
