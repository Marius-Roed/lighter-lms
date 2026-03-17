<?php

namespace LighterLMS\DB;


class Base_Controller {

	protected \wpdb $db;

	private int $depth = 0;

	public Topics_Repository $topics;
	public Topic_Lessons_Repository $topic_lessons;
	public Lighter_LMS_Schema $schema;

	public function __construct( \wpdb $wpdb ) {
		$this->schema        = new Lighter_LMS_Schema( $wpdb );
		$this->topics        = new Topics_Repository( $wpdb );
		$this->topic_lessons = new Topic_Lessons_Repository( $wpdb );
	}

	public function start_transaction(): void {
		if ( $this->depth === 0 ) {
			$this->db->query( 'START TRANSACTION' );
		} else {
			$this->db->query( "SAVEPOINT lighter_sp_{$this->depth}" );
		}

		++$this->depth;
	}

	public function commit(): void {
		--$this->depth;

		if ( $this->depth === 0 ) {
			$this->db->query( 'COMMIT' );
		} else {
			$this->db->query( "RELEASE SAVEPOINT lighter_sp_{$this->depth}" );
		}
	}

	public function rollback(): void {
		--$this->depth;

		if ( $this->depth === 0 ) {
			$this->db->query( 'ROLLBACK' );
		} else {
			$this->db->query( "ROLLBACK TO SAVEPOINT lighter_sp_{$this->depth}" );
		}
	}
}
