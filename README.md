# Laravel Tagged Cache

A fairly straight-forward wrapper around the cache implementation to reduce complexity regarding caching in your Models.
It has no external dependencies or configuration, and should "just work" out of the box.

Note, however, that this package requires you to be using a Cache implementation that supports tags. This means that it 
**will not work** if you are using `file`, `dynamodb` or `database` cache drivers. 

# Installation

```shell
composer require brekitomasson/laravel-tagged-cache
```

# Usage

In any Laravel Models, just `use BrekiTomasson\LaravelTaggedCache\HasTaggedCache`, and you will be able to create
reliable caches that do not require a bunch of special handling regarding your cache keys. Instead of needing to come
up with complex naming rules for your cache keys, you can use the same cache key for everything, as the differentiation
will be done using the cache tags instead. This allows you to do things like:

```php
public function getDisplayNameAttribute(): string
{
  return $this->taggedCache()->remember(
      key: 'displayname', 
      ttl: now()->addHour(), 
      callback: fn () => $this->nickname ?? $this->name ?? $this->email,
  );
}
```

This method, if in your `User` model, will store the cache key `displayname` with the tags `users` and `user:23` when
used to get the `display_name` attribute for a user with the ID of 23.

You can also provide any amount of additional strings to the `taggedCache()` method, and those strings will be added to
the list of tags. If, for example, you put something like this in your `BlogEntry` model:

```php
public function getContentHtml(): string
{
    return $this->taggedCache('markdown')->remember(
        key: 'content:html',
        ttl: now()->addHour(),
        callback: fn () => Markdown::parse($this->content)->toHtml(),
    );
}
```

Then the `content:html` key will be tagged with `blog_entries`, `blog_entry:25`, and `markdown`. This allows you to do
`Cache::tags('markdown')->flush()` if you've just made changes to your Markdown implementation and want all 
Markdown-related caches in all models to be cleared. Since a cache key will only get a hit if **all** tags match, this
means that anything tagged with `markdown` in this way will automatically be forgotten, no matter which model it may
be connected to.

# Advanced Usage and Recommendations

## 1 - Cache Invalidation _(optional, but recommended!)_

[They say](https://martinfowler.com/bliki/TwoHardThings.html) that there are only two hard things in Computer Science:
cache invalidation and naming things. Since this package makes it so much easier to cache things, you might be more
willing to put things in your cache than you're used to, meaning you'll be more prone to get cache-related problems.

The normal way of working would be to build Observers that track changes to your models and flush caches based on which
attributes have been modified, but this package offers a shortcut. By implementing a single method in your model, all
caches for that specific instance of that model will be flushed. For example, if you were to put this in your Model:

```php
public function flushTaggedCacheOnAttributeUpdate(): array
{
    return ['name', 'display_name', 'status'];
}
```

With this method in place, if you were to `ModelName::find(132)->update(['display_name' => 'ACME Systems']);` the
package would automatically flush all caches related to that row, as the attribute `display_name` is listed inside the
array returned by `flushTaggedCacheOnAttributeUpdate()`. Caches for other rows in that model will remain in place. This
is the equivalent of building a Model Observer with a method like:

```php
public function updated(ModelName $model): void
{
    if ($model->isDirty(['name', 'display_name', 'status'])) {
        $model->taggedCache()->flush();
    }
}
```

> Note: Caches will **always** be flushed for rows that are deleted. This behavior cannot be disabled.

## 2 - Getting and Caching a Single Attribute

Instead of having to write `$this->taggedCache()->remember('name', now()->addDay(), fn () => $this->name)` to get a
cached instance of a single attribute from a model, there's a simple helper function available. The code above can be
rewritten: `$this->getCachedAttribute('name')`. The cache is stored with a key named after the attribute for 24 hours.

Under the hood, it uses Laravel's own `getAttribute()` method, which means you can use it to return relationships and
attributes made available through Mutators and/or Accessor functions, and it takes your `$casts` into account.

## 3 - Personalizing the Name of the Cache Tags

By default, the name of the database table underlying the Model will be used as a basis for the Cache tags. If your
model is `Country` and the underlying database table is `countries`, then `Country::find(23)->taggedCache()` will use
the tags `countries` and `country:23`. The latter is generated using Laravel's `Str::singular()` method.

To override what base name to use in your cache tags, you can implement the `getCacheTagIdentifier()` method in your
model. Any string returned by this method will automatically be converted into snake_case, but please try to keep any
implementation of this to a plural string. For example:

```php
public function getCacheTagIdentifier(): string
{
    return 'Comic Books';
}
```

If this method were in your model, then `Model::find(42)->taggedCache()` would use the tags `comic_books` and
`comic_book:42`.

## 4 - Cache Duration Helper

Instead of relying on things like `now()->addDays(3)` when setting up how long a cache key should be remembered, this
package contains an invokable and callable Enum class called `TimeSpan` that contains a whole host of pre-defined
values, defined in seconds. Using this will save you the processing time required for `Carbon` to get the current
system time, add your given duration to it, and for Laravel to then calculate the difference between the current time
and the returned time. It may not seem like an expensive operation, but it all adds up quite quickly if you're using it
often enough. To use the `TimeSpan` Enum in your cache statements, just do something like this:

```php
public function getCountryNameAttribute(): string
{
    return $this->taggedCache()->remember(
        'country-name',
        TimeSpan::ONE_WEEK(),
        fn() => $this->country->name
    );
}
```

If there is not an appropriate case defined in the Enum, such as if you want exactly 32 minutes, or if you think it
looks cleaner to write it that way, there are static methods for `minutes`, `days`, and `weeks` which you can invoke
using syntax like `TimeSpan::minutes(32)` or `TimeSpan::weeks(9)`. These always return the appropriate number of seconds
as an integer.

```php
public function getOpenTicketCount(): int
{
    return $this->taggedCache()->remember(
        'open-tickets',
        TimeSpan::minutes(3),
        fn() => $this->tickets->whereNotIn('status', [TicketStatus::CLOSED, TicketStatus::PENDING])->count();
    );
}
```

> Note: The `TimeSpan` class does not have values or methods for months, as these are of variable length and cannot
> adequately be defined in seconds. The longest duration available as an Enum case is `TimeSpan::FOUR_WEEKS()`. To get
> a value approximating three months, you can use something like `TimeSpan::weeks(12)` or `TimeSpan::days(90)`.

# Future Development Ideas

These are things I've been thinking about when it comes to future development for this package. Some things listed
below will be implemented, others won't. Pull Requests and suggestions are **always** welcome.

- Is `TimeSpan` a good enough name for that class? I want to avoid potential naming conflicts, so I'm avoiding calling
  the class simply `Seconds` or something like that, but I also want the name to be descriptive enough for what the
  class is all about, which `TimeSpan` doesn't necessarily feel like.
- Does `TimeSpan` really have to be an `Enum`? Feels like most of what I'm doing there can be done with just a normal
  class, and maybe even in a better way than currently.
- Add ability to flush model caches on **any** change, not just to attributes listed in overridden method.

# Copyright / License

This package is distributed under an MIT license. See the `LICENSE` file for more information.
