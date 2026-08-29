<?php
/**
 * One-off POT generator (dev utility).
 *
 * @package VMS_MSG
 */

$root = dirname( __DIR__ );
$rii  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) );
$entries = array();

foreach ( $rii as $file ) {
	if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
		continue;
	}
	$path = $file->getPathname();
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . '.' ) ) {
		continue;
	}
	if ( false !== strpos( $path, 'bin' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}

	$code = file_get_contents( $path );
	if ( ! preg_match_all( '/(?:__|_e|esc_html__|esc_attr__|_n|_nx)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', $code, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		continue;
	}

	$rel = str_replace( '\\', '/', substr( $path, strlen( $root ) + 1 ) );
	foreach ( $m as $hit ) {
		$msgid = stripcslashes( $hit[2][0] );
		$line  = substr_count( substr( $code, 0, $hit[0][1] ), "\n" ) + 1;
		if ( ! isset( $entries[ $msgid ] ) ) {
			$entries[ $msgid ] = array();
		}
		$entries[ $msgid ][] = $rel . ':' . $line;
	}
}

ksort( $entries );

$out   = array();
$out[] = '# Copyright (C) 2026 Shohel Hossain';
$out[] = '# This file is distributed under the GPL-2.0-or-later.';
$out[] = 'msgid ""';
$out[] = 'msgstr ""';
$out[] = '"Project-Id-Version: VMS Elements Multi Mailer 1.0.0\n"';
$out[] = '"Report-Msgid-Bugs-To: https://vmsuniverse.com\n"';
$out[] = '"POT-Creation-Date: ' . gmdate( 'Y-m-d H:iO' ) . '\n"';
$out[] = '"MIME-Version: 1.0\n"';
$out[] = '"Content-Type: text/plain; charset=UTF-8\n"';
$out[] = '"Content-Transfer-Encoding: 8bit\n"';
$out[] = '"X-Domain: vms-elements-multiple-smtp-email-gateway\n"';
$out[] = '';

foreach ( $entries as $msgid => $refs ) {
	foreach ( array_slice( $refs, 0, 8 ) as $ref ) {
		$out[] = '#: ' . $ref;
	}
	$escaped = addcslashes( $msgid, "\0..\37\"\\" );
	$out[]   = 'msgid "' . $escaped . '"';
	$out[]   = 'msgstr ""';
	$out[]   = '';
}

$dir = $root . '/languages';
if ( ! is_dir( $dir ) ) {
	mkdir( $dir, 0755, true );
}

file_put_contents( $dir . '/vms-elements-multiple-smtp-email-gateway.pot', implode( "\n", $out ) );
echo 'entries=' . count( $entries ) . PHP_EOL;
