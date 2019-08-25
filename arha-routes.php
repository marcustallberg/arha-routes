<?php
/*
Plugin Name: Arha Routes
Description: Adds REST endpoints for Headless setup
Version: 1.0.0
Author: Atte Liimatainen
 */

if (!defined('WPINC')) {
  die;
}

require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';

class ArhaRoutes {
  protected $_version;
  protected $_slug;
  protected $_namespace;
  protected $_api_version;

  public function __construct() {
    $this->_version     = '1.0.0';
    $this->_slug        = 'arha-routes';
    $this->_namespace   = 'arha';
    $this->_api_version = 'v1';
  }

  public function attach_hooks() {
    add_action('rest_api_init', [$this, 'register_api_endpoints']);
  }

  public function register_api_endpoints() {
    $namespace = $this->_namespace . '/' . $this->_api_version;

    register_rest_route($namespace, "/page", [
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [$this, "get_page"],
    ]);

    register_rest_route($namespace, "/post", [
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [$this, "get_post"],
    ]);

    register_rest_route($namespace, "/options", [
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [$this, "get_options"],
    ]);

    register_rest_route($namespace, "/archive", [
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [$this, "get_archive"],
    ]);
  }

  public function get_post(WP_REST_Request $request) {
    $return_message;
    try {

      $required_params = ['slug', 'post_type'];
      if (class_exists('Polylang')) {
        $required_params[] = 'lang';
      }
      ArhaHelpers::check_required_params($request, $required_params);

      $filter    = 'arha_routes/post_excluded_post_types';
      $post_type = $request->get_param('post_type');
      if (!post_type_exists($post_type)) {
        throw new Exception("post_type-param wasn't found on System");
      }
      ArhaHelpers::check_excluded_post_types($filter, $post_type);

      $slug = $request->get_param('slug');

      $args = [
        'name'        => $slug,
        'post_type'   => $post_type,
        'numberposts' => 1,
        'post_status' => 'publish',
      ];

      $lang;
      if (class_exists('Polylang')) {
        $lang         = $request->get_param('lang');
        $args['lang'] = $lang;
      }

      $posts = get_posts($args);
      if (sizeof($posts) == 0) {
        throw new Exception("System didn't find post with post_type '${post_type}' and slug '${slug}'");
      }
      $post = $posts[0];

      if (class_exists('Polylang')) {
        ArhaHelpers::check_post_language($post, $lang);
      }

      $content = apply_filters('arha_routes/format_post', $post);

      $return_message = [
        'status'  => 'success',
        'content' => $content,
      ];
    } catch (Exception $e) {
      $return_message = [
        'status'  => 'error',
        'content' => $e->getMessage(),
      ];
    }
    return $return_message;
  }

  public function get_page(WP_REST_Request $request) {
    $return_message;
    try {
      $required_params = ['path'];
      ArhaHelpers::check_required_params($request, $required_params);

      $unescaped_path = $request->get_param('path');
      $path           = stripslashes($unescaped_path);

      $page = get_page_by_path($path, 'OBJECT', 'page');
      if (!$page) {
        throw new Exception("Didn't find page with path '${path}'");
      }

      $content = apply_filters('arha_routes/format_page', $page);

      $return_message = [
        'status'  => 'success',
        'content' => $content,
      ];
    } catch (Exception $e) {
      $return_message = [
        'status'  => 'error',
        'content' => $e->getMessage(),
      ];
    }
    return $return_message;
  }

  public function get_options(WP_REST_Request $request) {
    $return_message;
    try {
      $content;
      if (class_exists('Polylang')) {
        $required_params = ['lang'];
        ArhaHelpers::check_required_params($request, $required_params);
        $lang    = $request->get_param('lang');
        $content = apply_filters('arha_routes/format_options', [], $lang);
      } else {
        $content = apply_filters('arha_routes/format_options', []);
      }

      $return_message = [
        'status'  => 'success',
        'content' => $content,
      ];
    } catch (Exception $e) {
      $return_message = [
        'status'  => 'error',
        'content' => $e->getMessage(),
      ];
    }
    return $return_message;
  }

  public function get_archive(WP_REST_Request $request) {
    $return_message;
    try {
      $required_params = ['post_type', 'posts_per_page', 'paged', 'orderby', 'order'];
      if (class_exists('Polylang')) {
        $required_params[] = 'lang';
      }
      ArhaHelpers::check_required_params($request, $required_params);

      $filter    = 'arha_routes/archive_excluded_post_types';
      $post_type = $request->get_param('post_type');
      if (!post_type_exists($post_type)) {
        throw new Exception("Post_type wasn't found on System");
      }
      ArhaHelpers::check_excluded_post_types($filter, $post_type);

      $posts_per_page = $request->get_param('posts_per_page');
      $posts_per_page = (int)$posts_per_page;
      if ($posts_per_page < 1 || $posts_per_page > 100) {
        throw new Exception('posts_per_page needs to a number be between 1 and 100');
      }

      $paged = $request->get_param('paged');
      $paged = (int)$paged;
      if ($paged < 1) {
        throw new Exception('paged needs to be a number above 1');
      }

      $orderby = $request->get_param('orderby');
      ArhaHelpers::check_orderby_param($orderby);

      $order = $request->get_param('order');
      if ($order !== 'ASC' && $order !== 'DESC') {
        throw new Exception('Order param can only be ASC or DESC');
      }

      $args = [
        'post_type'      => $post_type,
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'orderby'        => $orderby,
        'order'          => $order,
        'status'         => 'publish',
      ];

      if ($orderby == 'meta_value' || $orderby == 'meta_value_num') {
        $meta_key = $request->get_param('meta_key');
        if (!$meta_key) {
          throw new Exception("To order posts by meta-field, define meta_key param with meta-field's name");
        }

        $args['meta_key'] = $meta_key;
      }

      if (class_exists('Polylang')) {
        $lang         = $request->get_param('lang');
        $args['lang'] = $lang;
      }

      $query       = new WP_Query($args);
      $found_posts = (int)$query->found_posts;
      $query_posts = $query->posts;

      $posts = array_map(function ($post) {
        return apply_filters('arha_routes/format_archive_post', $post);
      }, $query_posts);

      $return_message = [
        'status'  => 'success',
        'content' => [
          'found_posts' => $found_posts,
          'posts'       => $posts,
        ],
      ];

    } catch (Exception $e) {
      $return_message = [
        'status'  => 'error',
        'content' => $e->getMessage(),
      ];
    }

    return $return_message;
  }

}

$runner = function () {
  $plugin = new ArhaRoutes();
  $plugin->attach_hooks();
};

$runner();
unset($runner);