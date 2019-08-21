# Arha Routes

Wordpress plugin that serves content through REST routes.

## Available Routes

- `/wp-json/arha/v1/post?post_type=POST_TYPE&slug=LUG`
- `/wp-json/arha/v1/page?path=PATH`
- `/wp-json/arha/v1/options`
- `/wp-json/arha/v1/archive?post_type=POST_TYPE&posts_per_page=POSTS_PER_PAGE&paged=PAGED&orderby=ORDERBY&order=ORDER(&meta_key=META_KEY)`

## Filters

- To exclude querying specific post types from `post`- and `archive`-routes, you
  can use following filters:

```
add_filter('arha_routes/archive_excluded_post_types', 'exclude_post_types');
add_filter('arha_routes/post_excluded_post_types', 'exclude_post_types');
function exclude_post_types($excluded_post_types) {
  $excluded_post_types = ['post'];
  return $excluded_post_types;
}
```

- To format `post`-route's post before it's served to client, use `arha_routes/format_post`-filter

```
add_filter('arha_routes/format_post', 'format_post');
function format_post($post) {
  return $post;
}
```

- To format `archive`-route's posts before they are served to client, use `arha_routes/format_archive_post`-filter

```
add_filter('arha_routes/format_archive_post', 'format_archive_post');
function format_archive_post($post) {
  return $post;
}
```

- `options`-route returns empty result by default. To add content to it, use `arha_routes/format_options`-filter

```
add_filter('arha_routes/format_options', 'format_options');
function format_options($options) {
  return $options;
}
```

## Comments

- `Archive`-route supports ordering posts by meta_field
  - `orderby`-param needs to be either `meta_value` or `meta_value_num`
  - `order`-param needs to be either `ASC` or `DESC`
  - `meta_key`-param needs to be meta-field's slug
  - example:
    - `orderby=meta_value_num&order=DESC&meta_key=release_date`
    - `orderby=meta_value&order=ASC&meta_key=person_name`
  - when ordering by native WP fields, just use `orderby` (field's slug) and
    `order` (`ASC` or `DESC`)
