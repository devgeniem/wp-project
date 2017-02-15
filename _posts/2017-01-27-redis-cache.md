---
layout: page
title: "Redis Cache"
category: optimization
date: 2017-01-27 13:34:37
---

## Redis Object Cache

We use this dropin to override the `WP_Object_Cache` class and the caching functions related to it. The dropin extends the object cache and uses Redis for caching.

When you use caching functions described [in the codex](https://codex.wordpress.org/Class_Reference/WP_Object_Cache), the keys and the data is stored in Redis with the expire time ranging from 0 _(infinite)_ to the set expire time in seconds. [WordPress provides constants](https://codex.wordpress.org/Easier_Expression_of_Time_Constants) for easier expression of time intervals to be used in caching functions.

[Documentation](https://github.com/devgeniem/wp-redis-object-cache-dropin)

## Redis Group Cache

Usually the issue with caching is data invalidation when data is modified. This plugin solves this problem with a group caching functionality.

The plugin extends `Redis Object Cache` and provides automatic group cache setting and a method to invalidate a whole cache group.

Setting a group cache is done automatically when the `wp_cache_set` is called with a group key parameter.

```
wp_cache_set( 'my_cache_key', $data, 'my_cache_group', HOUR_IN_SECONDS );
```

If you want to delete multiple keys and the data stored with them simultaneously, call the `delete_group` function with the designated group key.

```
\Geniem\Group_Cache::delete_group( 'my_cache_group' );
```

Example usage in a theme or a plugin:

```
function my_data() {
  if ( $cache = wp_cache_get( 'my_cache_key', 'my_cache_group' ) ) {
    return $cache;
  }
  $data = get_data();
  wp_cache_set( 'my_cache_key', $data, 'my_cache_group', HOUR_IN_SECONDS );
  return data;
}
```

[Documentation](https://github.com/devgeniem/wp-redis-group-cache)