<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

?>

<div style="position:fixed;z-index:1000000;right:0px;background:#66db64;padding:20px;" id="tjd-top-bar">
	<div>
		<select id="tjd-top-bar-theme-select">
			<option value="default"><?php echo esc_html__( 'Theme Default' ); ?></option>
			<?php foreach ( $themes as $key => $value ) : ?>
				<option value="<?php echo sanitize_key( $key ); ?>"><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
