<?php

declare(strict_types=1);

namespace Engelsystem\Models;

use Carbon\Carbon;
use Engelsystem\Models\User\User;
use Engelsystem\Models\User\UsesUserModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int                           $id
 * @property Carbon|null                   $birthday
 * @property string                        $address
 * @property int                           $zip
 * @property string                        $food
 * @property int                           $preferred_type
 * @property bool                          $driver_license
 * @property string                        $can_bring
 * @property string                        $my_best_experience
 * @property string                        $note
 * @property Carbon|null                   $created_at
 * @property Carbon|null                   $updated_at
 * @property-read User                     $user
 */
class Gagga extends BaseModel
{
    use HasFactory;
    use UsesUserModel;

    /** @var bool Enable timestamps */
    public $timestamps = true; // phpcs:ignore

    protected $table = 'gagga_survey';

    /** @var array<string, string> */
    protected $casts = [ // phpcs:ignore
        'user_id'      => 'integer',
    ];

    /** @var array<string, bool> Default attributes */
    protected $attributes = [ // phpcs:ignore
    ];

    /** @var array<string> */
    protected $fillable = [ // phpcs:ignore
        'address',
        'zip',
        'preferred_type',
        'food',
        'birthday',
        'driver_license',
        'can_bring',
        'my_best_experience',
        'note',
    ];

    /** @var array<string> The attributes that should be mutated to dates */
    protected $dates = [ // phpcs:ignore
        'birthday',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function required($id): bool
    {
        return Gagga::whereUserId($id)->count() == 0;
    }

}
