<?php

namespace LighterLMS;

use wpdb;

class Lessons
{
	/** @var wpdb */
	protected $db;

	/** @var string */
	protected $table;

	/** @var int The lesson ID */
	protected $lesson;

	/** @var int The parent ID */
	protected $parent;

	/** @var int The topic ID */
	protected $topic;

	public function __construct($args = [], $db = null)
	{
		global $wpdb;

		$this->db = $db ?: $wpdb;
		$this->table = $this->db->prefix . 'lighter_lessons';

		$this->lesson = isset($args['lesson']) && (int)$args['lesson'] > 0 ? (int)$args['lesson'] : null;
		$this->parent = isset($args['parent']) && (int)$args['parent'] > 0 ? (int)$args['parent'] : null;
		$this->topic = isset($args['topic']) && (int)$args['topic'] > 0 ? (int)$args['topic'] : null;
	}

	public function install()
	{
		$charset_collate = $this->db->get_charset_collate();

		$sql = "CREATE TABLE {$this->table} (
		  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  lesson_id bigint(20) unsigned NOT NULL,
		  parent_id bigint(20) unsigned NULL,
		  topic_id bigint(20) unsigned NULL,
		  PRIMARY KEY (ID),
		  KEY idx_parent_id (parent_id),
		  KEY idx_lesson_parent (lesson_id, parent_id),
		  KEY idx_topic_parent (topic_id, parent_id),
		  UNIQUE KEY unique_lesson_parent_topic (lesson_id, parent_id, topic_id),
		  FOREIGN KEY (lesson_id) REFERENCES {$this->db->posts}(ID) ON DELETE CASCADE ON UPDATE CASCADE,
		  FOREIGN KEY (parent_id) REFERENCES {$this->db->posts}(ID) ON DELETE CASCADE ON UPDATE CASCADE,
		  FOREIGN KEY (topic_id) REFERENCES {$this->db->prefix}lighter_topics(ID) ON DELETE CASCADE ON UPDATE CASCADE
		) $charset_collate";

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	/**
	 * @param string $join
	 * @param \WP_Query $query
	 */
	public function db_join($join, $query)
	{
		if ($query->is_main_query()) {
			return $join;
		}

		$vars = $query->query_vars;

		if (isset($vars['lesson_parent']) && intval($vars['lesson_parent'])) {
			$join .= " INNER JOIN {$this->table} AS ll ON {$this->db->posts}.ID = ll.parent_id AND ll.lesson_id = " . intval($vars['lesson_parent']);
			return $join;
		}

		if (isset($vars['lighter_course']) && intval($vars['lighter_course'])) {
			$join .= " INNER JOIN {$this->table} AS ll ON {$this->db->posts}.ID = ll.lesson_id AND ll.parent_id = " . intval($vars['lighter_course']);
			return $join;
		}

		return $join;
	}

	public function save()
	{
		if ($this->db->query($this->db->prepare("SELECT * FROM {$this->table} WHERE parent_id = {$this->parent} AND lesson_id = {$this->lesson}"))) {
			return $this->db->update(
				$this->table,
				[
					'topic_id' => $this->topic
				],
				[
					'parent_id' => $this->parent,
					'lesson_id' => $this->lesson,
				],
				['%d'],
			);
		}
		$this->db->insert(
			$this->table,
			[
				'lesson_id' => $this->lesson,
				'parent_id' => $this->parent,
				'topic_id' => $this->topic
			],
			[
				'%d',
				'%d',
				'%d'
			],
		);
	}
}
