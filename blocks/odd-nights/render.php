<?php
/**
 * Odd nights dates block: evenings of the odd nights for both possible
 * starts of the current or next Ramadan.
 *
 * @package Lailatulqadar
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$lq_r = lq_ramadan();
if ( ! $lq_r ) {
	return;
}

$lq_ms    = 'secondary' === lq_current_role();
$lq_year  = gmdate( 'Y', strtotime( $lq_r['start'] ) );
$lq_eid_a = lq_add_days( $lq_r['start'], $lq_r['days'] );
$lq_eid_b = lq_add_days( $lq_r['alt'], $lq_r['days'] );

$lq_head = $lq_ms
	? array( 'Malam (Hijrah)', 'Jika 1 Ramadan jatuh pada ' . lq_fmt_date( $lq_r['start'], 'short' ) . ' ' . $lq_year, 'Jika 1 Ramadan jatuh pada ' . lq_fmt_date( $lq_r['alt'], 'short' ) . ' ' . $lq_year )
	: array( 'Night (Hijri)', 'If 1 Ramadan falls on ' . lq_fmt_date( $lq_r['start'], 'short' ) . ' ' . $lq_year, 'If 1 Ramadan falls on ' . lq_fmt_date( $lq_r['alt'], 'short' ) . ' ' . $lq_year );

$lq_cell = function ( $ymd ) use ( $lq_ms ) {
	return $lq_ms ? lq_fmt_date( $ymd, 'short' ) . ' (selepas Maghrib)' : 'Evening of ' . lq_fmt_date( $ymd, 'short' );
};

$lq_rows = array();
foreach ( array( 21, 23, 25, 27, 29 ) as $lq_n ) {
	$lq_rows[] = array( ucfirst( lq_hijri( $lq_n, $lq_r['hijri'], 'ramadan', true ) ), $lq_cell( lq_night_evening( $lq_r['start'], $lq_n ) ), $lq_cell( lq_night_evening( $lq_r['alt'], $lq_n ) ) );
}
if ( ! empty( $attributes['eid'] ) ) {
	$lq_rows[] = array(
		lq_hijri( 1, $lq_r['hijri'], 'shawwal' ) . ( $lq_ms ? ' (Aidilfitri, jangkaan)' : ' (Eid al-Fitr, expected)' ),
		lq_fmt_date( $lq_eid_a, 'short' ),
		lq_fmt_date( $lq_eid_b, 'short' ),
	);
}

$lq_caption = $lq_ms
	? sprintf( 'Tarikh mengikut takwim Umm al-Qura bagi Ramadan %1$d H (%2$s) dan dikemas kini secara automatik setiap tahun. Rukyah tempatan menentukan tarikh muktamad; setiap malam bermula ketika matahari terbenam.', $lq_r['hijri'], $lq_year )
	: sprintf( 'Dates follow the Umm al-Qura calendar for Ramadan %1$d AH (%2$s) and update automatically each year. Local sighting decides the final calendar; each night begins at sunset.', $lq_r['hijri'], $lq_year );
?>
<figure <?php echo get_block_wrapper_attributes( array( 'class' => 'wp-block-table lq-table' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<table>
		<thead><tr><?php foreach ( $lq_head as $lq_h ) : ?><th><?php echo esc_html( $lq_h ); ?></th><?php endforeach; ?></tr></thead>
		<tbody>
			<?php foreach ( $lq_rows as $lq_row ) : ?>
				<tr><?php foreach ( $lq_row as $lq_c ) : ?><td><?php echo esc_html( $lq_c ); ?></td><?php endforeach; ?></tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<figcaption class="wp-element-caption"><?php echo esc_html( $lq_caption ); ?></figcaption>
</figure>
