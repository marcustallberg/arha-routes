# Arha Routes

Wordpress plugin that helps to serve content through REST routes and gives
customizability to developers through filters.

## Available Routes

- `/wp-json/arha/v1/post?post_type=POST_TYPE&slug=SLUG`
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

- To format `page`-route's page before it's served to client, use `arha_routes/format_page`-filter

```
add_filter('arha_routes/format_page', 'format_page');
function format_page($page) {
  return $page;
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

## Polylang

Arha Routes supports Polylang-plugin, which allows users to create
content in multiple languages.

Activating Polylang changes how some endpoints work:

- all routes require additional `lang`-param
  - Example: `/wp-json/arha/v1/archive?post_type=products&posts_per_page=10&paged=1&orderby=date&order=ASC&lang=en`
- `page`-route doesn't support language prefix in path, use path after it
  - Example: Permalink in WP `/zh/info`, use like this `/wp-json/arha/v1/page?path=/info&lang=zh`
- `options`-route passes `lang`-param forward to `arha_routes/format_options`-filter
  - Developer can then Polylang's language model to this language with `ArhaHelpers::set_polylang_curlang($lang)` if needed.
```
add_filter('arha_routes/format_options', 'format_options', 10, 2);
function format_options($options, $lang) {
  return $options;
}
```
