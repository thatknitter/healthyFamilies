<?php
// Prevent file from being loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'et_extra_font_style_choices' ) ) :

	/**
 * Returns font style options
 * @return array
 */
	function et_extra_font_style_choices() {
		return apply_filters( 'et_extra_font_style_choices', array(
			'bold'      => __( 'Bold', 'extra' ),
			'italic'    => __( 'Italic', 'extra' ),
			'uppercase' => __( 'Uppercase', 'extra' ),
			'underline' => __( 'Underline', 'extra' ),
		) );
	}

endif;

if ( ! function_exists( 'et_extra_header_style_choices' ) ) :

	/**
 * Returns list of header styles used by Extra
 * @return array
 */
	function et_extra_header_style_choices() {
		return apply_filters( 'et_extra_header_style_choices', array(
			'left-right' => __( 'Left/Right', 'extra' ),
			'centered'   => __( 'Centered', 'extra' ),
		) );
	}

endif;

if ( ! function_exists( 'et_extra_dropdown_animation_choices' ) ) :

	/**
 * Returns list of dropdown animation
 * @return array
 */
	function et_extra_dropdown_animation_choices() {
		return apply_filters( 'et_extra_dropdown_animation_choices', array(
			'Default'       => __( 'Fade In', 'extra' ),
			'fadeInTop'     => __( 'Fade In From Top', 'extra' ),
			'fadeInRight'   => __( 'Fade In From Right', 'extra' ),
			'fadeInBottom'  => __( 'Fade In From Bottom', 'extra' ),
			'fadeInLeft'    => __( 'Fade In From Left', 'extra' ),
			'scaleInRight'  => __( 'Scale In From Right', 'extra' ),
			'scaleInLeft'   => __( 'Scale In From Left', 'extra' ),
			'scaleInCenter' => __( 'Scale In From Center', 'extra' ),
			'flipInY'       => __( 'Flip In Horizontally', 'extra' ),
			'flipInX'       => __( 'Flip In Vertically', 'extra' ),
			'slideInX'      => __( 'Slide In Vertically', 'extra' ),
			'slideInY'      => __( 'Slide In Horizontally', 'extra' ),
		) );
	}

endif;

if ( ! function_exists( 'et_extra_footer_column_choices' ) ) :

	/**
 * Returns list of footer column choices
 * @return array
 */
	function et_extra_footer_column_choices() {
		return apply_filters( 'et_extra_footer_column_choices', array(
			'4'             => __( '4 Columns', 'extra' ),
			'3'             => __( '3 Columns', 'extra' ),
			'2'             => __( '2 Columns', 'extra' ),
			'1'             => __( '1 Column', 'extra' ),
			'1_4__3_4'      => __( '1/4 + 3/4 Columns', 'extra' ),
			'3_4__1_4'      => __( '3/4 + 1/4 Columns', 'extra' ),
			'1_3__2_3'      => __( '1/3 + 2/3 Columns', 'extra' ),
			'2_3__1_3'      => __( '2/3 + 1/3 Columns', 'extra' ),
			'1_4__1_4__1_2' => __( '1/4 + 1/4 + 1/2 Columns', 'extra' ),
			'1_2__1_4__1_4' => __( '1/2 + 1/4 + 1/4 Columns', 'extra' ),
			'1_4__1_2__1_4' => __( '1/4 + 1/2 + 1/4 Columns', 'extra' ),
		) );
	}

endif;

if ( ! function_exists( 'et_extra_yes_no_choices' ) ) :

	/**
 * Returns yes no choices
 * @return array
 */
	function et_extra_yes_no_choices() {
		return apply_filters( 'et_extra_yes_no_choices', array(
			'yes' => __( 'Yes', 'extra' ),
			'no'  => __( 'No', 'extra' ),
		) );
	}

endif;

if ( ! function_exists( 'et_extra_left_right_choices' ) ) :

	/**
 * Returns left or right choices
 * @return array
 */
	function et_extra_left_right_choices() {
		return apply_filters( 'et_extra_left_right_choices', array(
			'right' => __( 'Right', 'extra' ),
			'left'  => __( 'Left', 'extra' ),
		) );
	}

endif;

if ( ! function_exists( 'et_extra_image_animation_choices' ) ) :

	/**
 * Returns image animation choices
 * @return array
 */
	function et_extra_image_animation_choices() {
		return apply_filters( 'et_extra_image_animation_choices', array(
			'left'    => __( 'Left to Right', 'extra' ),
			'right'   => __( 'Right to Left', 'extra' ),
			'top'     => __( 'Top to Bottom', 'extra' ),
			'bottom'  => __( 'Bottom to Top', 'extra' ),
			'fade_in' => __( 'Fade In', 'extra' ),
			'off'     => __( 'No Animation', 'extra' ),
		) );
	}

endif;

if ( ! function_exists( 'et_extra_divider_style_choices' ) ) :

	/**
 * Returns divider style choices
 * @return array
 */
	function et_extra_divider_style_choices() {
		return apply_filters( 'et_extra_divider_style_choices', array(
			'solid'  => __( 'Solid', 'extra' ),
			'dotted' => __( 'Dotted', 'extra' ),
			'dashed' => __( 'Dashed', 'extra' ),
			'double' => __( 'Double', 'extra' ),
			'groove' => __( 'Groove', 'extra' ),
			'ridge'  => __( 'Ridge', 'extra' ),
			'inset'  => __( 'Inset', 'extra' ),
			'outset' => __( 'Outset', 'extra' ),
		) );
	}

endif;

if ( ! function_exists( 'et_extra_divider_position_choices' ) ) :

	/**
 * Returns divider position choices
 * @return array
 */
	function et_extra_divider_position_choices() {
		return apply_filters( 'et_extra_divider_position_choices', array(
			'top'    => __( 'Top', 'extra' ),
			'center' => __( 'Vertically Centered', 'extra' ),
			'bottom' => __( 'Bottom', 'extra' ),
		) );
	}

endif;
