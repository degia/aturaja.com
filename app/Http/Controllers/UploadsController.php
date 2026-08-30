<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Support\BankLogos;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadsController extends Controller
{
    public function accountLogo(Account $account): BinaryFileResponse
    {
        abort_unless($account->hasUploadedIcon(), 404);

        $path = BankLogos::uploadedPath($account->icon);
        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }
}