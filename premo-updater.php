<?php
if (!defined('ABSPATH')) die();

if (!class_exists('PremoUpdater')) {
  class PremoUpdater {
    public static $package = null;
    public static $url = null;
    public static $version = null;
    public static $key = null;
    public static $cache_key = null;
    public static $cache_expiration = DAY_IN_SECONDS;

    function __construct($args) {
      self::$package = $args['package'];
      self::$url = $args['url'];
      self::$version = $args['version'];
      self::$key = $args['key'];
      self::$cache_key = self::$package . '_update_check_response';

      if (isset($args['cache_expiration'])) {
        self::$cache_expiration = $args['cache_expiration'];
      }

      add_filter('plugins_api', array($this, 'info'), 20, 3);
      add_filter('site_transient_update_plugins', array($this, 'update'));
      add_action('upgrader_process_complete', array($this, 'purge'), 10, 2);
      add_action('in_plugin_update_message-' . self::$package . '/' . self::$package . '.php', array($this, 'update_message'), 10, 2);
    }

    public static function error_message($plugin_file, $plugin_data) {
      echo '<tr id="' . self::$package . '-error-message" data-slug="' . self::$package . '" data-plugin="' . $plugin_file . '">';
      echo '<td colspan="4" class="colspanchange">';
      echo '<div class="notice inline notice-error notice-alt">';
      echo '<p>An error occured while attempting to check for ' . $plugin_data['Name'] . ' updates.</p>';
      echo '</div>';
      echo '</td>';
      echo '</tr>';
    }

    public static function info($res, $action, $args) {
      if ($action !== 'plugin_information' || $args->slug !== self::$package) {
        return $res;
      }

      $info = self::request();

      if (!$info) {
        return $res;
      }

      $res = new stdClass();

      $res->slug = $info->name;
      $res->version = $info->version;
      $res->last_updated = $info->release_date;

      if (property_exists($info, 'download_url')) {
        $res->download_link = $info->download_url;
        $res->trunk = $info->download_url;
      }

      if (property_exists($info, 'notes')) {
        $res->sections = array(
          'changelog' => $info->notes
        );
      }

      return $res;
    }

    public static function purge($upgrader, $options) {
      if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        delete_transient(self::$cache_key);
      }
    }

    public static function request() {
      if (!($info = get_transient(self::$cache_key))) {
        $url = add_query_arg(array(
          'p' => self::$package,
          'k' => self::$key
        ), self::$url);

        $response = wp_remote_get($url, array(
          'timeout' => 10,
          'headers' => array(
            'Accept' => 'application/json'
          )
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
          add_action('after_plugin_row_' . self::$package . '/' . self::$package . '.php', array($this, 'error_message'), 10, 2);
          return false;
        }

        $info = wp_remote_retrieve_body($response);

        if (!empty($info)) {
          $info = json_decode($info);
          set_transient(self::$cache_key, $info, self::$cache_expiration);
        }
      }

      return $info;
    }

    public static function update($transient) {
      if (empty($transient->checked)) {
        return $transient;
      }

      $info = self::request();

      if ($info && version_compare(self::$version, $info->version, '<')) {
        $res = new stdClass();

        $res->slug = self::$package;
        $res->plugin = self::$package . '/' . self::$package . '.php';
        $res->new_version = $info->version;

        if (property_exists($info, 'download_url')) {
          $res->package = $info->download_url;
        }

        if (property_exists($info, 'upgrade_notice')) {
          $res->upgrade_notice = $info->upgrade_notice;
        }

        $transient->response[$res->plugin] = $res;
      }

      return $transient;
    }

    public static function update_message($plugin_data, $response) {
      if (property_exists($response, 'upgrade_notice')) {
        echo '<br />' . $response->upgrade_notice;
      }
    }
  }
}
?>