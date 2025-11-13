<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable // implements FilamentUser
{
    use UsesDefaultConnectionWhenTesting;

    use HasRoles;
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasPanelShield;
    use HasProfilePhoto;
    use TwoFactorAuthenticatable;

    protected $connection = 'sagansa'; // Use the sagansa database connection
    protected $table = 'users';

    // protected $connection = 'sagansa'; // Use the sagansa database connection
    // protected $table = 'users'; // Table name in the sagansa database

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['profile_photo_url'];

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
        ];
    }

    /**
     * Get all of the chargerLocations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chargerLocations()
    {
        return $this->hasMany(ChargerLocation::class);
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }

    /**
     * Get all of the stateOfHealths.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stateOfHealths()
    {
        return $this->hasMany(StateOfHealth::class);
    }

    /**
     * Get all of the vehicles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get all of the charges.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * Get all of the discountHomeChargings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function discountHomeChargings()
    {
        return $this->hasMany(DiscountHomeCharging::class);
    }

    public function contributorProfile()
    {
        return $this->hasOne(ContributorProfile::class);
    }

    public function locationReports()
    {
        return $this->hasMany(LocationReport::class, 'reporter_id');
    }

    public function locationUpdates()
    {
        return $this->hasMany(LocationUpdate::class, 'contributor_id');
    }

    public function verifiedLocations()
    {
        return $this->hasMany(ChargerLocation::class, 'verified_by');
    }

    public function processedReports()
    {
        return $this->hasMany(LocationReport::class, 'processed_by');
    }

    public function approvedUpdates()
    {
        return $this->hasMany(LocationUpdate::class, 'approved_by');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole('super_admin');
        } elseif ($panel->getId() === 'user') {
            return $this->hasRole('user');
        }

        return false;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function () {
            static::ensureDefaultRoleExists();
        });

        static::created(function (User $user) {
            static::assignDefaultRole($user);
        });
    }

    protected static function ensureDefaultRoleExists(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $guard = config('auth.defaults.guard', 'web');
        $role = Role::query()->firstOrCreate([
            'name' => 'user',
            'guard_name' => $guard,
        ]);

        if ($role->wasRecentlyCreated) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    protected static function assignDefaultRole(User $user): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }
    }
}
