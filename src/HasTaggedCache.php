<?php

declare(strict_types=1);

namespace BrekiTomasson\LaravelTaggedCache;

/**
 * Simplify handling of Cache Tags for Laravel Models.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasTaggedCache
{
    /** Set up the various hooks required for cache flushing etc. */
    public function bootHasTaggedCache(): void
    {
        static::updated(static function (self $model): void {
            if ($model->isDirty($model->flushTaggedCacheOnAttributeUpdate())) {
                $model->taggedCache()->flush();
            }
        });

        static::deleted(static function (self $model): void {
            $model->taggedCache()->flush();
        });
    }

    /** Model Attributes that cause the cache to be flushed. */
    public function flushTaggedCacheOnAttributeUpdate(): array
    {
        return [];
    }

    public function getCachedAttribute(string $attribute): mixed
    {
        return $this->taggedCache()->remember(
            $attribute,
            TimeSpan::days(1),
            fn () => $this->getAttribute($attribute),
        );
    }

    /** The name to base the cache tags on. Defaults to the model's database table. */
    public function getCacheTagIdentifier(): string
    {
        return (new static())->getTable();
    }

    public function taggedCache(...$extraTags): \Illuminate\Cache\TaggedCache
    {
        return \Illuminate\Support\Facades\Cache::tags($this->btltcCacheTags(...$extraTags));
    }

    protected function btltcCacheTag(): \Illuminate\Support\Stringable
    {
        return str($this->getCacheTagIdentifier())->lower()->snake();
    }

    /** @return array<int, int|string> */
    protected function btltcCacheTags(...$extraTags): array
    {
        return [
            $this->btltcCacheTag()->toString(),
            $this->btltcCacheTag()
                ->singular()
                ->append(':')
                ->append($this->btltcGetCacheTagsUniqueIdentifier())
                ->toString(),
            ...$extraTags,
        ];
    }

    private function btltcGetCacheTagsUniqueIdentifier(): string
    {
        return str((string) $this->getKey())->snake()->toString();
    }
}
