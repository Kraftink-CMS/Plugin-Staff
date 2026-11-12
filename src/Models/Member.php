<?php

namespace KraftInk\Plugins\Staff\Models;

use App\Kraft\NavigationTargets;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use App\Models\Accounts\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin Builder<static>
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $locale
 * @property string $name
 * @property string $title
 * @property string|null $biography
 * @property string|null $avatar
 * @property string|null $website
 * @property array|null $social_links
 * @property int $position
 * @property bool $published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 */
class Member extends Model
{
    public const MAX_SOCIAL_LINKS = 12;

    public const SOCIAL_ICONS = [
        'link-45deg',
        'globe',
        'discord',
        'github',
        'youtube',
        'twitch',
        'twitter-x',
        'instagram',
        'tiktok',
        'facebook',
        'linkedin',
        'steam',
        'reddit',
    ];

    protected $table = 'staff__members';

    protected $guarded = ['id'];

    public static function socialLinkRules(): array
    {
        return [
            'social_links' => ['sometimes', 'array', 'list', 'max:'.self::MAX_SOCIAL_LINKS],
            'social_links.*' => ['required', 'array:label,url,icon'],
            'social_links.*.label' => ['required', 'string', 'max:80'],
            'social_links.*.url' => [
                'bail',
                'required',
                'string',
                'max:2000',
                'url:https',
                function (string $attribute, string $value, Closure $fail): void {
                    if (!NavigationTargets::validUrl($value)) {
                        $fail(__('validation.url', ['attribute' => __('staff::profiles.link_url')]));
                    }
                },
            ],
            'social_links.*.icon' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9-]*$/D'],
        ];
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'social_links' => 'array',
            'position' => 'integer',
            'published' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(RoleListing::class, 'staff__member_roles', 'member_id', 'role_listing_id')
            ->using(MemberRole::class)
            ->orderBy('staff__roles.position')
            ->orderBy('staff__roles.id');
    }
}
