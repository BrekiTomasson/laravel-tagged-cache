<?php

declare(strict_types = 1);

namespace BrekiTomasson\LaravelTaggedCache;

use Illuminate\Cache\TaggedCache;
use Illuminate\Support\Facades\Cache;

/**
 * Simplify handling of Cache Tags for Laravel Models.
 *
 * @property string $cacheTagIdentifier Optional tag to use instead of the model name.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasTaggedCache
{
    public function getCachedAttribute(string $attribute): mixed
    {
        return $this->taggedCache()->remember(
            $attribute,
            TimeSpan::days(1),
            fn () => $this->getAttribute($attribute),
        );
    }

    public function taggedCache(...$extraTags): TaggedCache
    {
        return Cache::tags($this->btltcCacheTags(...$extraTags));
    }

    /** @return array<int, int|string> */
    protected function btltcCacheTags(...$extraTags): array
    {
        return [
            $this->btltcGetCacheTagsTableName(),
            str($this->btltcGetCacheTagsTableName())
                ->singular()
                ->append(':')
                ->append($this->btltcGetCacheTagsUniqueIdentifier())
                ->toString(),
            ...$extraTags,
        ];
    }

    protected function btltcGetCacheTagsTableName(): string
    {
        return (new static())->getTable();
    }

    private function btltcGetCacheTagsUniqueIdentifier(): string
    {
        $identifier = ($this->getAttribute($this->cacheTagIdentifier) ?? $this->getKey());

        return str($identifier)->snake()->toString();
    }
}
