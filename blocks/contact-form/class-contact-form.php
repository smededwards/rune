<?php
/**
 * Accessible contact form markup.
 *
 * @package Rune
 */

namespace Rune\Blocks;

/**
 * Renders fixed fields with editor-owned labels and safe request state.
 */
final class ContactForm {

	/**
	 * Render a single form on its own public page.
	 *
	 * @param array $attributes Saved editor content.
	 * @return string Escaped form markup.
	 */
	public function render( array $attributes ): string {
		if ( ! has_filter( 'rune_contact_state' ) || ! is_singular( 'page' ) || get_the_ID() !== get_queried_object_id() ) {
			return '';
		}
		foreach ( array(
			'nameLabel'          => __( 'Name', 'rune' ),
			'emailLabel'         => __( 'Email', 'rune' ),
			'subjectLabel'       => __( 'Subject', 'rune' ),
			'messageLabel'       => __( 'Message', 'rune' ),
			'subjectPlaceholder' => __( 'Select a Subject (Optional)', 'rune' ),
			'buttonLabel'        => __( 'Send Message', 'rune' ),
			'successMessage'     => __( 'Thank you. Your message has been sent.', 'rune' ),
		) as $key => $fallback ) {
			if ( '' === trim( $attributes[ $key ] ) ) {
				$attributes[ $key ] = $fallback;
			}
		}
		$state   = apply_filters(
			'rune_contact_state',
			array(
				'values' => array(),
				'errors' => array(),
			)
		);
		$errors  = $state['errors'];
		$notice  = '';
		$summary = '';
		if ( ! empty( $state['sent'] ) && ! $errors ) {
			$notice = sprintf( '<p class="contact-form__notice" role="status">%s</p>', esc_html( $attributes['successMessage'] ) );
		}
		if ( $errors ) {
			$links = '';
			foreach ( $errors as $field => $error ) {
				$links .= 'general' === $field
					? sprintf( '<li>%s</li>', esc_html( $error ) )
					: sprintf( '<li><a href="#contact-%s">%s: %s</a></li>', esc_attr( $field ), esc_html( $attributes[ $field . 'Label' ] ), esc_html( $error ) );
			}
			$summary = sprintf(
				'<div class="contact-form__notice contact-form__notice--error" role="alert" tabindex="-1" autofocus>
					<p>%s</p>
					<ul>%s</ul>
				</div>',
				esc_html__( 'Please check your message.', 'rune' ),
				$links
			);
		}
		$fields          = '';
		$subject_options = array(
			'employment'  => __( 'Employment Opportunity', 'rune' ),
			'project'     => __( 'Project Enquiry', 'rune' ),
			'consultancy' => __( 'WordPress Consultancy', 'rune' ),
			'general'     => __( 'General Enquiry', 'rune' ),
			'other'       => __( 'Other', 'rune' ),
		);
		foreach ( array(
			'name'    => 100,
			'email'   => 254,
			'subject' => 0,
			'message' => 5000,
		) as $field => $limit ) {
			$value = $state['values'][ $field ] ?? '';
			$error = $errors[ $field ] ?? '';
			$aria  = $error ? sprintf( ' aria-invalid="true" aria-describedby="contact-%s-error"', esc_attr( $field ) ) : '';
			if ( 'subject' === $field ) {
				$subject_markup = '';
				foreach ( $subject_options as $subject_value => $subject_label ) {
					$subject_markup .= sprintf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $subject_value ),
						selected( $value, $subject_value, false ),
						esc_html( $subject_label )
					);
				}
				$input = sprintf(
					'<select id="contact-subject" name="aeon_contact[subject]"%1$s>
						<option value="">%2$s</option>
						%3$s
					</select>',
					$aria,
					esc_html( $attributes['subjectPlaceholder'] ),
					$subject_markup
				);
			} elseif ( 'message' === $field ) {
				$input = sprintf( '<textarea id="contact-message" name="aeon_contact[message]" rows="7" maxlength="%d" placeholder="%s" required%s>%s</textarea>', $limit, esc_attr( $attributes['messagePlaceholder'] ), $aria, esc_textarea( $value ) );
			} else {
				$input = sprintf( '<input id="contact-%1$s" name="aeon_contact[%1$s]" type="%2$s" autocomplete="%1$s"%3$s maxlength="%4$d" placeholder="%5$s" value="%6$s" required%7$s>', esc_attr( $field ), 'email' === $field ? 'email' : 'text', 'email' === $field ? ' inputmode="email"' : '', $limit, esc_attr( $attributes[ $field . 'Placeholder' ] ), esc_attr( $value ), $aria );
			}
			$required = 'subject' === $field ? '' : esc_html__( '(required)', 'rune' );
			$fields  .= sprintf(
				'<div class="contact-form__field">
					<label for="contact-%1$s">
						%2$s <span class="contact-form__required">%3$s</span>
					</label>
					%4$s
					%5$s
				</div>',
				esc_attr( $field ),
				esc_html( $attributes[ $field . 'Label' ] ),
				$required,
				$input,
				$error ? sprintf( '<p class="contact-form__error" id="contact-%s-error">%s</p>', esc_attr( $field ), esc_html( $error ) ) : ''
			);
		}
		return sprintf(
			'<div %1$s id="contact-form">
				%2$s
				%3$s
				<form class="contact-form__form" action="%4$s#contact-form" method="post">
					<input type="hidden" name="_aeon_nonce" value="%5$s">
					<div hidden aria-hidden="true">
						<label for="contact-website">%6$s</label>
						<input id="contact-website" name="aeon_contact[website]" type="text" tabindex="-1" autocomplete="off">
					</div>
					%7$s
					<button class="contact-form__submit" type="submit">%8$s</button>
				</form>
			</div>',
			get_block_wrapper_attributes( array( 'class' => 'contact-form' ) ),
			$notice,
			$summary,
			esc_url( get_permalink() ),
			esc_attr( wp_create_nonce( 'aeon_contact_' . get_queried_object_id() ) ),
			esc_html__( 'Leave this field empty', 'rune' ),
			$fields,
			esc_html( $attributes['buttonLabel'] )
		);
	}
}
