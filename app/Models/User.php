<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Enums\AccentColor;
use App\Enums\Locale;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property string $timezone
 * @property Locale $locale
 * @property AccentColor $accent_color
 * @property bool $digest_enabled
 * @property string $digest_time
 * @property CarbonInterface|null $digest_last_sent_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable([
    'name',
    'email',
    'password',
    'current_team_id',
    'timezone',
    'locale',
    'accent_color',
    'digest_enabled',
    'digest_time',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timezone' => 'UTC',
        'locale' => 'en',
        'accent_color' => 'neutral',
        'digest_enabled' => false,
        'digest_time' => '07:00:00',
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
            'two_factor_confirmed_at' => 'datetime',
            'locale' => Locale::class,
            'accent_color' => AccentColor::class,
            'digest_enabled' => 'boolean',
            'digest_last_sent_on' => 'date',
        ];
    }

    /**
     * Get the user's preferred locale, used for emails and notifications.
     */
    public function preferredLocale(): string
    {
        return $this->locale->value;
    }

    /**
     * Get the current time in the user's timezone.
     */
    public function localNow(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone);
    }

    /**
     * Get the start of today in the user's timezone.
     */
    public function today(): CarbonImmutable
    {
        return $this->localNow()->startOfDay();
    }
}
