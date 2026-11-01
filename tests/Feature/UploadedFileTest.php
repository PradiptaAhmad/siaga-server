<?php

use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('uploads a valid image to the private disk', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/v1/files/image', [
        'image' => HttpUploadedFile::fake()->image('photo.jpg'),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.original_name', 'photo.jpg')
        ->assertJsonPath('data.mime_type', 'image/jpeg');

    $uploadedFile = UploadedFile::query()->firstOrFail();

    expect($uploadedFile->user_id)->toBe($user->id);
    Storage::disk('local')->assertExists($uploadedFile->path);
});

it('rejects a non-image upload', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/v1/files/image', [
        'image' => HttpUploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
    expect(UploadedFile::query()->count())->toBe(0);
});

it('rejects an image larger than five megabytes', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/v1/files/image', [
        'image' => HttpUploadedFile::fake()->create('large.jpg', 5121, 'image/jpeg'),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
    expect(UploadedFile::query()->count())->toBe(0);
});

it('returns unauthorized when uploading without authentication', function () {
    $response = $this->post('/v1/files/image');

    $response->assertUnauthorized();
});

it('allows only the owner to download and delete an uploaded image', function () {
    Storage::fake('local');
    $owner     = User::factory()->create();
    $otherUser = User::factory()->create();
    $path      = 'images/photo.jpg';
    Storage::disk('local')->put($path, 'image content');
    $uploadedFile = UploadedFile::create([
        'user_id'       => $owner->id,
        'path'          => $path,
        'original_name' => 'photo.jpg',
        'mime_type'     => 'image/jpeg',
        'size'          => 13,
    ]);

    $this->actingAs($otherUser)
        ->get("/v1/files/{$uploadedFile->id}/download")
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->delete("/v1/files/{$uploadedFile->id}")
        ->assertForbidden();

    $this->actingAs($owner)
        ->get("/v1/files/{$uploadedFile->id}/download")
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    $this->actingAs($owner)
        ->delete("/v1/files/{$uploadedFile->id}")
        ->assertOk();

    expect(UploadedFile::query()->find($uploadedFile->id))->toBeNull();
    Storage::disk('local')->assertMissing($path);
});
