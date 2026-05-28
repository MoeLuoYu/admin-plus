<?php
/**
 * Gravatar Mirror - Gravatar镜像源替换类
 *
 * 将WordPress中的Gravatar头像URL替换为用户选择的镜像源，
 * 解决中国大陆地区Gravatar访问缓慢或无法加载的问题。
 *
 * @package Admin_Plus
 */

defined('ABSPATH') || exit;

class Gravatar_Mirror {

    private static $instance = null;

    private static $gravatar_hosts = array(
        'www.gravatar.com',
        'secure.gravatar.com',
        '0.gravatar.com',
        '1.gravatar.com',
        '2.gravatar.com',
        '3.gravatar.com',
        'cn.gravatar.com',
        'en.gravatar.com',
        'i0.wp.com',
        'i1.wp.com',
        'i2.wp.com',
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $settings = Admin_Settings::get_instance();
        $mirror = $settings->get_setting('gravatar_mirror');

        if (empty($mirror)) {
            return;
        }

        add_filter('get_avatar_url', array($this, 'replace_gravatar_url'), 10, 3);
        add_filter('get_avatar', array($this, 'replace_gravatar_html'), 10, 6);
    }

    private function get_mirror_host() {
        $settings = Admin_Settings::get_instance();
        $mirror = $settings->get_setting('gravatar_mirror');

        if ($mirror === 'custom') {
            $custom = $settings->get_setting('gravatar_mirror_custom');
            if (!empty($custom)) {
                return $custom;
            }
            return '';
        }

        return $mirror;
    }

    public function replace_gravatar_url($url, $id_or_email, $args) {
        $mirror_host = $this->get_mirror_host();
        if (empty($mirror_host)) {
            return $url;
        }

        foreach (self::$gravatar_hosts as $host) {
            if (strpos($url, '//' . $host . '/') !== false) {
                $url = str_replace('//' . $host . '/', '//' . $mirror_host . '/', $url);
                break;
            }
        }

        return $url;
    }

    public function replace_gravatar_html($avatar, $id_or_email, $size, $default, $alt, $args) {
        $mirror_host = $this->get_mirror_host();
        if (empty($mirror_host)) {
            return $avatar;
        }

        foreach (self::$gravatar_hosts as $host) {
            $avatar = str_replace('//' . $host . '/', '//' . $mirror_host . '/', $avatar);
        }

        return $avatar;
    }
}
