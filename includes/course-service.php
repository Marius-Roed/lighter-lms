<?php

namespace LighterLMS;

use Error;
use Exception;

defined("ABSPATH") || exit();

class Course_Service
{
    public $post;

    public function __construct()
    {
        $this->post = new Course_Post();
    }

    public function create(array $data): int
    {
        $data["post_type"] = $this->post->post_type;
        $data["post_status"] = "draft";
        $post_id = wp_insert_post($data);
        return $post_id;
    }

    public function get(?int $id = null): null|\WP_Post|array
    {
        if ($id) {
            $course = get_post($id);
            if ($course->post_type !== lighter_lms()->course_post_type) {
                _doing_it_wrong(__FUNCTION__, "Cannot call get on a non LighterLMS course post type", "1.0.0");
                return null;
            }
            return $course;
        }
        return get_posts([
            'post_type' => lighter_lms()->course_post_type,
            'post_status' => 'any',
            'numberposts' => -1,
        ]);
    }

    public function save(int $id, array $data): int
    {
        $data["ID"] = $id;
        return wp_update_post($data);
    }

    public function delete(int $id): void
    {
        $post = get_post($id);
        if ($post->post_type !== lighter_lms()->course_post_type) {
            _doing_it_wrong(
                __FUNCTION__,
                "Called delete course, on post of type \"{$post->post_type}\"",
                "1.0.0",
            );
            return;
        }

        lighter()->lms->db->start_transaction();
        try {
            $topics = $this->get_topics($id);
            foreach ($topics as $topic) {
                lighter()->lms->db->topics->delete($topic["id"]);
            }

            $deleted = wp_delete_post($id, true);

            if (!$deleted) {
                throw new Error("Could not delete post {$id}");
            }

            lighter()->lms->db->commit();
        } catch (\Throwable $e) {
            lighter()->lms->db->rollback();
            // TODO: Show admin notice
        }
    }

    public function get_structure(
        int|\WP_Post $post,
        bool $with_trashed = false,
    ): array {
        $post = get_post($post);

        if ($post->post_type !== lighter_lms()->course_post_type) {
            _doing_it_wrong(
                __FUNCTION__,
                "Cannot get lighter topics on post of type \"{$post->post_type}\"",
                "1.0.0",
            );
            return [];
        }

        $topics = lighter()->lms->db->topics->find_by_course($post->ID);

        $structure = [];

        foreach ($topics as $topic) {
            $lessons = lighter()->lms->db->topic_lessons->find_by_topic(
                $topic->ID,
                $with_trashed,
            );

            $structure[] = [
                ...(array) $topic,
                "lessons" => array_map(
                    fn($l) => get_post($l->lesson_id),
                    $lessons,
                ),
            ];
        }

        return $structure;
    }

    /**
     * @return \LighjterLMS\DB\TopicRow[]
     */
    public function get_topics(int|\WP_Post $post): array
    {
        $post = get_post($post);

        if ($post->post_type !== lighter_lms()->course_post_type) {
            _doing_it_wrong(
                __FUNCTION__,
                "Cannot get lighter topics on post of type \"{$post->post_type}\"",
                "1.0.0",
            );
            return [];
        }

        return lighter()->lms->db->topics->find_by_course($post->ID);
    }

    /**
     * Get all lessons for a given course,ordered by topic sort_order,
     * then lesson sort_order
     *
     * @return \WP_Post[]
     */
    public function get_flat_lessons(
        int|\WP_Post $post,
        bool $unique = false,
    ): array {
        $post = get_post($post);

        if ($post->post_type !== lighter_lms()->course_post_type) {
            _doing_it_wrong(
                __FUNCTION__,
                "Cannot get lessons on post of type \"{$post->post_type}\"",
                "1.0.0",
            );
            return [];
        }

        $lesson_ids = lighter()->lms->db->topic_lessons->find_course_lesson_ids(
            $post->ID,
        );

        if (empty($lesson_ids)) {
            return [];
        }

        if ($unique) {
            $lesson_ids = array_unique($lesson_ids);
        }
        $lesson_ids = array_values($lesson_ids);

        $args = [
            "post_type" => lighter_lms()->lesson_post_type,
            "post__in" => array_map("intval", $lesson_ids),
            "orderby" => "post__in",
            "numberposts" => -1,
            "post_status" => "any",
        ];

        $posts = get_posts($args);

        return $posts;
    }

    public function get_settings(int|\WP_Post $post): array
    {
        return [];
    }

    public function save_settings(int $id, array $data): bool
    {
        throw new Exception("Not yet implemented");
    }
}
