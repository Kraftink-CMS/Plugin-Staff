<?php

namespace KraftInk\Plugins\Staff\Models;

use Illuminate\Support\Carbon;
use App\Kraft\Auth\PermissionAccess;
use App\Models\Accounts\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder<static>
 *
 * @property int $id
 * @property string $locale
 * @property string $slug
 * @property string $title
 * @property string $body
 * @property string|null $category
 * @property int $position
 * @property string|null $permission
 * @property bool $published
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Document extends Model
{
    protected $table = 'staff__documents';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'published' => 'boolean',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'id' => 'integer',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRevision::class, 'document_id');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $query->whereNull('deleted_at')->where('locale', app()->getLocale())->where('published', true);
        $access = app(PermissionAccess::class);

        return $access->filterQuery($query, 'permission', $access->permissions($user), 'staff.view');
    }

    public function recordRevision(int $actor): DocumentRevision
    {
        return $this->revisions()->create([
            'user_id' => $actor,
            'snapshot' => $this->attributesToArray(),
        ]);
    }
}
