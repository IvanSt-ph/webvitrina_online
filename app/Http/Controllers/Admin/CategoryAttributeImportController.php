<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttributeImport\ImportException;
use App\Services\AttributeImport\ImportService;
use App\Services\AttributeImport\XlsxReader;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;

final class CategoryAttributeImportController extends Controller
{
    public function index()
    {
        return view('admin.categories.import');
    }

    public function template()
    {
        return response()->download(base_path('docs/templates/category-attributes-colors-2026-11-13.xlsx'));
    }

    public function preview(Request $request, XlsxReader $reader, ImportService $service)
    {
        $file = $this->file($request);
        try {
            $report = $service->preview($reader->read($file->getRealPath()));
            $token = $report['blocked'] ? null : Crypt::encryptString(json_encode([
                'version' => 2, 'user' => (string) $request->user()->getAuthIdentifier(),
                'expires' => time() + 1800, 'file' => hash_file('sha256', $file->getRealPath()),
                'plan' => $report['fingerprint'],
            ], JSON_THROW_ON_ERROR));

            return view('admin.categories.import', compact('report', 'token'));
        } catch (ImportException $e) {
            return view('admin.categories.import', ['failure' => $e->getMessage()]);
        } catch (\Throwable) {
            return view('admin.categories.import', ['failure' => 'Предпросмотр недоступен из-за внутренней ошибки. Обратитесь к администратору.']);
        }
    }

    public function apply(Request $request, XlsxReader $reader, ImportService $service)
    {
        $request->validate(['token' => 'required|string|max:4096', 'confirmed' => 'accepted']);
        $file = $this->file($request);
        try {
            $proof = json_decode(Crypt::decryptString($request->string('token')->toString()), true, 16, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return view('admin.categories.import', ['failure' => 'Недействительное подтверждение. Повторите предпросмотр.']);
        }
        if (($proof['version'] ?? null) !== 2 || ($proof['user'] ?? null) !== (string) $request->user()->getAuthIdentifier()
            || ($proof['expires'] ?? 0) < time() || ! is_string($proof['file'] ?? null) || ! is_string($proof['plan'] ?? null)
            || ! hash_equals($proof['file'], hash_file('sha256', $file->getRealPath()))) {
            return view('admin.categories.import', ['failure' => 'Файл изменён или подтверждение истекло. Выполните предпросмотр заново.']);
        }
        try {
            $report = $service->apply($reader->read($file->getRealPath()), $proof['plan']);

            return view('admin.categories.import', ['report' => $report, 'applied' => true]);
        } catch (ImportException $e) {
            return view('admin.categories.import', ['failure' => $e->getMessage()]);
        } catch (\Throwable) {
            return view('admin.categories.import', ['failure' => 'Импорт не завершён из-за внутренней ошибки. Выполните новый предпросмотр для проверки состояния.']);
        }
    }

    private function file(Request $request): UploadedFile
    {
        $request->validate(['file' => 'required|file|max:2048|extensions:xlsx']);

        return $request->file('file');
    }
}
