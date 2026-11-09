<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $fillable = ['key', 'value_json', 'description'];

    /** @var array<string, mixed>|null */
    private static ?array $bag = null;

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }

    public static function flushBag(): void
    {
        self::$bag = null;
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (self::$bag === null) {
            // ponytail: one SELECT per request
            self::$bag = static::query()->pluck('value_json', 'key')->all();
        }

        return self::$bag[$key] ?? $default;
    }

    public static function setValue(string $key, mixed $value, ?string $description = null): void
    {
        $row = static::query()->firstOrNew(['key' => $key]);
        $row->value_json = $value;
        if ($description !== null) {
            $row->description = $description;
        }
        $row->save();

        self::$bag ??= [];
        self::$bag[$key] = $value;
    }

    public function displayValue(): string
    {
        $value = $this->value_json;

        return is_scalar($value) || $value === null
            ? (string) $value
            : json_encode($value);
    }
}
