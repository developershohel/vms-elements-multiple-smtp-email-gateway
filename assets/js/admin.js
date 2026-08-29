(function ($) {
	'use strict';

	if (typeof vmsMsgAdmin === 'undefined') {
		return;
	}

	var $modal = $('#vms-msg-resend-modal');
	var $accountSelect = $('#vms-msg-resend-account');
	var $logId = $('#vms-msg-resend-log-id');
	var $feedback = $('#vms-msg-resend-feedback');
	var $confirm = $('#vms-msg-resend-confirm');

	function fillAccountDropdown() {
		var accounts = vmsMsgAdmin.accounts || [];
		$accountSelect.empty();
		$accountSelect.append(
			$('<option/>', {
				value: '',
				text: vmsMsgAdmin.i18n.selectAccount
			})
		);
		accounts.forEach(function (account) {
			$accountSelect.append(
				$('<option/>', {
					value: String(account.id),
					text: account.name
				})
			);
		});
	}

	function openModal(logId) {
		fillAccountDropdown();
		$logId.val(String(logId));
		$feedback.prop('hidden', true).removeClass('is-error is-success').text('');
		$confirm.prop('disabled', false).text(vmsMsgAdmin.i18n.confirmResend);
		$modal.prop('hidden', false);
		$accountSelect.trigger('focus');
	}

	function closeModal() {
		$modal.prop('hidden', true);
	}

	$(document).on('click', '.vms-msg-resend-btn', function (e) {
		e.preventDefault();
		var id = $(this).data('log-id');
		if (!id) {
			return;
		}
		if (!(vmsMsgAdmin.accounts || []).length) {
			window.alert(vmsMsgAdmin.i18n.selectAccount);
			return;
		}
		openModal(id);
	});

	$(document).on('click', '[data-vms-msg-close]', function (e) {
		e.preventDefault();
		closeModal();
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && !$modal.prop('hidden')) {
			closeModal();
		}
	});

	$confirm.on('click', function () {
		var logId = parseInt($logId.val(), 10) || 0;
		var accountId = parseInt($accountSelect.val(), 10) || 0;

		if (!logId || !accountId) {
			$feedback
				.prop('hidden', false)
				.removeClass('is-success')
				.addClass('is-error')
				.text(vmsMsgAdmin.i18n.selectAccount);
			return;
		}

		$confirm.prop('disabled', true).text(vmsMsgAdmin.i18n.resending);
		$feedback.prop('hidden', true);

		$.post(vmsMsgAdmin.ajaxUrl, {
			action: 'vms_msg_resend_email',
			nonce: vmsMsgAdmin.nonce,
			log_id: logId,
			account_id: accountId
		})
			.done(function (response) {
				var message =
					response && response.data && response.data.message
						? response.data.message
						: vmsMsgAdmin.i18n.success;
				$feedback
					.prop('hidden', false)
					.removeClass('is-error')
					.addClass('is-success')
					.text(message);
				window.setTimeout(function () {
					window.location.reload();
				}, 700);
			})
			.fail(function (xhr) {
				var message = vmsMsgAdmin.i18n.error;
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				}
				$feedback
					.prop('hidden', false)
					.removeClass('is-success')
					.addClass('is-error')
					.text(message);
				$confirm.prop('disabled', false).text(vmsMsgAdmin.i18n.confirmResend);
			});
	});

	$(document).on('click', '.vms-msg-test-btn', function (e) {
		e.preventDefault();
		var accountId = $(this).data('account-id');
		var defaultTo = $(this).data('default-to') || '';
		var to = window.prompt(vmsMsgAdmin.i18n.testPrompt, defaultTo);

		if (to === null) {
			return;
		}
		to = String(to).trim();
		if (!to) {
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true);

		$.post(vmsMsgAdmin.ajaxUrl, {
			action: 'vms_msg_test_email',
			nonce: vmsMsgAdmin.nonce,
			account_id: accountId,
			to_email: to
		})
			.done(function (response) {
				var message =
					response && response.data && response.data.message
						? response.data.message
						: vmsMsgAdmin.i18n.success;
				window.alert(message);
			})
			.fail(function (xhr) {
				var message = vmsMsgAdmin.i18n.error;
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				}
				window.alert(message);
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});

	function resolveHost(provider) {
		var host = provider.host || '';
		if (provider.id === 'amazon_ses') {
			var region = $('#ses_region').val() || 'us-east-1';
			host = 'email-smtp.' + region + '.amazonaws.com';
		}
		if (provider.id === 'mailgun') {
			var mg = $('#mailgun_region').val() || 'us';
			host =
				vmsMsgAdmin.providers.mailgunHosts && vmsMsgAdmin.providers.mailgunHosts[mg]
					? vmsMsgAdmin.providers.mailgunHosts[mg]
					: 'smtp.mailgun.org';
		}
		if (provider.id === 'zoho') {
			var zh = $('#zoho_region').val() || 'us';
			host =
				vmsMsgAdmin.providers.zohoHosts && vmsMsgAdmin.providers.zohoHosts[zh]
					? vmsMsgAdmin.providers.zohoHosts[zh]
					: 'smtp.zoho.com';
		}
		if (provider.id === 'mailtrap') {
			var mode = $('#mailtrap_mode').val() || 'live';
			host =
				vmsMsgAdmin.providers.mailtrapHosts && vmsMsgAdmin.providers.mailtrapHosts[mode]
					? vmsMsgAdmin.providers.mailtrapHosts[mode]
					: 'live.smtp.mailtrap.io';
		}
		return host;
	}

	function updateConnectionMap(provider, host) {
		var mapped = provider.id !== 'other';
		$('#vms-msg-connection-map').prop('hidden', !mapped);
		$('.vms-msg-row-smtp-manual').prop('hidden', mapped);

		$('#vms-msg-map-host').text(host || '—');
		$('#vms-msg-map-port').text(provider.port || '—');
		$('#vms-msg-map-enc').text((provider.encryption || '—').toUpperCase());

		var userDisplay = '—';
		if (provider.usernameLock) {
			userDisplay = provider.usernameLock + ' (fixed)';
		} else if (provider.mirrorPass) {
			userDisplay = '(same as password / API token)';
		} else if (provider.usernameFromSender) {
			userDisplay = $('#sender_email').val() || '(from sender email)';
		} else {
			userDisplay = $('#smtp_username').val() || '(enter below)';
		}
		$('#vms-msg-map-user').text(userDisplay);
	}

	function applyProvider(providerId, options) {
		options = options || {};
		var cfg = (vmsMsgAdmin.providers && vmsMsgAdmin.providers.providers) || {};
		var provider = cfg[providerId] || cfg.other;
		if (!provider) {
			return;
		}

		$('#vms-msg-provider').val(provider.id);
		$('.vms-msg-provider-card')
			.removeClass('is-selected')
			.attr('aria-pressed', 'false');
		$('.vms-msg-provider-card[data-provider="' + provider.id + '"]')
			.addClass('is-selected')
			.attr('aria-pressed', 'true');

		$('#vms-msg-provider-help').text(provider.help || '');
		$('#vms-msg-username-hint').text(provider.usernameHint || '');

		var isEdit = parseInt($('input[name="account_id"]').val(), 10) > 0;
		if (!isEdit || !$('#smtp_password').val()) {
			var keepNote = isEdit
				? ' Leave blank to keep the existing encrypted password.'
				: '';
			$('#vms-msg-password-hint').text((provider.passwordHint || '') + keepNote);
		}

		var isOther = provider.id === 'other';
		var $host = $('#smtp_host');
		var $port = $('#smtp_port');
		var $enc = $('#smtp_encryption');
		var $user = $('#smtp_username');
		var host = resolveHost(provider);

		$('.vms-msg-row-ses-region').prop('hidden', provider.id !== 'amazon_ses');
		$('.vms-msg-row-mailgun-region').prop('hidden', provider.id !== 'mailgun');
		$('.vms-msg-row-zoho-region').prop('hidden', provider.id !== 'zoho');
		$('.vms-msg-row-mailtrap-mode').prop('hidden', provider.id !== 'mailtrap');

		if (!isOther) {
			if (host) {
				$host.val(host);
			}
			$port.val(provider.port);
			$enc.val(provider.encryption);
		}

		$host.prop('readonly', !isOther);
		$port.prop('readonly', !isOther);
		$enc.prop('disabled', !isOther);

		var hideUsername = !!(provider.usernameLock || provider.mirrorPass);
		$('.vms-msg-row-smtp-username').prop('hidden', hideUsername && !isOther);

		if (provider.usernameLock) {
			$user.val(provider.usernameLock).prop('readonly', true);
		} else if (provider.mirrorPass) {
			$user.prop('readonly', true);
			if ($('#smtp_password').val()) {
				$user.val($('#smtp_password').val());
			}
		} else if (provider.usernameFromSender) {
			var sender = $('#sender_email').val();
			if (sender && (!options.keepUsername || !$user.val())) {
				$user.val(sender);
			}
			$user.prop('readonly', false);
		} else {
			$user.prop('readonly', false);
		}

		updateConnectionMap(provider, host || $host.val());
	}

	$(document).on('click', '.vms-msg-provider-card', function () {
		applyProvider($(this).data('provider'), { forceDefaults: true });
	});

	$('#ses_region, #mailgun_region, #zoho_region, #mailtrap_mode').on('change', function () {
		applyProvider($('#vms-msg-provider').val() || 'other', { forceDefaults: true, keepUsername: true });
	});

	$('#sender_email').on('input change', function () {
		var providerId = $('#vms-msg-provider').val();
		var cfg = (vmsMsgAdmin.providers && vmsMsgAdmin.providers.providers) || {};
		var provider = cfg[providerId];
		if (provider && provider.usernameFromSender && !provider.usernameLock && !provider.mirrorPass) {
			$('#smtp_username').val($(this).val());
			updateConnectionMap(provider, resolveHost(provider));
		}
	});

	$('#smtp_password').on('input', function () {
		var providerId = $('#vms-msg-provider').val();
		var cfg = (vmsMsgAdmin.providers && vmsMsgAdmin.providers.providers) || {};
		var provider = cfg[providerId];
		if (provider && provider.mirrorPass) {
			$('#smtp_username').val($(this).val());
			updateConnectionMap(provider, resolveHost(provider));
		}
	});

	$('#smtp_username').on('input', function () {
		var providerId = $('#vms-msg-provider').val();
		var cfg = (vmsMsgAdmin.providers && vmsMsgAdmin.providers.providers) || {};
		var provider = cfg[providerId];
		if (provider) {
			updateConnectionMap(provider, resolveHost(provider));
		}
	});

	if ($('#vms-msg-account-form').length) {
		applyProvider($('#vms-msg-provider').val() || 'other', { keepUsername: true });
		$('#vms-msg-account-form').on('submit', function () {
			$('#smtp_host, #smtp_port, #smtp_encryption, #smtp_username').prop('disabled', false).prop('readonly', false);
			$('.vms-msg-row-smtp-manual, .vms-msg-row-smtp-username').prop('hidden', false);
		});
	}

	$(document).on('click', '.vms-msg-health-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var accountId = $btn.data('account-id');
		var $cell = $('.vms-msg-health-cell[data-account-id="' + accountId + '"]');

		$btn.prop('disabled', true).text(vmsMsgAdmin.i18n.checking);

		$.post(vmsMsgAdmin.ajaxUrl, {
			action: 'vms_msg_health_check',
			nonce: vmsMsgAdmin.nonce,
			account_id: accountId
		})
			.done(function (response) {
				var data = (response && response.data) || {};
				var status = data.status || 'ok';
				var message = data.message || vmsMsgAdmin.i18n.success;
				var checked = data.checked_at || '';
				var badgeClass = status === 'ok' ? 'vms-msg-badge--ok' : 'vms-msg-badge--err';
				var label = status === 'ok' ? vmsMsgAdmin.i18n.healthOk : vmsMsgAdmin.i18n.healthFail;
				$cell.html(
					'<span class="vms-msg-badge ' +
						badgeClass +
						'" title="' +
						$('<div/>').text(message).html() +
						'">' +
						label +
						'</span>' +
						(checked ? '<br /><small>' + $('<div/>').text(checked).html() + '</small>' : '')
				);
				window.alert(message);
			})
			.fail(function (xhr) {
				var message = vmsMsgAdmin.i18n.error;
				var data = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : {};
				if (data.message) {
					message = data.message;
				}
				$cell.html(
					'<span class="vms-msg-badge vms-msg-badge--err" title="' +
						$('<div/>').text(message).html() +
						'">' +
						vmsMsgAdmin.i18n.healthFail +
						'</span>'
				);
				window.alert(message);
			})
			.always(function () {
				$btn.prop('disabled', false).text('Check health');
			});
	});

	$(document).on('change', '#vms-msg-check-all', function () {
		var checked = $(this).prop('checked');
		$('#vms-msg-logs-bulk input[name="log_ids[]"]').prop('checked', checked);
	});
})(jQuery);
