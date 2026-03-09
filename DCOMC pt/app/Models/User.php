<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'department_id',
    'department_scope',
    'created_by_role',
    'created_by_user_id',
    'school_id',
    'year_level',
    'semester',
    'school_year',
    'block_id',
    'shift',
    'last_name',
    'first_name',
    'middle_name',
    'gender',
    'date_of_birth',
    'place_of_birth',
    'civil_status',
    'citizenship',
    'phone',
    'college',
    'course',
    'major',
    'units_enrolled',
    'student_status',
    'student_type',
    'previous_program',
    'status_color',
    'is_freshman',
    'high_school',
    'hs_graduation_date',
    'father_name',
    'father_occupation',
    'mother_name',
    'mother_occupation',
    'annual_income',
    'monthly_income',
    'num_siblings',
    'num_family_members',
    'dswd_household_no',
    'registration_remarks',
    'emergency_contact_name',
    'emergency_contact_phone',
    'house_number',
    'street',
    'purok_zone',
    'barangay',
    'municipality',
    'province',
    'zip_code',
    'boarding_house_number',
    'boarding_street',
    'boarding_barangay',
    'boarding_municipality',
    'boarding_province',
    'boarding_phone',
    'profile_completed',
    'faculty_type',
    'program_scope',
    'max_units',
    'schedule_selection_limit',
    'assigned_units',
    'accounting_access',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'accounting_access' => 'boolean',
            'assigned_units' => 'integer',
            'max_units' => 'integer',
        ];
    }

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STUDENT = 'student';
    public const ROLE_REGISTRAR = 'registrar';
    public const ROLE_STAFF = 'staff';
    public const ROLE_UNIFAST = 'unifast';
    public const ROLE_DEAN = 'dean';
    public const ROLE_ACCOUNTING = 'staff';

    public static function roles(): array
    {
        return [
            self::ROLE_STUDENT,
            self::ROLE_ADMIN,
            self::ROLE_REGISTRAR,
            self::ROLE_STAFF,
            self::ROLE_UNIFAST,
            self::ROLE_DEAN,
        ];
    }

    public static function nonAdminRoles(): array
    {
        return array_values(array_filter(self::roles(), fn (string $role) => $role !== self::ROLE_ADMIN));
    }

    public function effectiveRole(): string
    {
        $switch = session('role_switch');

        if (
            $this->isAdmin() &&
            is_array($switch) &&
            ($switch['active'] ?? false) === true &&
            in_array($switch['as_role'] ?? '', self::nonAdminRoles(), true)
        ) {
            return $switch['as_role'];
        }

        return $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    /** For irregular students: additional block assignments (can be in multiple blocks/year levels). */
    public function blockAssignments()
    {
        return $this->hasMany(StudentBlockAssignment::class);
    }

    /** True if student type is Irregular/Shifter or status_color is yellow. */
    public function isIrregularType(): bool
    {
        if ($this->role !== self::ROLE_STUDENT) {
            return false;
        }
        return in_array($this->student_type, ['Irregular', 'Shifter'], true)
            || (string) ($this->status_color ?? '') === 'yellow';
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Program/course for display and matching: block's program when assigned, else user's course.
     */
    public function getResolvedProgramAttribute(): ?string
    {
        $block = $this->relationLoaded('block') ? $this->block : $this->block()->first();
        return $block?->program ?? $this->course;
    }

    /**
     * Year level for display and matching: block's year_level when assigned, else user's year_level.
     */
    public function getResolvedYearLevelAttribute(): ?string
    {
        $block = $this->relationLoaded('block') ? $this->block : $this->block()->first();
        return $block?->year_level ?? $this->year_level;
    }

    /**
     * Semester for display and matching: block's semester when assigned, else user's semester.
     */
    public function getResolvedSemesterAttribute(): ?string
    {
        $block = $this->relationLoaded('block') ? $this->block : $this->block()->first();
        return $block?->semester ?? $this->semester;
    }

    public function teachingSchedules()
    {
        return $this->hasMany(ClassSchedule::class, 'professor_id');
    }

    public function subjectAssignments()
    {
        return $this->hasMany(ProfessorSubjectAssignment::class, 'professor_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'user_id');
    }

    /**
     * Form responses submitted by the user (if any).
     */
    public function formResponses()
    {
        return $this->hasMany(\App\Models\FormResponse::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Subject completion history (passed, failed, dropped, credited) for irregular enrollment validation.
     */
    public function subjectCompletions()
    {
        return $this->hasMany(StudentSubjectCompletion::class, 'student_id');
    }
}
