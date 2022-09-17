# Laravel Tagged Cache

A fairly straight-forward wrapper around the cache implementation to reduce complexity regarding caching in your Models.

Note that this requires you to be using a Cache implementation that supports tags. It is **not supported** if you are using `file`, `dynamodb` or `database` 
cache drivers. My personal favorite cache store is `redis`, but `memcached` isn't bad either.  

# Installation

```shell
composer require brekitomasson/laravel-tagged-cache
```

# Usage

In any one of your Laravel Models, just `use` the trait `HasTaggedCache`, and you will be able to create reliable caches that do not require a bunch of 
special handling regarding your cache keys. No more `Cache::remember('user:' . $this->id . ':displayname)` or anything like that, as the heavy lifting is 
done inside the cache tags instead.

For example:

```php
public function getDisplayNameAttribute(): string
{
  return $this->taggedCache()->remember('display-name', now()->addHour(), fn () => $this->nickname ?? $this->name ?? $this->email);
}
```

This method, if in your `User` model, will store the cache key `display-name` with the tags `users` and `user:23` when used to get the `display_name` 
attribute for a user with the ID of 23 (or the value of whatever else that model's database key is set to). It uses the name of the database table connected to 
the model as the generic `users` tag, then uses the `Str::singular()` method of that name as well as the value of `id` for the given instance of the model 
to generate `user:23`.

To set your own name instead of using the name of the database table, you can define a `public string $cacheTagIdentifier = 'things'` in your Model and the 
contents of that attribute will be used instead, storing the above example with `things` and `thing:23`. Note, however, that it **has to be a string** and 
that a plural noun is recommended so that `Str::singular()` can do its job.

You can also provide any amount of additional strings to the `taggedCache()` method, and those strings will be added to the list of tags. If, for example, you 
put something like this in your `BlogEntry` model:

```php
public function getContent(): string
{
    return $this->taggedCache('markdown')->remember('content', now()->addHour(), fn () => $this->entry)
}
```

Then the `content` key will be tagged with `blog_entries`, `blog_entry:25`, and `markdown`, allowing you to easily `Cache::tags('markdown')->flush()` if 
you've just made changes to your implementation of Markdown to HTML conversion and want all Markdown-related caches in all models to be cleared.

# Advanced Usage and Recommendations

## Getting and Caching a Single Attribute

Instead of having to write `$this->taggedCache()->remember('name', TimeSpan::ONE_DAY(), fn () => $this->name)`, there's a simple helper function available 
if you're only interested in a single attribute. The code above can be rewritten as `$this->getCachedAttribute('name')`. The cache is stored with a key 
named after the attribute for 24 hours.

Under the hood, it uses Laravel's own `getAttribute()` method, which means you can use it to return relationships and attributes made available through 
Mutators and/or Accessor functions, and it takes your `$casts` into account.

## Cache Invalidation

[They say](https://martinfowler.com/bliki/TwoHardThings.html) that there are only two hard things in Computer Science: cache invalidation and naming things. 
This is, of course, always going to be true when you're storing things in your cache. Since this package makes it so much easier to cache things, however, you 
might be more willing to put things in your cache and run into some problems where the cache is returning values that are no longer correct. For that reason,
I suggest you do the following:

If you are using `HasTaggedCache` in your `User` model, create a `Listener` for the `UserHasLoggedIn` event to ensure your users always log in with none of 
their values cached. It doesn't have to be anything complicated, something like this is enough:

```php
class ClearUserCacheOnLogin implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function handle(\App\Events\Auth\UserHasLoggedIn $userHasLoggedIn): void
    {
        $userHasLoggedIn->user->taggedCache()->flush();
    }
}
```

Also, for whatever models you've added `HasTaggedCache` to, it is not a bad idea to create an `Observer` for the model where you flush caches depending on 
what contents of the model has changed. For example, you might want to clear the Caches for `BlogEntry` if `title` or `content` has changed, but not if 
`blog_category_id` has changed, since that value is never cached. Something like this in your `BlogEntryObserver` would do the trick:

```php
    public function updated(BlogEntry $blogEntry): void
    {
        if ($blogEntry->isDirty(['title', 'content'])) {
            $blogEntry->taggedCache()->flush();        
        }
    }
```

## Cache Duration Helper

Instead of relying on things like `now()->addDays(3)` when setting up how long a cache key should be remembered, this package contains an invokable and 
callable Enum class called `TimeSpan` that contains a whole host of pre-defined values, defined in seconds. Using this will save you the processing time 
required for `Carbon` to get  the current system time, add your given duration to it, and for Laravel to then calculate the difference between the current time 
and the returned time. It may not seem like an expensive operation, but it all adds up quite quickly if you're using it often enough. To use the `TimeSpan` 
Enum in your cache statements, just do something like this:

```php
public function getCountryNameAttribute(): string
{
    return $this->taggedCache()->remember('country-name', TimeSpan::ONE_WEEK(), fn() => $this->country->name);
}
```

If there is not an appropriate case defined in the Enum, such as if you want exactly 32 minutes, or if you think it looks cleaner to write it that way, there 
are static methods for `minutes`, `days`, `weeks` and `months` which you can call using syntax like `TimeSpan::minutes(32)` or `TimeSpan::weeks(9)`:

```php
public function getOpenTickets(): int
{
    return $this->taggedCache()->remember(
        'open-tickets',
        TimeSpan::minutes(5),
        fn() => $this->tickets->whereNotIn('status', [TicketStatus::CLOSED, TicketStatus::PENDING])->count();
    );
}
```

> Note: The `TimeSpan` class does not have values or methods for months, as they are of variable length and cannot adequately be defined in seconds.
> The longest duration available as an Enum case is `TimeSpan::FOUR_WEEKS()`. To get a value approximating three months, you can use something like 
> `TimeSpan::weeks(12)` or `TimeSpan::days(90)`.

# Future Development Ideas

A number of small changes and fixes are planned for the future (Pull Requests are always welcome!). These include:

- Is `TimeSpan` a good enough name for that class? I want to avoid potential naming conflicts, so I'm avoiding calling the class simply `Seconds` or 
  something like that, but I also want the name to be descriptive enough for what the class is all about, which `TimeSpan` doesn't necessarily feel like it is.
- Introduce an (optional) array that one can add to one's models, allowing `HasTaggedCache` to automatically flush cache for any given instance of a model 
  if one of the attributes mentioned in that array changes.

# Copyright / License

This package is distributed under an MIT license. See the `LICENSE` file for more information.
