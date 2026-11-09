<?php

namespace KraftInk\Plugins\Staff\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Accounts\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin Builder<static>
 *
 * @property int $id
 * @property int|null $role_id
 * @property string $locale
 * @property string $title
 * @property string $color
 * @property int $position
 */
class RoleListing extends Model
{
    public const DEFAULT_COLOR = '#6c757d';

    public const COLOR_PATTERN = '/\A#[0-9a-fA-F]{6}\z/';

    public const COLOR_RULE = 'regex:'.self::COLOR_PATTERN;

    protected $table = 'staff__roles';

    protected $guarded = ['id'];

    protected $attributes = ['color' => self::DEFAULT_COLOR];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
            'position' => 'integer',
            'id' => 'integer',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'staff__member_roles', 'role_listing_id', 'member_id')
            ->using(MemberRole::class);
    }

    public function backgroundColor(): string
    {
        return preg_match(self::COLOR_PATTERN, $this->color ?? '') ? $this->color : self::DEFAULT_COLOR;
    }

    public function foregroundColor(): string
    {
        $channels = array_map(function (string $hex): float {
            $value = hexdec($hex) / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($this->backgroundColor(), 1), 2));
        [$red, $green, $blue] = $channels;
        $luminance = 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;

        return $luminance >= 0.179 ? '#000000' : '#ffffff';
    }
}
