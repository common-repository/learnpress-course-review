<?php

namespace LP_Addon_Course_Review;

use Academy\API\Course;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LP_Addon_Course_Review;
use LP_Addon_Course_Review_Preload;
use Throwable;

/**
 * Class Template
 *
 * @package RealPress\Helpers
 * @since 1.0.1
 * @version 1.0.1
 */
class LP_Addon_Review_List_Rating_Reviews_Template {
	public $template;

	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'learn-press/course-review/list-rating-reviews', array( $this, 'list_rating_reviews' ) );
		add_filter( 'learn-press/single-course/offline/section-left', [
			$this,
			'single_course_offline_list_rating_reviews'
		] );
		add_filter(
			'lean-press/single-course/offline/info-bar',
			[ $this, 'single_course_offline_info_bar' ],
			10,
			2
		);
	}

	/**
	 * Load templates for single property
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function list_rating_reviews( array $data ) {
		$elms = apply_filters(
			'learn-press/course-review/list-rating-reviews/elements',
			[
				'course-sum-rating.php',
				'form-submit-review.php',
				'item-review-wait-approve',
				'list-reviews.php',
			],
			$data
		);

		foreach ( $elms as $elm ) {
			LP_Addon_Course_Review_Preload::$addon->get_template( $elm, $data );
		}
	}

	/**
	 * Add section show list rating on single course offline
	 *
	 * @param array $sections
	 *
	 * @return array
	 * @since 4.1.4
	 * @version 1.0.0
	 */
	public function single_course_offline_list_rating_reviews( array $sections = [] ): array {
		$sections_new = [];

		try {
			ob_start();
			LP_Addon_Course_Review_Preload::$addon->add_course_tab_reviews_callback();
			$html_rating_reviews_main = ob_get_clean();

			$sectionR = [
				'wrapper'     => '<div class="lp-rating-reviews-wrapper">',
				'header'      => '<h3 class="item-title">' . esc_html__( 'Reviews', 'learnpress-course-review' ) . '</h3>',
				'content'     => $html_rating_reviews_main,
				'wrapper_end' => '</div>',
			];

			foreach ( $sections as $k => $section ) {
				$sections_new[ $k ] = $section;
				if ( $k === 'instructor' ) {
					$sections_new['reviews'] = Template::combine_components( $sectionR );
				}
			}
		} catch ( Throwable $e ) {
			$sections_new = $sections;
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $sections_new;
	}

	/**
	 * Add section show list rating on single course offline
	 *
	 * @param array $sections
	 * @param CourseModel $course
	 *
	 * @return array
	 * @since 4.1.4
	 * @version 1.0.0
	 */
	public function single_course_offline_info_bar( array $sections, CourseModel $course ): array {
		$sections_new = [];

		try {
			foreach ( $sections as $k => $section ) {
				$sections_new[ $k ] = $section;
				if ( $k === 'author' ) {
					$sections_new['reviews'] = $this->html_tiny_rating_info( $course );
				}
			}
		} catch ( Throwable $e ) {
			$sections_new = $sections;
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $sections_new;
	}

	/**
	 * Get html tiny rating info
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 */
	public function html_tiny_rating_info( CourseModel $course ): string {
		$html = '';

		try {
			$rating_info = LP_Addon_Course_Review_Preload::$addon->get_rating_of_course( $course->get_id() );
			/*if ( $rating_info['total'] === 0 ) {
				return $html;
			}*/

			wp_enqueue_style( 'course-review' );

			$html_star = sprintf(
				'<em class="fas lp-review-svg-star">%s</em>',
				LP_Addon_Course_Review::get_svg_star()
			);
			$html = sprintf(
				'<div class="item-meta">
					<div class="star-info">
					<span class="ico-star">%s</span><span class="info-rating">%d/%d</span> %s
					</div>
				</div>',
				$html_star,
				$rating_info['rated'],
				$rating_info['total'],
				_n( 'Rating', 'Ratings', $rating_info['total'], 'learnpress-course-review' )
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}
}

LP_Addon_Review_List_Rating_Reviews_Template::instance();
