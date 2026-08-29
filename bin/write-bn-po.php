<?php
/**
 * Write starter bn_BD.po with UTF-8 Bengali strings.
 *
 * @package VMS_MSG
 */

$path = dirname( __DIR__ ) . '/languages/vms-elements-multiple-smtp-email-gateway-bn_BD.po';

$pairs = array(
	'VMS Elements Multi Mailer' => 'VMS Elements Multi Mailer',
	'VMS Multi Mailer' => 'VMS Multi Mailer',
	'Dashboard' => 'ড্যাশবোর্ড',
	'SMTP Accounts' => 'SMTP অ্যাকাউন্ট',
	'Email Logs' => 'ইমেইল লগ',
	'Settings' => 'সেটিংস',
	'Email analytics' => 'ইমেইল বিশ্লেষণ',
	'Sent today' => 'আজ পাঠানো',
	'Failed today' => 'আজ ব্যর্থ',
	'Sent / failed (7 days)' => 'পাঠানো / ব্যর্থ (৭ দিন)',
	'Sent / failed (30 days)' => 'পাঠানো / ব্যর্থ (৩০ দিন)',
	'Queued emails' => 'কিউতে থাকা ইমেইল',
	'Today’s usage vs limits' => 'আজকের ব্যবহার বনাম সীমা',
	'No SMTP accounts configured yet.' => 'এখনো কোনো SMTP অ্যাকাউন্ট সেট করা হয়নি।',
	'Account' => 'অ্যাকাউন্ট',
	'Daily limit' => 'দৈনিক সীমা',
	'Unlimited' => 'সীমাহীন',
	'Top failing accounts (7 days)' => 'সবচেয়ে বেশি ব্যর্থ অ্যাকাউন্ট (৭ দিন)',
	'No failures in the last 7 days.' => 'গত ৭ দিনে কোনো ব্যর্থতা নেই।',
	'Failures' => 'ব্যর্থতা',
	'All statuses' => 'সব স্ট্যাটাস',
	'Sent' => 'পাঠানো',
	'Failed' => 'ব্যর্থ',
	'All accounts' => 'সব অ্যাকাউন্ট',
	'Filter' => 'ফিল্টার',
	'Export CSV' => 'CSV এক্সপোর্ট',
	'Bulk actions' => 'বাল্ক অ্যাকশন',
	'Delete selected' => 'নির্বাচিত মুছুন',
	'Delete all failed (matching filters)' => 'সব ব্যর্থ লগ মুছুন (মিল থাকা ফিল্টার)',
	'Apply' => 'প্রয়োগ',
	'No email logs found.' => 'কোনো ইমেইল লগ পাওয়া যায়নি।',
	'Save settings' => 'সেটিংস সংরক্ষণ',
	'Failure failover' => 'ব্যর্থতা ফেলওভার',
	'Background mail queue' => 'ব্যাকগ্রাউন্ড মেইল কিউ',
	'Alert email' => 'অ্যালার্ট ইমেইল',
	'Health failure alerts' => 'হেলথ ব্যর্থতার অ্যালার্ট',
	'Failure spike alerts' => 'ব্যর্থতা স্পাইক অ্যালার্ট',
	'Mail queue' => 'মেইল কিউ',
	'Process queue now' => 'এখন কিউ প্রসেস করুন',
	'Smoke test' => 'স্মোক টেস্ট',
	'Import / export accounts' => 'অ্যাকাউন্ট ইমপোর্ট / এক্সপোর্ট',
	'Export JSON without passwords. After import, edit each account and set the SMTP password again.' => 'পাসওয়ার্ড ছাড়া JSON এক্সপোর্ট। ইমপোর্টের পর প্রতিটি অ্যাকাউন্টে SMTP পাসওয়ার্ড আবার সেট করুন।',
	'Fallback priority' => 'ফেলব্যাক অগ্রাধিকার',
	'Delete this SMTP account?' => 'এই SMTP অ্যাকাউন্ট মুছবেন?',
	'Delete this log entry?' => 'এই লগ এন্ট্রি মুছবেন?',
	'Select an SMTP provider' => 'একটি SMTP প্রোভাইডার নির্বাচন করুন',
	'Resend email' => 'ইমেইল আবার পাঠান',
	'Sending…' => 'পাঠানো হচ্ছে…',
	'Success' => 'সফল',
	'Error' => 'ত্রুটি',
	'Close' => 'বন্ধ',
	'Resend now' => 'এখন আবার পাঠান',
	'Send test email to:' => 'টেস্ট ইমেইল পাঠান:',
	'Checking…' => 'চেক করা হচ্ছে…',
	'Healthy' => 'সুস্থ',
	'You do not have permission to access this page.' => 'এই পেজে প্রবেশের অনুমতি নেই।',
);

$out   = array();
$out[] = '# Copyright (C) 2026 Shohel Hossain';
$out[] = '# This file is distributed under the GPL-2.0-or-later.';
$out[] = 'msgid ""';
$out[] = 'msgstr ""';
$out[] = '"Project-Id-Version: VMS Elements Multi Mailer 1.0.0\n"';
$out[] = '"Report-Msgid-Bugs-To: https://vmsuniverse.com\n"';
$out[] = '"POT-Creation-Date: 2026-08-21 00:00+0000\n"';
$out[] = '"PO-Revision-Date: 2026-08-21 00:00+0000\n"';
$out[] = '"Last-Translator: Shohel Hossain\n"';
$out[] = '"Language-Team: Bengali\n"';
$out[] = '"Language: bn_BD\n"';
$out[] = '"MIME-Version: 1.0\n"';
$out[] = '"Content-Type: text/plain; charset=UTF-8\n"';
$out[] = '"Content-Transfer-Encoding: 8bit\n"';
$out[] = '"Plural-Forms: nplurals=2; plural=(n != 1);\n"';
$out[] = '"X-Domain: vms-elements-multiple-smtp-email-gateway\n"';
$out[] = '';

foreach ( $pairs as $en => $bn ) {
	$out[] = 'msgid "' . addcslashes( $en, "\\\"\n\r\t" ) . '"';
	$out[] = 'msgstr "' . addcslashes( $bn, "\\\"\n\r\t" ) . '"';
	$out[] = '';
}

file_put_contents( $path, implode( "\n", $out ) );
echo 'Wrote ' . count( $pairs ) . ' strings (' . filesize( $path ) . " bytes)\n";
