<?php

namespace App\Casts;

use App\Data\Recurrence;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<Recurrence|null, Recurrence|array<string, mixed>|null>
 */
class AsRecurrence implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Recurrence
    {
        if ($value === null) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : null;

        return is_array($data) ? Recurrence::fromArray($data) : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = Recurrence::fromArray($value);
        }

        return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
    }
}
