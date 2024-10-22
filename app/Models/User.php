<?php

namespace App\Models;

use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Jetstream\HasProfilePhoto;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable // implements FilamentUser
{
    use HasRoles;
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasPanelShield;
    use HasProfilePhoto;
    use TwoFactorAuthenticatable;

    protected $connection = 'mysql'; // Use the sagansa database connection
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

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole('super_admin');
        } elseif ($panel->getId() === 'user') {
            return $this->hasRole('user');
        }

        return false;
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function (User $user) {
            $user->assignRole('user');
        });
    }
}
