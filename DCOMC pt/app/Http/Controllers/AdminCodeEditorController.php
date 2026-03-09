<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCodeEditorController extends Controller
{
    private const MAX_FILE_BYTES = 1024 * 1024; // 1MB

    /**
     * @return array<int, string>
     */
    private function allowedRoots(): array
    {
        $base = base_path();
        $blocked = $this->blockedTopLevel();
        $roots = [];

        foreach (@scandir($base) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $abs = $base . DIRECTORY_SEPARATOR . $name;
            if (! @is_dir($abs)) {
                continue;
            }
            if (in_array($name, $blocked, true)) {
                continue;
            }
            $roots[] = $name;
        }

        sort($roots, \SORT_NATURAL | \SORT_FLAG_CASE);

        return $roots;
    }

    /**
     * @return array<int, string>
     */
    private function blockedTopLevel(): array
    {
        return [
            'vendor',
            'node_modules',
            'storage',
            'bootstrap',
            '.git',
            '.idea',
            '.vscode',
        ];
    }

    private function isEnabled(): bool
    {
        // Default: enabled locally, disabled elsewhere unless explicitly set.
        $default = app()->environment('local');
        return (bool) env('ADMIN_CODE_EDITOR_ENABLED', $default);
    }

    public function index(Request $request): View
    {
        abort_unless($this->isEnabled(), 404);

        return view('dashboards.admin-system-editor', [
            'roots' => $this->allowedRoots(),
        ]);
    }

    public function tree(Request $request)
    {
        abort_unless($this->isEnabled(), 404);

        $rel = (string) $request->query('path', '');
        $resolved = $this->resolvePath($rel, allowNonExisting: false, mustBeDir: true);

        $items = [];
        foreach (@scandir($resolved['abs']) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            if ($resolved['rel'] === '' && in_array($name, $this->blockedTopLevel(), true)) {
                continue;
            }

            $childRel = ltrim($resolved['rel'] . '/' . $name, '/');
            if (! $this->isAllowedRelative($childRel)) {
                continue;
            }

            $childAbs = $resolved['abs'] . DIRECTORY_SEPARATOR . $name;
            $isDir = @is_dir($childAbs);
            $size = $isDir ? null : (@filesize($childAbs) ?: 0);
            $mtime = @filemtime($childAbs) ?: null;

            $items[] = [
                'name' => $name,
                'path' => $childRel,
                'type' => $isDir ? 'dir' : 'file',
                'size' => $size,
                'mtime' => $mtime,
            ];
        }

        usort($items, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return response()->json([
            'path' => $resolved['rel'],
            'items' => $items,
        ]);
    }

    public function readFile(Request $request)
    {
        abort_unless($this->isEnabled(), 404);

        $rel = (string) $request->query('path', '');
        $resolved = $this->resolvePath($rel, allowNonExisting: false, mustBeDir: false);
        abort_unless(@is_file($resolved['abs']), 404);

        $size = @filesize($resolved['abs']);
        if ($size !== false && $size > self::MAX_FILE_BYTES) {
            return response()->json([
                'error' => 'File too large to edit in-browser (max 1MB).',
                'maxBytes' => self::MAX_FILE_BYTES,
                'size' => $size,
            ], 413);
        }

        $content = (string) @file_get_contents($resolved['abs']);
        if (str_contains($content, "\0")) {
            return response()->json([
                'error' => 'Binary file cannot be edited.',
            ], 415);
        }

        return response()->json([
            'path' => $resolved['rel'],
            'language' => $this->guessLanguage($resolved['rel']),
            'content' => $content,
        ]);
    }

    public function writeFile(Request $request)
    {
        abort_unless($this->isEnabled(), 404);

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:5000'],
            'content' => ['required', 'string'],
        ]);

        $rel = (string) $validated['path'];
        $resolved = $this->resolvePath($rel, allowNonExisting: true, mustBeDir: false);

        abort_if(Str::startsWith($resolved['rel'], '.env'), 403, 'Editing .env is blocked.');

        $parentAbs = dirname($resolved['abs']);
        abort_unless(@is_dir($parentAbs), 422, 'Parent folder does not exist.');

        $bytes = strlen($validated['content']);
        if ($bytes > self::MAX_FILE_BYTES) {
            return response()->json([
                'error' => 'File too large to save (max 1MB).',
                'maxBytes' => self::MAX_FILE_BYTES,
                'size' => $bytes,
            ], 413);
        }

        abort_if(str_contains($validated['content'], "\0"), 415, 'Binary content is not allowed.');

        $ok = @file_put_contents($resolved['abs'], $validated['content'], LOCK_EX);
        abort_unless($ok !== false, 500, 'Failed to write file.');

        AuditLogger::log('system.editor.write', null, [
            'path' => $resolved['rel'],
            'bytes' => $bytes,
        ]);

        return response()->json([
            'ok' => true,
            'path' => $resolved['rel'],
            'bytes' => $bytes,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($this->isEnabled(), 404);

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'in:file,dir'],
        ]);

        $rel = (string) $validated['path'];
        $resolved = $this->resolvePath($rel, allowNonExisting: true, mustBeDir: false);

        abort_if(Str::startsWith($resolved['rel'], '.env'), 403, 'Creating .env is blocked.');

        if (File::exists($resolved['abs'])) {
            return response()->json(['error' => 'Path already exists.'], 409);
        }

        $ok = false;
        if ($validated['type'] === 'dir') {
            $ok = @mkdir($resolved['abs'], 0775, true);
        } else {
            $parentAbs = dirname($resolved['abs']);
            if (! @is_dir($parentAbs)) {
                $mk = @mkdir($parentAbs, 0775, true);
                abort_unless($mk, 500, 'Failed to create parent folder.');
            }
            $ok = @file_put_contents($resolved['abs'], '', LOCK_EX) !== false;
        }

        abort_unless($ok, 500, 'Failed to create.');

        AuditLogger::log('system.editor.create', null, [
            'path' => $resolved['rel'],
            'type' => $validated['type'],
        ]);

        return response()->json(['ok' => true, 'path' => $resolved['rel']]);
    }

    public function rename(Request $request)
    {
        abort_unless($this->isEnabled(), 404);

        $validated = $request->validate([
            'from' => ['required', 'string', 'max:5000'],
            'to' => ['required', 'string', 'max:5000'],
        ]);

        $from = $this->resolvePath((string) $validated['from'], allowNonExisting: false, mustBeDir: null);
        $to = $this->resolvePath((string) $validated['to'], allowNonExisting: true, mustBeDir: null);

        abort_if(Str::startsWith($from['rel'], '.env') || Str::startsWith($to['rel'], '.env'), 403, 'Editing .env is blocked.');

        abort_unless(File::exists($from['abs']), 404);
        abort_if(File::exists($to['abs']), 409, 'Target already exists.');

        $parentAbs = dirname($to['abs']);
        abort_unless(@is_dir($parentAbs), 422, 'Target parent folder does not exist.');

        $ok = @rename($from['abs'], $to['abs']);
        abort_unless($ok, 500, 'Failed to rename.');

        AuditLogger::log('system.editor.rename', null, [
            'from' => $from['rel'],
            'to' => $to['rel'],
        ]);

        return response()->json(['ok' => true, 'from' => $from['rel'], 'to' => $to['rel']]);
    }

    public function delete(Request $request)
    {
        abort_unless($this->isEnabled(), 404);

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:5000'],
        ]);

        $resolved = $this->resolvePath((string) $validated['path'], allowNonExisting: false, mustBeDir: null);
        abort_if(Str::startsWith($resolved['rel'], '.env'), 403, 'Deleting .env is blocked.');

        if (File::isFile($resolved['abs'])) {
            $ok = @unlink($resolved['abs']);
            abort_unless($ok, 500, 'Failed to delete file.');
        } elseif (File::isDirectory($resolved['abs'])) {
            // Safety: only delete empty directories via UI
            $children = @scandir($resolved['abs']) ?: [];
            $children = array_values(array_filter($children, fn ($n) => $n !== '.' && $n !== '..'));
            abort_if(! empty($children), 422, 'Directory is not empty.');

            $ok = @rmdir($resolved['abs']);
            abort_unless($ok, 500, 'Failed to delete directory.');
        } else {
            abort(404);
        }

        AuditLogger::log('system.editor.delete', null, [
            'path' => $resolved['rel'],
        ]);

        return response()->json(['ok' => true, 'path' => $resolved['rel']]);
    }

    private function guessLanguage(string $relPath): string
    {
        $relPathNorm = str_replace('\\', '/', $relPath);
        if (Str::endsWith($relPathNorm, '.blade.php')) {
            return 'html';
        }
        $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
        return match ($ext) {
            'php' => 'php',
            'js' => 'javascript',
            'ts' => 'typescript',
            'json' => 'json',
            'css' => 'css',
            'scss' => 'scss',
            'md' => 'markdown',
            'html' => 'html',
            'yml', 'yaml' => 'yaml',
            default => 'plaintext',
        };
    }

    /**
     * @return array{abs: string, rel: string}
     */
    private function resolvePath(string $rel, bool $allowNonExisting, bool|null $mustBeDir): array
    {
        $rel = str_replace('\\', '/', $rel);
        $rel = ltrim($rel, '/');
        $rel = preg_replace('#/+#', '/', $rel) ?? $rel;

        abort_if($rel === '', 422, 'Path is required.');
        abort_if(str_contains($rel, "\0"), 400, 'Invalid path.');
        abort_if(preg_match('#(^|/)\.\.(/|$)#', $rel) === 1, 400, 'Invalid path.');

        abort_unless($this->isAllowedRelative($rel), 403, 'Path is not allowed.');

        $abs = base_path($rel);

        // Prevent symlink escape: existing paths must resolve under base_path.
        if (file_exists($abs)) {
            $real = realpath($abs);
            abort_unless(is_string($real), 404);

            $base = realpath(base_path());
            abort_unless(is_string($base), 500);
            $base = rtrim(str_replace('\\', '/', $base), '/');
            $realNorm = str_replace('\\', '/', $real);

            abort_unless(Str::startsWith($realNorm, $base . '/'), 403, 'Path is not allowed.');

            if ($mustBeDir === true) {
                abort_unless(is_dir($real), 404);
            }
            if ($mustBeDir === false) {
                abort_unless(is_file($real), 404);
            }
        } else {
            abort_unless($allowNonExisting, 404);
            $parent = dirname($abs);
            $parentReal = realpath($parent);
            abort_unless(is_string($parentReal), 422, 'Parent folder does not exist.');

            $base = realpath(base_path());
            abort_unless(is_string($base), 500);
            $base = rtrim(str_replace('\\', '/', $base), '/');
            $parentNorm = str_replace('\\', '/', $parentReal);
            abort_unless(Str::startsWith($parentNorm, $base . '/'), 403, 'Path is not allowed.');
        }

        return ['abs' => $abs, 'rel' => $rel];
    }

    private function isAllowedRelative(string $rel): bool
    {
        $rel = str_replace('\\', '/', $rel);
        $rel = ltrim($rel, '/');
        $segments = explode('/', $rel);
        $top = $segments[0] ?? '';

        if ($top === '') {
            return false;
        }
        if (in_array($top, $this->blockedTopLevel(), true)) {
            return false;
        }
        if (! in_array($top, $this->allowedRoots(), true)) {
            return false;
        }
        if (Str::startsWith($rel, '.env')) {
            return false;
        }
        if (Str::contains($rel, '/.env')) {
            return false;
        }

        return true;
    }
}

