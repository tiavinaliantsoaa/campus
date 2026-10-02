<?php

namespace App\Models;

use App\Enums\Audience;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['academic_year_id', 'author_id', 'title', 'body', 'audience', 'published_at', 'expires_at'])]
class Announcement extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => Audience::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return HasMany<AnnouncementTarget, $this>
     */
    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now())
            && ($this->expires_at === null || $this->expires_at->gte(now()));
    }

    /**
     * @param  Builder<Announcement>  $query
     * @param  list<int>  $roleIds
     * @param  list<int>  $groupIds
     * @return Builder<Announcement>
     */
    #[Scope]
    protected function visibleTo(Builder $query, array $roleIds, array $groupIds): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function (Builder $query) use ($roleIds, $groupIds): void {
                $query->where('audience', Audience::Everyone->value);

                if ($roleIds !== []) {
                    $query->orWhere(function (Builder $query) use ($roleIds): void {
                        $query->where('audience', Audience::Role->value)
                            ->whereHas('targets', function (Builder $targets) use ($roleIds): void {
                                $targets->where('target_type', 'role')->whereIn('target_id', $roleIds);
                            });
                    });
                }

                if ($groupIds !== []) {
                    $query->orWhere(function (Builder $query) use ($groupIds): void {
                        $query->where('audience', Audience::Group->value)
                            ->whereHas('targets', function (Builder $targets) use ($groupIds): void {
                                $targets->where('target_type', 'group')->whereIn('target_id', $groupIds);
                            });
                    });
                }
            });
    }
}
