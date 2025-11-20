<?php

namespace LighterLMS;

/**
 * This class is simply used to be able to import types
 *
 * @phpstan-type LessonShape array{
 *	id: int,
 *	key: string,
 *	slug: string,
 *	title: string,
 *	sortOrder: int,
 *	parentTopicKey: string
 * }
 *
 * @phpstan-type OwnedShape array{
 *	course_id: int,
 *	lessons: array<int, int>,
 *	access_type: string,
 *	start_date: string,
 *	drip_interval: ?string,
 *	expires: ?string
 * }
 *
 * @phpstan-type ProgressShape array{	
 *	max_unlocked_lesson: int,
 *	unlocked_lessons: array<int, int>,
 *	completed_lessons: array<int, int>,
 *	completion_date: string
 * }
 *
 * @phpstan-type TopicsShape array{
 *	key: string,
 *	title: string,
 *	sortOrder: int,
 *	courseId: int
 * }
 *
 * @phpstan-type CourseData array{
 *	lessons: LessonShape,
 *	owned: OwnedShape,
 *	progress: ProgressShape,
 *	topics: ?TopicsShape
 * }
 *
 * @phpstan-type PostNorm array{
 *	id: int,
 *	title: string,
 *	date: string,
 *	date_gmt: string,
 *	modified: string,
 *	modified_gmt: string,
 *	link: string,
 *	slug: string,
 *	status: string,
 *	tags: array|\WP_Error,
 *	type: string
 * }
 *
 */
class Types {}
