/* HTML del form login ------------------------------------ */
function modalMarkup() {
	return (
		'<div class="modal-container">' +
			'<div class="modal-wrapper">' +
				'<button class="modal-close" aria-label="Close"></button>' +
				'<h2>Login</h2>' +
				'<form id="brut-login-form">' +
					'<p><label>Username / Email<br>' +
					'<input type="text" name="username" required></label></p>' +
					'<p><label>Password<br>' +
					'<input type="password" name="password" required></label></p>' +
					'<input type="hidden" name="action" value="brut_login">' +
					'<input type="hidden" name="nonce" value="' + brutLogin.nonce + '">' +
					'<p><button type="submit">Login</button></p>' +
					'<p class="btn-black"><a href="/registrati" class="wp-block-button__link">Registrati</a></p>' +
					'<p class="modal-error" style="display:none;"></p>' +
				'</form>' +
			'</div>' +
		'</div>'
	);
}

jQuery(function ($) {

	/* Apre la modale ----------------------------------------- */
	$(document).on('click', '.item-login', function (e) {
		e.preventDefault();
		if ($('.modal-container').length) return;          // già aperta
		$('body').append(modalMarkup());
	});

	/* Chiude la modale --------------------------------------- */
	$(document).on('click', '.modal-close', function () {
		$(this).closest('.modal-container').remove();
	});

	/* Submit AJAX login -------------------------------------- */
	$(document).on('submit', '#brut-login-form', function (e) {
		e.preventDefault();

		let $form  = $(this),
			$error = $form.find('.modal-error').hide().text('');

		$.post(brutLogin.ajaxurl, $form.serialize())
		 .done(function (resp) {
			if (resp.success) {
				window.location = resp.data.redirect;
			} else {
				$error.text(resp.data).show();
			}
		 })
		 .fail(function () {
			 $error.text('Server error. Try again.').show();
		 });
	});

});
