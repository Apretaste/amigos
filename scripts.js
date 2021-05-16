$(document).ready(function() {
	// start components
	$('.tabs').tabs();
	$('.modal').modal();
	$('select').formSelect();

	// set reminder function
	$('.remainder').on('input', function(){
		// get values
		var message = $(this).val().trim();
		var maxlength = $(this).attr('maxlength');
		var counter = $("label[for='" + $(this).attr('id') + "'] span");

		// calculate the reminder
		var remainder = (message.length <= maxlength) ? (maxlength - message.length) : 0;

		// restrict message to maxlength
		if (remainder <= 0) {
			message = message.substring(0, maxlength);
			$(this).val(message);
		}

		// update the counter with the remainder amount
		counter.html(message.length);
	})

	// forms
	$(".ap-form").submit(function(e) {
		e.preventDefault();

		var form = $(this);
		var valid = true;
		var data = getDataForm(form);

		var validator = form.attr('data-validator');
		if (validator)  {
			eval('valid = ' + validator +'(data)');
			if (!valid) return;
		}

		var redirect = form.attr('data-redirect');
		if (typeof redirect === 'undefined') { redirect = true; }
		else redirect = redirect !== 'false';

		var callback = form.attr('data-callback');
		if (typeof callback === 'undefined') callback = null;

		apretaste.send({
			command: form.attr('action'),
			data: data,
			redirect: redirect,
			callback: {name: callback}
		});
	});

	if (asyncAllowed() && typeof page != "undefined") {
		document.addEventListener('scroll', function () {
			var scrollTop = document.documentElement.scrollTop;
			var scrollHeight = document.documentElement.scrollHeight;
			var clientHeight = document.documentElement.clientHeight;

			if (scrollTop + clientHeight >= scrollHeight - 600) {
				loadMoreAsync();
			}
		}, {
			passive: true
		})

		if (page > pages) {
			var loadingText = $('#loadingText')
			loadingText.html('No hay más resultados');
			loadingText.show();
		}
	} else if (typeof page != "undefined") {
		$('#paginationFooter').show();
	}
});


function searchFormValidator(data) {
	return true;
}

function asyncAllowed() {
	return typeof apretaste.connectionMethod != 'undefined' && apretaste.connectionMethod == 'http';
}

var loading = false;

function loadMoreAsync() {
	var searchText = cleanUpSpecialChars($('#buscar').val().toLowerCase());

	if (pages > page && !loading && searchText === '') {
		var command = 'amigos';

		if (title === 'Solicitudes') {
			command += ' waiting';
		} else if (title === 'Bloqueados') {
			command += ' blocked';
		}

		loading = true;
		$('#loadingText').show();

		apretaste.send({
			command: command,
			data: {page: page + 1},
			async: true,
			callback: {name: 'loadMoreCallback'}
		});
	}
}

var currentAsyncResponse = null;

function loadMoreCallback(data, images) {
	page = data.page;
	pages = data.pages;

	var template;

	if (title === 'Solicitudes') {
		data.waiting.forEach(function (user) {
			waiting.push(user);
		});

		template = 'Waiting';

		if (data.waiting.length === 0) {
			pages = page;
		}
	} else if (title === 'Bloqueados') {
		data.blocked.forEach(function (user) {
			blocked.push(user);
		});

		template = 'Blocked';

		if (data.blocked.length === 0) {
			pages = page;
		}
	} else { // friends
		data.friends.forEach(function (user) {
			friends.push(user);
		});

		template = 'Friends';

		if (data.friends.length === 0) {
			pages = page;
		}
	}

	currentAsyncResponse = data;

	apretaste.readServiceFile({
		service: 'amigos',
		path: 'templates/load' + template + 'Template.ejs',
		isTemplate: true,
		callback: 'loadMoreResultsCallback'
	});
}

function loadMoreResultsCallback(content) {
	if (content != null) {
		var renderedResult = ejs.render(content, currentAsyncResponse);
		$('.results').append(renderedResult);

		$('.person-avatar').each(function (i, item) {
			setElementAsAvatar(item);
		});
	}

	currentAsyncResponse = null;
	loading = false;

	var loadingText = $('#loadingText');
	if (page < pages || pages === 0) {
		loadingText.hide();
	} else {
		loadingText.html('No hay más resultados');
	}
}

function nextPage() {
	var command = 'amigos';

	if (title === 'Solicitudes') {
		command += ' waiting';
	} else if (title === 'Bloqueados') {
		command += ' blocked';
	}

	apretaste.send({
		command: command,
		data: {page: page + 1}
	});
}

function previousPage() {
	var command = 'amigos';

	if (title === 'Solicitudes') {
		command += ' waiting';
	} else if (title === 'Bloqueados') {
		command += ' blocked';
	}

	apretaste.send({
		command: command,
		data: {page: page - 1}
	});
}

var currentUser = null;
var currentUsername = null;

function openSearchModal() {
	M.Modal.getInstance($('#searchModal')).open();
	$('#search').focus();
}

function deleteModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#deleteModal')).open();
}

function rejectModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#rejectModal')).open();
}

function cancelRequestModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#cancelRequestModal')).open();
}

function blockModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#blockModal')).open();
}

function unblockModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#unblockModal')).open();
}

function addFriendModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#addFriendModal')).open();
}

function acceptModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#acceptFriendModal')).open();
}

function addFriend(message) {
	apretaste.send({
		command: 'amigos agregar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'addFriendCallback',
			data: message
		}
	});
}

function addFriendCallback(message) {
	showToast(message);

	var waitingCounter = $('#waitingCounter');
	waiting.pop();
	waitingCounter.html(waiting.length + ' ' + (waiting.length > 1 ? 'peticiones' : 'petición'));


	$('#' + currentUser).remove();
}

function deleteFriend() {
	apretaste.send({
		command: 'amigos eliminar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'deleteFriendCallback',
		}
	});
}

function deleteFriendCallback() {
	showToast('Amigo eliminado');
	$('#' + currentUser).remove();

	var friendsCounter = $('#friendsCounter');
	friends.pop();
	friendsCounter.html(friends.length + ' ' + (friends.length > 1 ? 'amigos' : 'amigo'));

}

function rejectFriend(message) {
	apretaste.send({
		command: 'amigos rechazar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'rejectFriendCallback',
			data: {message: message, id: currentUser}
		}
	});
}

function blockUser() {
	apretaste.send({
		command: 'amigos bloquear',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'showToast',
			data: 'Usuario bloqueado'
		}
	});
}

function unblockUser() {
	apretaste.send({
		command: 'amigos desbloquear',
		data: {id: currentUser, username: currentUsername},
		redirect: false,
		callback: {
			name: 'showToast',
			data: 'Usuario desbloqueado'
		}
	});

	$('#' + currentUser).remove();
}

// open search input
function openSearch() {
	$('.filter').removeClass('hide');
	$('#buscar').focus();
}

function closeSearch() {
	$('.filter').addClass('hide');
}

// search for a service on the list
function buscar() {
	// get text to search by
	var text = cleanUpSpecialChars($('#buscar').val().toLowerCase());

	$('.waiting, .friend, .blocked').show().each(function (i, e) {
		// get the caption
		var caption = cleanUpSpecialChars($(e).attr('data-value').toLowerCase());

		// hide if caption does not match
		if (caption.indexOf(text) < 0) {
			$(e).hide();
		}
	})
}

// clean special chars
function cleanUpSpecialChars(str) {
	return str
		.replace(/Á/g, "A").replace(/a/g, "a")
		.replace(/É/g, "E").replace(/é/g, "e")
		.replace(/Í/g, "I").replace(/í/g, "i")
		.replace(/Ó/g, "O").replace(/ó/g, "o")
		.replace(/Ú/g, "U").replace(/ú/g, "u")
		.replace(/Ñ/g, "N").replace(/ñ/g, "n")
		.replace(/[^a-z0-9]/gi, ''); // final clean up
}

function openProfile(id) {
	apretaste.send({command: 'perfil', data: {username: id}});
}

function showToast(text) {
	M.toast({html: text});
}

function setCurrentUsername(username) {
	currentUsername = username;
	$('.username').html('@' + username);
}

function rejectFriendCallback(data) {
	showToast(data.message);
	$('#' + data.id).remove();

	var waitingCounter = $('#waitingCounter');
	waiting.pop();
	waitingCounter.html(waiting.length + ' ' + (waiting.length > 1 ? 'peticiones' : 'petición'));


	var users = $('.waiting, .friend');

	users.hide();
	users.show();
}