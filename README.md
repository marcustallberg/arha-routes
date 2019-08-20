# Arha Routes

Wordpress plugin that serves content through REST routes.

# How to use

- Activate the plugin
- Need to fetch post or custom post with slug?
  - Navigate to `/wp-json/arha/v1/post?post_type=POST_TYPE_SLUG&slug=POSTS_SLUG`
- Need to fetch page with path?
  - Navigate to `/wp-json/arha/v1/page?path=POSTS_PATH`
- Need to fetch ACF options pages?
  - Navigate to `/wp-json/arha/v1/options`
- Need to fetch many posts for archive?
  - Navigate to
    `/wp-json/arha/v1/archive?post_type=POST_TYPE_SLUG&posts_per_page=POSTS_PER_PAGE&paged=PAGED`
  - By default query uses `orderby => date` and `order => DESC`
  - Optionally, you can add your own `orderby`- and `order`-param if you need
    to override default one.
  - Ordering by meta-field works similarly how it's done with `get_posts()`
    - `orderby` needs to be either `meta_value` or `meta_value_num`
    - `order` needs to be defined to either `ASC` or `DESC`
    - `meta_key` needs to be meta-field's slug
    - example:
      - `orderby=meta_value_num&order=DESC&meta_key=release_date`
      - `orderby=meta_value&order=ASC&meta_key=person_name`

# Filters

- To exclude post types from `post`- and `archive`-routes, use `arha_routes/rest_excluded_post_types`-filter

```
add_filter('arha_routes/rest_excluded_post_types', 'exclude_post_types');
function exclude_post_types($excluded_post_types) {
  $excluded_post_types = ['post', 'product_order'];
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

- To format taxonomy-object, use below filter.
  In other use cases, you can use `arha_routes/format_post`- or `arha_routes/format_post`-filter.

```
add_filter('arha_routes/format_taxonomy', 'format_taxonomy');
function format_taxonomy($taxonomy) {
  return $taxonomy;
}
```

- To format post terms, use below filter.
  You can use `arha_routes/format_post`- or `arha_routes/format_post`-filter.

```
add_filter('arha_routes/format_term', 'format_term');
function format_term($term) {
  return $term;
}
```

- To format `options`-route's results before they are served to client, use `arha_routes/format_options`-filter

```
add_filter('arha_routes/format_options', 'format_options');
function format_options($options) {
  return $options;
}
```

# TODO

- Add post comments to posts (if not disabled)
- Support multiple orderbys in archive endpoint
- separate `arha_routes/rest_excluded_post_types`-filter for post and archive
  endpoint?
