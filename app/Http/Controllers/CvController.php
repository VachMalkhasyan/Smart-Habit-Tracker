<?php

namespace App\Http\Controllers;

use App\Models\UserCv;
use App\Models\JobApplication;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CvController extends Controller
{
    // CV management page
    public function index(Request $request)
    {
        return Inertia::render('Jobs/Cv', [
            'cvs'       => $request->user()->cvs()->latest()->get(),
            'active_cv' => $request->user()->activeCV(),
        ]);
    }

    // Upload PDF/Word file
    public function upload(Request $request)
    {
        $request->validate([
            'file'  => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'title' => 'nullable|string|max:100',
        ]);

        $file     = $request->file('file');
        $path     = $file->store('cvs/' . $request->user()->id, 'local');
        $rawText  = $this->extractText($file);

        $cv = $this->createCv($request->user(), [
            'title'     => $request->title ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'raw_text'  => $rawText,
        ]);

        return back()->with('success', 'CV uploaded and analyzed! ✅');
    }

    // Paste raw text
    public function storeText(Request $request)
    {
        $request->validate([
            'raw_text' => 'required|string|min:100|max:20000',
            'title'    => 'nullable|string|max:100',
        ]);

        $this->createCv($request->user(), [
            'title'    => $request->title ?? 'My CV',
            'raw_text' => $request->raw_text,
        ]);

        return back()->with('success', 'CV saved and analyzed! ✅');
    }

    private function createCv($user, array $data): UserCv
    {
        // Deactivate other CVs
        $user->cvs()->update(['is_active' => false]);

        $cv = $user->cvs()->create([
            ...$data,
            'is_active' => true,
        ]);

        // Parse CV with AI
        $parsed = app(AiService::class)->parseCv($cv->raw_text);
        $cv->update(['parsed_data' => $parsed]);

        return $cv;
    }

    // Set active CV
    public function setActive(Request $request, UserCv $userCv)
    {
        abort_if($userCv->user_id !== $request->user()->id, 403);
        $request->user()->cvs()->update(['is_active' => false]);
        $userCv->update(['is_active' => true]);
        return back()->with('success', 'Active CV updated!');
    }

    // Get improvement suggestions
    public function improve(Request $request, UserCv $userCv)
    {
        abort_if($userCv->user_id !== $request->user()->id, 403);
        $suggestions = app(AiService::class)->improveCv($userCv);
        return response()->json(['suggestions' => $suggestions]);
    }

    // ATS score for specific job
    public function scoreJob(Request $request, JobApplication $jobApplication)
    {
        abort_if($jobApplication->user_id !== $request->user()->id, 403);

        $cv = $request->user()->activeCV();
        if (!$cv) {
            return response()->json([
                'error' => 'No active CV found. Please upload your CV first.'
            ], 422);
        }

        $analysis = app(AiService::class)->scoreJobFit($cv, $jobApplication);
        return back()->with('success', 'ATS Match Score calculated! ✨');
    }

    public function destroy(Request $request, UserCv $userCv)
    {
        abort_if($userCv->user_id !== $request->user()->id, 403);
        if ($userCv->file_path) {
            Storage::disk('local')->delete($userCv->file_path);
        }
        $userCv->delete();
        return back()->with('success', 'CV removed.');
    }

    // Extract text from uploaded file
    private function extractText($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf') {
            $path = $file->getRealPath();
            $text = '';
            if (function_exists('shell_exec')) {
                $text = shell_exec("pdftotext " . escapeshellarg($path) . " -");
            }
            return $text ?: 'PDF text extraction failed. Please use the paste option.';
        }

        if (in_array($extension, ['doc', 'docx'])) {
            try {
                $zip = new \ZipArchive();
                if ($zip->open($file->getRealPath()) === true) {
                    $xml  = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if ($xml) {
                        $text = strip_tags(str_replace(
                            ['</w:p>', '</w:tr>'],
                            ["\n", "\n"],
                            $xml
                        ));
                        return preg_replace('/\s+/', ' ', $text);
                    }
                }
            } catch (\Exception $e) {
                // fallback
            }
            return 'DOCX text extraction failed. Please use the paste option.';
        }

        return '';
    }
}
