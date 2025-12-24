<?php

namespace App\Http\Controllers\FormSubmissionFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PreviewController extends Controller
{
    public function store()
    {
        $folder = Str::uuid().'-'.now()->timestamp;

        return response()->json(['status' => true, 'message' => 'Test upload on preview', 'folder' => $folder]);
    }

    public function destroy(Request $request)
    {
        $folder = $request->getContent();

        return response()->json(['status' => true, 'message' => 'Test delete upload on preview', 'folder' => $folder]);
    }
}
