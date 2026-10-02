<?php

namespace App\Models;

use Database\Factories\StudentGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['academic_year_id', 'level_id', 'name', 'code', 'capacity', 'color'])]
class StudentGroup extends Model
{
    /** @use HasFactory<StudentGroupFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    public const PALETTE = [
        '#d0123c',
        '#2563eb',
        '#059669',
        '#d97706',
        '#7c3aed',
        '#0891b2',
        '#db2777',
        '#4f46e5',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
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
     * @return BelongsTo<Level, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<GroupAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(GroupAssignment::class);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function calendarColor(): string
    {
        $color = strtolower((string) $this->color);

        return preg_match('/^#[0-9a-f]{6}$/', $color) === 1 ? $color : '#64748b';
    }

    public static function nextColor(int $academicYearId): string
    {
        $used = static::query()->where('academic_year_id', $academicYearId)->pluck('color');

        foreach (self::PALETTE as $color) {
            if (! $used->contains($color)) {
                return $color;
            }
        }

        return self::PALETTE[$used->count() % count(self::PALETTE)];
    }
}
