<?php
/**
 * Accessible contact form markup.
 *
 * @package Rune
 */

namespace Rune\Blocks;

/** Renders fixed fields with editor-owned labels and safe request state. */
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
			'nameLabel'      => __( 'Name', 'rune' ),
			'emailLabel'     => __( 'Email', 'rune' ),
			'messageLabel'   => __( 'Message', 'rune' ),
			'buttonLabel'    => __( 'Send message', 'rune' ),
			'successMessage' => __( 'Thank you. Your message has been sent.', 'rune' ),
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
			$summary = sprintf( '<div class="contact-form__notice contact-form__notice--error" role="alert" tabindex="-1" autofocus><p>%s</p><ul>%s</ul></div>', esc_html__( 'Please check your message.', 'rune' ), $links );
		}
		$fields = '';
		foreach ( array(
			'name'    => 100,
			'email'   => 254,
			'message' => 5000,
		) as $field => $limit ) {
			$value   = $state['values'][ $field ] ?? '';
			$error   = $errors[ $field ] ?? '';
			$aria    = $error ? sprintf( ' aria-invalid="true" aria-describedby="contact-%s-error"', esc_attr( $field ) ) : '';
			$input   = 'message' === $field
				? sprintf( '<textarea id="contact-message" name="aeon_contact[message]" rows="7" maxlength="%d" placeholder="%s" required%s>%s</textarea>', $limit, esc_attr( $attributes['messagePlaceholder'] ), $aria, esc_textarea( $value ) )
				: sprintf( '<input id="contact-%1$s" name="aeon_contact[%1$s]" type="%2$s" autocomplete="%1$s" maxlength="%3$d" placeholder="%4$s" value="%5$s" required%6$s>', esc_attr( $field ), 'email' === $field ? 'email' : 'text', $limit, esc_attr( $attributes[ $field . 'Placeholder' ] ), esc_attr( $value ), $aria );
			$fields .= sprintf(
				'<div class="contact-form__field"><label for="contact-%1$s">%2$s <span class="contact-form__required">%3$s</span></label>%4$s%5$s</div>',
				esc_attr( $field ),
				esc_html( $attributes[ $field . 'Label' ] ),
				esc_html__( '(required)', 'rune' ),
				$input,
				$error ? sprintf( '<p class="contact-form__error" id="contact-%s-error">%s</p>', esc_attr( $field ), esc_html( $error ) ) : ''
			);
		}
		return sprintf(
			'<div %1$s id="contact-form">%2$s%3$s<form class="contact-form__form" action="%4$s#contact-form" method="post" novalidate><input type="hidden" name="_aeon_nonce" value="%5$s"><div hidden aria-hidden="true"><label for="contact-website">%6$s</label><input id="contact-website" name="aeon_contact[website]" type="text" tabindex="-1" autocomplete="off"></div>%7$s<button class="contact-form__submit" type="submit">%8$s</button></form></div>',
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
