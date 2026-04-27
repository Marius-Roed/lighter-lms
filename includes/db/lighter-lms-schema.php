<?php

namespace LighterLMS\DB;

use WP_Error;

final class Lighter_LMS_Schema
{
    const VERSION = "2";
    const VERSION_OPTION = "lighter_lms_schema_version";

    private const STATUS_OPTION = "lighter_lms_schema_status";
    private const NOTICE_OPTION = "lighter_lms_schema_notice";
    private const LOCK_TRANSIENT = "lighter_lms_schema_lock";

    protected \wpdb $db;
    protected string $topics_table;
    protected string $topic_lessons_table;

    protected string $legacy_topics_table;
    protected string $legacy_lessons_table;

    public function __construct(\wpdb $wpdb)
    {
        $this->db = $wpdb;
        $this->topics_table = $this->db->prefix . lighter_lms()->topics_table;
        $this->topic_lessons_table =
            $this->db->prefix . lighter_lms()->topic_lessons_table;

        $this->legacy_topics_table = $this->db->prefix . "lighter_topics";
        $this->legacy_lessons_table = $this->db->prefix . "lighter_lessons";
    }

    public function maybe_upgrade(?string $version = null): bool|\WP_Error
    {
        $installed = (string) get_option(self::VERSION_OPTION, "0");

        if (version_compare($installed, $version ?? self::VERSION, ">=")) {
            return true;
        }

        if (get_transient(self::LOCK_TRANSIENT)) {
            return new \WP_Error(
                "lighter_lms_schema_locked",
                __("A database migration is already running.", "lighterlms"),
            );
        }

        set_transient(self::LOCK_TRANSIENT, 1, MINUTE_IN_SECONDS * 5);

        $this->set_status([
            "status" => "running",
            "target" => self::VERSION,
            "from" => $installed,
            "failed_step" => "",
            "message" => "",
            "code" => "",
            "last_attempt" => gmdate("Y-m-d H:i:s"),
            "completed_at" => "",
        ]);

        $result = $this->run_upgrade();

        delete_transient(self::LOCK_TRANSIENT);

        if (is_wp_error($result)) {
            $this->set_status([
                "status" => "failed",
                "target" => self::VERSION,
                "from" => $installed,
                "failed_step" => $result->get_error_data("step") ?? "",
                "message" => $result->get_error_message(),
                "code" => $result->get_error_code(),
                "last_attempt" => gmdate("Y-m-d H:i:s"),
                "completed_at" => "",
            ]);

            return $result;
        }

        update_option(self::VERSION_OPTION, self::VERSION, false);

        $this->set_status([
            "status" => "completed",
            "target" => self::VERSION,
            "from" => $installed,
            "failed_step" => "",
            "message" => "",
            "code" => "",
            "last_attempt" => gmdate("Y-m-d H:i:s"),
            "completed_at" => gmdate("Y-m-d H:i:s"),
        ]);

        $this->set_notice([
            "type" => "success",
            "message" => __(
                "Lighter LMS database updated successfully!",
                "lighterlms",
            ),
        ]);

        return true;
    }

    public function retry_update(): bool|\WP_Error
    {
        delete_transient(self::LOCK_TRANSIENT);
        return $this->maybe_upgrade();
    }

    protected function run_upgrade(): bool|\WP_Error
    {
        $result = $this->_install_current_schema();

        if (is_wp_error($result)) {
            return $result;
        }

        if (
            $this->_table_exists($this->legacy_topics_table) ||
            $this->_table_exists($this->legacy_lessons_table)
        ) {
            $result = $this->_migrate_to_v2();
            if (is_wp_error($result)) {
                return $result;
            }
        }

        return true;
    }

    private function _install_current_schema(): bool|\WP_Error
    {
        require_once ABSPATH . "wp-admin/includes/upgrade.php";

        $charset = $this->db->get_charset_collate();

        $sql_topics = "CREATE TABLE {$this->topics_table} (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			topic_key char(16) NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL,
            sort_order bigint(20) unsigned NOT NULL DEFAULT 10,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ID),
			UNIQUE KEY uq_topic_key (topic_key),
			KEY idx_course_order (course_id, sort_order),
            KEY idx_course (course_id)
		) {$charset};";

        $sql_topic_lessons = "CREATE TABLE {$this->topic_lessons_table} (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lesson_id bigint(20) unsigned NOT NULL,
			topic_id bigint(20) unsigned NOT NULL,
			sort_order bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY (ID),
			UNIQUE KEY uq_topic_lesson (topic_id, lesson_id),
			KEY idx_topic_sort (topic_id, sort_order),
			KEY idx_lesson (lesson_id)
		) {$charset};";

        $this->db->last_error = "";
        dbDelta($sql_topics);

        if (!$this->_table_exists($this->topics_table)) {
            return new \WP_Error(
                "lighter_lms_topics_table_create_failed",
                __("Could not create the LMS topics table", "lighterlms"),
                [
                    "step" => "create_topics_table",
                    "sql_error" => $this->db->last_error,
                ],
            );
        }

        $this->db->last_error = "";
        dbDelta($sql_topic_lessons);

        if (!$this->_table_exists($this->topic_lessons_table)) {
            return new \WP_Error(
                "lighter_lms_topic_lessons_table_create_failed",
                __(
                    "Could not create the LMS topic lessons table.",
                    "lighterlms",
                ),
                [
                    "step" => "create_topic_lessons_table",
                    "sql_error" => $this->db->last_error,
                ],
            );
        }

        return $this->maybe_add_topic_foreign_key();
    }

    protected function maybe_add_topic_foreign_key(): bool|\WP_Error
    {
        $constraint = "fk_lighter_lms_topic_lessons_topic";

        if (
            $this->_constraint_exists($this->topic_lessons_table, $constraint)
        ) {
            return true;
        }

        $this->db->last_error = "";

        $result = $this->db->query(
            "ALTER TABLE {$this->topic_lessons_table}
				ADD CONSTRAINT {$constraint}
				FOREIGN KEY (topic_id)
				REFERENCES {$this->topics_table}(ID)
				ON DELETE CASCADE
				ON UPDATE CASCADE",
        );

        if ($result === false) {
            return new \WP_Error(
                "lighter_lms_topic_fk_create_failed",
                __(
                    "Could not create the topic relationship constraint.",
                    "lighterlms",
                ),
                [
                    "step" => "create_topic_foreign_key",
                    "sql_error" => $this->db->last_error,
                ],
            );
        }

        return true;
    }

    private function _migrate_to_v2(): bool|\WP_Error
    {
        $result = $this->_validate_legacy_data();

        if (is_wp_error($result)) {
            return $result;
        }

        if ($this->_table_exists($this->legacy_topics_table)) {
            $this->db->last_error = "";

            $result = $this->db->query(
                "
				INSERT IGNORE INTO {$this->topics_table}
					(ID, topic_key, course_id, title, sort_order, created_at, updated_at)
				SELECT
					ID,
					topic_key,
					post_id,
					title,
                    COALESCE(sort_order, 1) * 10,
					created_at,
					updated_at
				FROM {$this->legacy_topics_table}
				",
            );

            if ($result === false) {
                return new \WP_Error(
                    "lighter_lms_topics_copy_failed",
                    __("Could not migrate legacy topics", "ligterlms"),
                    [
                        "step" => "copy_topics",
                        "sql_error" => $this->db->last_error,
                    ],
                );
            }
        }

        if ($this->_table_exists($this->legacy_lessons_table)) {
            $has_topic_sort =
                $this->_table_exists($this->legacy_topics_table) &&
                $this->_column_exists($this->legacy_topics_table, "sort_order");
            if ($has_topic_sort) {
                $sort_expr = "COALESCE(t.sort_order, 1) * 10";
                $join = "LEFT JOIN {$this->legacy_topics_table} t ON t.ID = l.topic_id";
            } else {
                $sort_expr = "10";
                $join = "";
            }

            $this->db->last_error = "";

            $result = $this->db->query(
                "
				INSERT IGNORE INTO {$this->topic_lessons_table}
					(ID, lesson_id, topic_id, sort_order)
				SELECT
					l.ID,
					l.lesson_id,
					l.topic_id,
					{$sort_expr}
                FROM {$this->legacy_lessons_table} l
                {$join}
			",
            );

            if ($result === false) {
                return new \WP_Error(
                    "lighter_lms_topic_lessons_copy_failed",
                    __(
                        "Could not migrate legacy topic lesson relationships",
                        "lighterlms",
                    ),
                    [
                        "step" => "copy_topic_lessons",
                        "sql_error" => $this->db->last_error,
                    ],
                );
            }
        }

        return true;
    }

    private function _validate_legacy_data(): bool|\WP_Error
    {
        if (!$this->_table_exists($this->legacy_lessons_table)) {
            return true;
        }

        $null_topics = (int) $this->db->get_var(
            "SELECT COUNT(*)
			FROM {$this->legacy_lessons_table}
			WHERE topic_id IS NULL",
        );

        if ($null_topics > 0) {
            return new \WP_Error(
                "lighter_lms_legacy_null_topic_ids",
                __(
                    "Mirgation stopped. Legacy rows do not have topics assigned",
                    "lighterlms",
                ),
                [
                    "step" => "validate_null_topic_ids",
                    "sql_error" => $null_topics,
                ],
            );
        }

        if ($this->_table_exists($this->legacy_topics_table)) {
            $orphaned_topic_refs = (int) $this->db->get_var(
                "SELECT COUNT(*)
				FROM {$this->legacy_lessons_table} l
				LEFT JOIN {$this->legacy_topics_table} t
					ON t.ID = l.topic_id
				WHERE t.ID IS NULL",
            );

            if ($orphaned_topic_refs > 0) {
                return new \WP_Error(
                    "lighter_lms_legacy_orphaned_topic_refs",
                    __(
                        "Migration stopped. Legacy rows contain deleted topic ids",
                        "lighterlms",
                    ),
                    [
                        "step" => "validate_orphaned_topic_refs",
                        "sql_error" => $orphaned_topic_refs,
                    ],
                );
            }
        }

        $duplicate_pairs = (int) $this->db->get_var(
            "SELECT COUNT(*)
			FROM (
				SELECT lesson_id, topic_id
				FROM {$this->legacy_lessons_table}
				GROUP BY lesson_id, topic_id
				HAVING COUNT(*) > 1
			) AS dupes",
        );

        if ($duplicate_pairs > 0) {
            return new \WP_Error(
                "lighter_lms_legacy_duplicate_topic_lessons",
                __(
                    "Migration stopped. Duplicate lesson on topic.",
                    "lighterlms",
                ),
                [
                    "step" => "validate_duplicate_topic_lessons",
                    "sql_error" => $duplicate_pairs,
                ],
            );
        }

        return true;
    }

    private function _table_exists(string $table): bool
    {
        $found = $this->db->get_var(
            $this->db->prepare("SHOW TABLES LIKE %s", $table),
        );

        return $found === $table;
    }

    private function _column_exists(string $table, string $column): bool
    {
        $found = $this->db->get_var(
            $this->db->prepare("SHOW COLUMNS FROM `{$table}` LIKE %s", $column),
        );

        return $found === $column;
    }

    private function _constraint_exists(string $table, string $constaint): bool
    {
        $found = $this->db->get_var(
            $this->db->prepare(
                'SELECT CONSTRAINT_NAME
				FROM information_schema.TABLE_CONSTRAINTS
				WHERE CONSTRAINT_SCHEMA = DATABASE()
					AND TABLE_NAME = %s
					AND CONSTRAINT_NAME = %s
				LIMIT 1',
                $table,
                $constaint,
            ),
        );

        return $found === $constaint;
    }

    public function get_status(): array
    {
        $status = get_option(self::STATUS_OPTION, []);
        return is_array($status) ? $status : [];
    }

    protected function set_status(array|object $status): void
    {
        update_option(self::STATUS_OPTION, $status, false);
    }

    public function get_notice(): array
    {
        $notice = get_option(self::NOTICE_OPTION, []);
        return is_array($notice) ? $notice : [];
    }

    protected function set_notice(array $notice)
    {
        update_option(self::NOTICE_OPTION, $notice, false);
    }

    public function delete_notice(): void
    {
        delete_option(self::NOTICE_OPTION);
    }
}
