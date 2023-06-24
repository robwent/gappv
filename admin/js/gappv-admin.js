document.addEventListener('DOMContentLoaded', function() {
	const elements = document.querySelectorAll('.gappv-total-views');
	let index = 0;

	function updateElement() {
		if (index < elements.length) {
			const element = elements[index];
			const dataId = element.getAttribute('data-id');
			const dataUpdate = element.getAttribute('data-update');
			if (dataId && dataUpdate === 'true') {
				console.log('Updating ' + dataId);
				element.innerHTML = 'Updating...';
				const xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxurl, true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
				xhr.onload = function() {
					if (this.status === 200) {
						element.innerHTML = this.responseText;
					} else {
						element.innerHTML = 'Error';
					}
					index++;
					updateElement();
				};
				xhr.onerror = function() {
					element.innerHTML = 'Error';
					index++;
					updateElement();
				};
				xhr.send(`action=gappv_ajax_views_update&post_id=${dataId}`);
			} else {
				index++;
				updateElement();
			}
		}
	}

	if (elements.length > 0) {
		updateElement();
	}
});
