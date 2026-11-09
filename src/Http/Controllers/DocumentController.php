<?php

namespace KraftInk\Plugins\Staff\Http\Controllers;

use App\Kraft\System\TableQuery;
use App\Kraft\Html;
use App\Kraft\Site\Seo;
use App\Models\Accounts\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use KraftInk\Plugins\Staff\Models\Document;
use KraftInk\Plugins\Staff\Models\DocumentRevision;
use KraftInk\Plugins\Staff\Services\Directory;
use KraftInk\Plugins\Staff\Services\DisplaySettings;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DocumentController
{
    public const string PLUGIN = 'staff';

    public function index(Request $request, Directory $directory, DisplaySettings $settings): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = Document::query()->visibleTo($request->user());
        if ($request->filled('q')) {
            $query->where(function ($query) use ($request) {
                $query
                    ->whereLike('title', '%'.$request->string('q').'%')
                    ->orWhereLike('body', '%'.$request->string('q').'%');
            });
        }
        $documents = $query->orderBy('position')->orderBy('title')->paginate(30)->withQueryString();
        $display = $settings->get();
        $members = $directory->members($request->input('q'), $request->user(), grouped: $display['grouped']);
        $memberGroups = $display['grouped']
            ? $members->getCollection()->groupBy(fn ($member) => $member->badges->first()?->id ?? 'other')
            : collect([$members->getCollection()]);

        return view('staff::default.documents.index', [
            'documents' => $documents,
            'plugin' => static::PLUGIN,
            'members' => $members,
            'memberGroups' => $memberGroups,
            'display' => $display,
            'seo' => ['page_parameter' => [$documents->getPageName(), $members->getPageName()]],
            'seoVariables' => [
                'members_count' => $members->total(),
                'documents_count' => $documents->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $document = Document::query()->visibleTo($request->user())->where('slug', $slug)->first();
        if (!$document) {
            abort(404);
        }

        return view('staff::default.documents.show', [
            'document' => $document,
            'plugin' => static::PLUGIN,
            'seo' => [
                'excerpt' => $document->body,
                'robots' => $document->permission ? Seo::NO_INDEX : '',
            ],
            'seoVariables' => [
                'document' => $document->title,
                'document_description' => $document->permission ? '' : $document->body,
            ],
        ]);
    }

    public function admin(Request $request): View
    {
        $data = $request->validate(['state' => ['nullable', Rule::in(['published', 'draft'])]]);
        $query = Document::query()->whereNull('deleted_at');
        if (!empty($data['state'])) {
            $query->where('published', $data['state'] === 'published');
        }

        return view('staff::admin.documents.index', [
            'documents' => TableQuery::paginate(
                $query->orderBy('position'),
                ['title' => 'title', 'locale' => 'locale', 'published' => 'published'],
                pageName: 'page',
                search: ['title'],
            ),
            'plugin' => static::PLUGIN,
        ]);
    }

    public function edit(?int $id = null): View
    {
        $document = $id ? Document::query()->whereNull('deleted_at')->find($id) : null;
        if ($id && !$document) {
            abort(404);
        }

        return view('staff::admin.documents.edit', ['document' => $document, 'plugin' => static::PLUGIN]);
    }

    public function save(Request $request, ?int $id = null): RedirectResponse
    {
        $document = $id ? Document::query()->whereNull('deleted_at')->find($id) : null;
        if ($id && !$document) {
            abort(404);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => [
                'required',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'max:180',
                Rule::unique('staff__documents')
                    ->where(fn ($query) => $query->where('locale', $request->input('locale')))
                    ->ignore($id),
            ],
            'body' => ['required', 'string', 'max:100000'],
            'locale' => ['required', Rule::in(array_keys(config('kraft.locales')))],
            'category' => ['nullable', 'string', 'max:120'],
            'position' => ['required', 'integer', 'min:0', 'max:100000'],
            'permission' => ['nullable', 'regex:/^[a-zA-Z0-9_.-]+$/', 'max:100'],
            'published' => ['sometimes', 'boolean'],
        ]);
        $data['body'] = app(Html::class)->clean($data['body']);
        $data['published'] = $request->boolean('published');
        $data['updated_at'] = now();
        DB::transaction(function () use ($data, $document, $id, $request) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($document) {
                $document = Document::query()->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            }
            if ($id && !$document) {
                abort(404);
            }
            if ($document) {
                $document->recordRevision($request->user()->id);
                Document::query()->where('id', $id)->update($data);
            } else {
                Document::query()->insert($data + ['created_at' => now()]);
            }
        });

        return redirect()
            ->route('plugins.staff.admin.documents.index')
            ->with('status', __('staff::messages.common.saved'));
    }

    public function revisions(int $id): View
    {
        $document = Document::query()->whereNull('deleted_at')->find($id);
        if ($document === null) {
            abort(404);
        }

        return view('staff::admin.documents.revisions', [
            'document' => $document,
            'revisions' => TableQuery::paginate(
                DocumentRevision::query()->where('document_id', $id)->orderByDesc('id'),
                ['created_at' => 'created_at'],
                pageName: 'page',
                search: [],
            ),
            'plugin' => static::PLUGIN,
        ]);
    }

    public function restore(Request $request, int $id, int $revision): RedirectResponse
    {
        DB::transaction(function () use ($request, $id, $revision) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $document = Document::query()->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            $record = $document?->revisions()->find($revision);
            if (!($document && $record)) {
                abort(404);
            }
            $snapshot = $record->snapshot;
            if (!empty($snapshot['anonymized'])) {
                throw new HttpException(410, __('staff::messages.privacy.revision_removed'));
            }
            $data = array_intersect_key(
                $snapshot,
                array_flip(['title', 'body', 'category', 'position', 'permission', 'published']),
            );
            $data['body'] = Html::clean($data['body'] ?? '');
            $data['updated_at'] = now();
            $document->recordRevision($request->user()->id);
            Document::query()->where('id', $id)->update($data);
        });

        return redirect()
            ->route('plugins.staff.admin.documents.edit', $id)
            ->with('status', __('staff::messages.common.restored'));
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $document = Document::query()->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            if ($document === null) {
                abort(404);
            }
            Document::query()
                ->where('id', $id)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        }, 3);

        return redirect()
            ->route('plugins.staff.admin.documents.index')
            ->with('status', __('site.content.moved_to_trash'));
    }
}
