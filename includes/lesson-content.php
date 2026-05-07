<?php

namespace LighterLMS;

defined("ABSPATH") || exit();

class Lesson_Content
{
    public static function get_builder(int $post_id): string
    {
        $active_plugins = (array) get_option("active_plugins", []);

        if (
            get_post_meta($post_id, "_elementor_edit_mode", true) ===
                "builder" &&
            in_array("elementor/elementor.php", $active_plugins, true)
        ) {
            return "elementor";
        }

        if (get_post_meta($post_id, "_fl_builder_enabled", true)) {
            return "beaver-builder";
        }

        if (get_post_meta($post_id, "_et_pb_use_builder", true) === "on") {
            return "divi";
        }

        $content = get_post_field("post_content", $post_id);
        if (has_blocks($content)) {
            return "gutenberg";
        }

        return "classic-editor";
    }

    public static function handle_elementor_content(
        int $post_id,
        \WP_Post $req_post,
    ): string {
        if (self::is_elementor_edit_mode()) {
            return "";
        }

        if (!class_exists("\Elementor\Plugin")) {
            return self::get_content($req_post);
        }

        if (!did_action("elementor/loaded")) {
            return self::get_content($req_post);
        }

        try {
            $elementor_data = get_post_meta($post_id, "_elementor_data", true);
            if (empty($elementor_data)) {
                return self::get_content($req_post);
            }

            global $post;
            $original_post = $post;
            $post = $saved_post = get_post($post_id);
            setup_postdata($saved_post);

            $elementor = \Elementor\Plugin::instance();

            if (!$elementor->frontend) {
                $elementor->init_common();
            }

            $content = $elementor->frontend->get_builder_content(
                $post_id,
                true,
            );

            wp_reset_postdata();
            $post = $original_post;

            return $content ?: self::get_content($req_post);
        } catch (\Exception $e) {
            error_log("Elementor content error: " . $e->getMessage());
            return self::get_content($req_post);
        }
    }

    private static function is_elementor_edit_mode(): bool
    {
        if (function_exists("elementor_is_edit_mode")) {
            return \elementor_is_edit_mode();
        }

        return (defined("ELEMENTOR_DEBUG") && ELEMENTOR_DEBUG) ||
            (isset($_GET["action"]) && $_GET["action"] === "elementor") ||
            (isset($_POST["action"]) &&
                strpost($_POST["action"], "elementor") !== false) ||
            (\Elementor\Plugin::$instance &&
                \Elementor\Plugin::$instance->editor->is_edit_mode());
    }

    public static function handle_gutenberg_content(
        int $post_id,
        \WP_Post $post,
    ): string {
        $content = get_post_field("post_content", $post_id);

        if (function_exists("parse_blocks")) {
            $blocks = parse_blocks($content);
            $rendered = "";

            foreach ($blocks as $block) {
                $rendered .= render_block($block);
            }

            return $rendered;
        }

        return apply_filters("the_content", $content);
    }

    /**
     * Gets the standard post content
     *
     * @param \WP_Post $post
     * @return string The post content already filtered.
     */
    public static function get_content(\WP_Post $post): string
    {
        $content = apply_filters("the_content", $post->post_content);
        return (string) $content;
    }

    public static function get_styles(int $post_id, string $builder): array
    {
        $styles = [];
        switch ($builder) {
            case "elementor":
                if (class_exists("\Elementor\Core\Files\CSS\Post")) {
                    $css_file = new \Elementor\Core\Files\CSS\Post($post_id);
                    $styles[] = $css_file->get_url();
                }
                break;
            case "beaver-builder":
                if (class_exists("FL_Builder")) {
                    $styles[] = \FLBuilder::get_css_url($post_id);
                }
                break;
            case "divi":
                $styles[] = get_template_directory_uri() . "/styles.css";
                break;
        }

        return array_filter($styles);
    }

    public static function post_content(\WP_Post|int $post, bool $display): ?array { 
        $post = get_post($post);
        $supported_builders = [
            "elementor" => "handle_elementor_content",
            "beaver-builder" => "handle_beaver_builder_content",
            "divi" => "handle_divi_content",
            "gutenberg" => "handle_gutenberg_content",
            "breakdance" => "handle_breakdance_content",
            "bricks" => "handle_bricks_content",
        ];
        $builder = self::get_builder($post->ID);
        $temp = $post;

        global $wp_query, $post;
        setup_postdata($temp);

        if ($builder && isset($supported_builders[$builder])) {
            $func = [self::class, $supported_builders[$builder]];
            $content = call_user_func($func, $temp->ID, $temp);
        } else {
            $content = self::get_content($temp);
        }

        wp_reset_postdata();

        $styles = self::get_styles($temp->ID, $builder);
        $styles = array_filter(array_map(function($style) {
            $css_path = str_replace(
                site_url('/'),
                ABSPATH,
                $style
            );

            if ( file_exists( $css_path ) ) {
                return file_get_contents($css_path);
            }
            return null;
        }, $styles));

        if ($display) {
            foreach ($styles as $style) {
                echo $style;
            }
                echo $content;
            return null;
        }

        return [$content, $styles];
    }
}
