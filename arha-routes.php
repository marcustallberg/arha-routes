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
require_once plugin_dir_path(__FILE__) . 'includes/builder.php';

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

    register_rest_route($namespace, "/page", array(
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [$this, "get_page"],
    ));

    register_rest_route($namespace, "/post", array(
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [$this, "get_post_type"],
    ));

    register_rest_route($namespace, "/options", array(
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [$this, "get_options"],
    ));

    register_rest_route($namespace, "/archive", array(
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [$this, "get_archive"],
    ));
  }

  public function get_post_type(WP_REST_Request $request) {
    $return_message;
    try {

      $required_params = ['slug', 'post_type'];
      ArhaHelpers::check_required_params($request, $required_params);

      $filter    = 'arha_routes/post_excluded_post_types';
      $post_type = $request->get_param('post_type');
      ArhaHelpers::check_excluded_post_types($filter, $post_type);

      $slug = $request->get_param('slug');

      $posts = get_posts([
        'name'        => $slug,
        'post_type'   => $post_type,
        'numberposts' => 1,
        'post_status' => 'publish',
      ]);

      if (sizeof($posts) == 0) {
        throw new Exception("System didn't find post with post_type '${post_type}' and slug '${slug}'");
      }

      $builder = new ArhaBuilder();
      $post    = $builder->build_post($posts[0]);
      // Let plugin user format post to their liking
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

      $builder        = new ArhaBuilder();
      $content        = $builder->build_post($page);
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
      $builder = new ArhaBuilder();
      $options = $builder->build_options();
      $content = apply_filters('arha_routes/format_options', $options);

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
      $required_params = ['post_type', 'posts_per_page', 'paged'];
      ArhaHelpers::check_required_params($request, $required_params);

      $filter    = 'arha_routes/archive_excluded_post_types';
      $post_type = $request->get_param('post_type');
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

      $orderby_param = $request->get_param('orderby');
      ArhaHelpers::check_orderby_param($orderby_param);
      $order_param = $request->get_param('order');
      if ($order_param !== 'ASC' && $order_param !== 'DESC') {
        throw new Exception('Order param can only be ASC or DESC');
      }

      // These both need to be defined, if order params are defined
      if ((!$orderby_param && $order_param) || ($orderby_param && !$order_param)) {
        throw new Exception('To order posts, define orderby and order param');
      }

      $args = [
        'post_type'      => $post_type,
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'status'         => 'publish',
      ];

      if ($orderby_param == 'meta_value' || $orderby_param == 'meta_value_num') {
        $meta_key_param = $request->get_param('meta_key');
        if (!$meta_key_param) {
          throw new Exception("To order posts by meta-field, define meta_key param with meta-field's name");
        }

        $args['meta_key'] = $meta_key_param;
      }

      $args['orderby'] = $orderby_param ?: 'date';
      $args['order']   = $order_param ?: 'DESC';

      $posts = get_posts($args);

      $builder     = new ArhaBuilder();
      $built_posts = array_map([$builder, 'build_post'], $posts);
      $content     = array_map(function ($post) {
        return apply_filters('arha_routes/format_archive_post', $post);
      }, $built_posts);

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

}

$runner = function () {
  $plugin = new ArhaRoutes();
  $plugin->attach_hooks();
};

$runner();
unset($runner);