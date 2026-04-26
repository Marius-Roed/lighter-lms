<?php

namespace LighterLMS\API;

defined("ABSPATH") || exit();

use LighterLMS\Lesson_Content;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Lesson extends Base_Controller
{
    public function register_routes(): void
    {
        register_rest_route($this->namespace, "/lesson/(?P<id>\d+)", [
            [
                "methods" => "GET",
                "callback" => [$this, "get_lesson"],
                "permission_callback" => [$this, "can_read"],
                "args" => [
                    "id" => [
                        "validate_callback" => fn($v) => is_numeric($v),
                        "sanitize_callback" => "absint",
                    ],
                ],
            ],
            [
                "methods" => "DELETE",
                "callback" => [$this, "delete_lesson"],
                "permission_callback" => [$this, "can_delete"],
            ],
        ]);

        register_rest_route($this->namespace, "/lesson/updateOrder", [
            [
                "methods" => "PUT",
                "callback" => [$this, "update_order"],
                "permission_callback" => [$this, "read_lessons"],
                "args" => [
                    "to" => [
                        "required" => true,
                        "type" => "object",
                    ],
                    "from" => [
                        "required" => false,
                        "type" => "object",
                    ],
                ],
            ],
        ]);
    }

    public function can_read(WP_REST_Request $request): bool
    {
        return current_user_can("read_post", $request->get_param("id"));
    }

    public function read_lessons(WP_REST_Request $request): bool
    {
        $to = $request->get_param("to");
        if (!$to) {
            return false;
        }

        $can_read = array_map(
            fn($p) => current_user_can("read_post", $p["id"]),
            $to["reorder"],
        );
        return !empty($can_read) && !in_array(false, $can_read, strict: true);
    }

    public function can_delete(WP_REST_Request $request): bool
    {
        return current_user_can("delete_post", $request->get_param("id"));
    }

    public function get_lesson(
        WP_REST_Request $request,
    ): WP_REST_Response|WP_Error {
        $content_only = $request->get_param("content_only");

        $post = $this->get_post_or_error(
            $request->get_param("id"),
            lighter_lms()->lesson_post_type,
        );

        if (is_wp_error($post)) {
            return $post;
        }

        $data = $this->format_post($post);
        if ($request->get_param("topics")) {
            $data["topics"] = lighter()->lms->course->get_topics($post->ID);
        }

        if ($content_only) {
            $supported_builders = [
                "elementor" => "handle_elementor_content",
                "beaver-builder" => "handle_beaver_builder_content",
                "divi" => "handle_divi_content",
                "gutenberg" => "handle_gutenberg_content",
                "breakdance" => "handle_breakdance_content",
                "bricks" => "handle_bricks_content",
            ];
            $builder = Lesson_Content::get_builder($post->ID);
            $lesson = $post;

            global $wp_query, $post;
            setup_postdata($lesson);

            if ($builder && isset($supported_builders[$builder])) {
                $func = [Lesson_Content::class, $supported_builders[$builder]];
                $content = call_user_func($func, $lesson->ID, $lesson);
            } else {
                $content = Lesson_Content::get_content($lesson);
            }

            wp_reset_postdata();

            return $this->success([
                "id" => $lesson->ID,
                "title" => get_the_title($lesson),
                "slug" => $lesson->post_name,
                "content" => $content,
                "builder" => $builder,
                "styles" => Lesson_Content::get_styles($lesson->ID, $builder),
            ]);
        }

        return $this->success($data);
    }

    public function update_order(
        WP_REST_Request $request,
    ): WP_Error|WP_REST_Response {
        $to_data = $request->get_param("to");
        $from_data = $request->get_param("from");

        $reordered = lighter()->lms->topic->reorder_lessons(
            $to_data,
            $from_data,
        );

        if (is_wp_error($reordered)) {
            return $reordered;
        }

        $course = lighter()->lms->course->get_topics($reordered->course_id);

        return $this->success(
            array_map(
                fn($t) => lighter()->lms->topic->normalise_for_rest($t, true),
                $course,
            ),
        );
    }

    public function delete_lesson(
        WP_REST_Request $request,
    ): WP_REST_Response|WP_Error {
        $post = $this->get_post_or_error(
            $request->get_param("id"),
            lighter_lms()->lesson_post_type,
        );

        if (is_wp_error($post)) {
            return $post;
        }

        // TODO: DELETE Topics and maybe lessons.

        return $this->success([
            "deleted" => true,
            "id" => $post->ID,
        ]);
    }

    public function pre_save_lesson(
        \WP_Post $post,
        \WP_REST_Request $request,
        bool $creating,
    ): void {
        $title = $request->get_param("title");
        if ($title) {
            $title = sanitize_text_field($title);
            wp_update_post([
                "ID" => $post->ID,
                "post_title" => $title,
            ]);
        }
    }

    public function prepare_lesson_item(
        WP_REST_Response $response,
        \WP_Post $post,
        WP_REST_Request $request,
    ): WP_REST_Response {
        $data = $response->get_data();

        if (!isset($data["title"])) {
            $data["title"]["rendered"] = get_the_title($post);
            if ($request->get_param("context") === "edit") {
                $data["title"]["raw"] = $post->post_title;
            }
        }

        $response->set_data($data);

        return $response;
    }
}
