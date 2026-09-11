(function () {
    'use strict';

    var section = document.getElementById('call-photos');
    if (!section) return;

    var reportId = section.getAttribute('data-report-id');
    var csrfToken = section.getAttribute('data-csrf-token');
    var fileInput = section.querySelector('[data-photo-file]');
    var newComment = section.querySelector('[data-photo-new-comment]');
    var uploadButton = section.querySelector('[data-photo-upload]');
    var status = section.querySelector('[data-photo-status]');
    var count = section.querySelector('[data-photo-count]');
    var grid = section.querySelector('[data-photo-grid]');
    var progress = section.querySelector('[data-photo-progress]');
    var progressBar = section.querySelector('[data-photo-progress-bar]');
    var reportSaveButton = document.querySelector('#call-form button[type="submit"]');
    var busy = false;
    var photos = [];

    try {
        photos = JSON.parse(document.getElementById('call-photo-data').textContent || '[]');
    } catch (error) {
        photos = [];
    }

    function galleryData() {
        return JSON.stringify(photos.map(function (photo) {
            return { url: photo.url, comment: photo.comment || '', group: 'GENERALES' };
        }));
    }

    function setStatus(message, isError) {
        status.textContent = message || '';
        status.classList.toggle('call-photo-status--error', !!isError);
    }

    function setBusy(value) {
        busy = value;
        fileInput.disabled = value || photos.length >= 5;
        newComment.disabled = value || photos.length >= 5;
        uploadButton.disabled = value || !fileInput.files.length || photos.length >= 5;
        if (reportSaveButton) reportSaveButton.disabled = value;
        grid.querySelectorAll('button, textarea').forEach(function (control) {
            control.disabled = value;
        });
    }

    function photoCard(photo, index) {
        var card = document.createElement('article');
        card.className = 'call-photo-card';

        var preview = document.createElement('button');
        preview.type = 'button';
        preview.className = 'call-photo-preview';
        preview.setAttribute('data-photo-gallery', '');
        preview.setAttribute('data-photo-index', String(index));
        preview.setAttribute('aria-label', 'Ampliar fotografía ' + (index + 1));
        var image = document.createElement('img');
        image.src = photo.url;
        image.alt = 'Fotografía general ' + (index + 1);
        image.loading = 'lazy';
        image.addEventListener('error', function () {
            preview.disabled = true;
            preview.removeAttribute('data-photo-gallery');
            preview.textContent = 'Fotografía no disponible';
        });
        preview.appendChild(image);

        var comment = document.createElement('textarea');
        comment.className = 'form-control';
        comment.maxLength = 500;
        comment.rows = 2;
        comment.value = photo.comment || '';
        comment.setAttribute('aria-label', 'Comentario de fotografía ' + (index + 1));

        var actions = document.createElement('div');
        actions.className = 'call-photo-card-actions';
        var save = document.createElement('button');
        save.type = 'button';
        save.className = 'btn btn-sm btn-outline-secondary';
        save.textContent = 'Guardar comentario';
        save.addEventListener('click', function () {
            updateComment(photo.name, comment.value);
        });
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.innerHTML = '<i class="fa fa-trash" aria-hidden="true"></i> Eliminar';
        remove.addEventListener('click', function () {
            if (window.confirm('¿Está seguro de eliminar esta fotografía?')) {
                deletePhoto(photo.name);
            }
        });
        actions.appendChild(save);
        actions.appendChild(remove);
        card.appendChild(preview);
        card.appendChild(comment);
        card.appendChild(actions);
        return card;
    }

    function render() {
        grid.innerHTML = '';
        grid.setAttribute('data-report-photos', galleryData());
        photos.forEach(function (photo, index) {
            grid.appendChild(photoCard(photo, index));
        });
        count.textContent = photos.length + ' / 5';
        if (!photos.length) {
            var empty = document.createElement('p');
            empty.className = 'call-photo-empty';
            empty.textContent = 'Todavía no hay fotografías generales.';
            grid.appendChild(empty);
        }
        setBusy(busy);
    }

    function parseResponse(response) {
        return response.json().catch(function () {
            throw new Error('El servidor devolvió una respuesta inválida.');
        }).then(function (body) {
            if (!response.ok || body.status !== 200) {
                throw new Error(body.message || 'No se pudo completar la operación.');
            }
            return body;
        });
    }

    function request(type, name, comment) {
        var body = new FormData();
        body.append('type', type);
        body.append('id', reportId);
        body.append('csrf_token', csrfToken);
        if (name) body.append('name', name);
        if (typeof comment === 'string') body.append('comment', comment);
        setBusy(true);
        setStatus('Procesando...', false);
        return fetch('process.php', { method: 'POST', body: body, credentials: 'same-origin' })
            .then(parseResponse)
            .then(function (bodyResponse) {
                photos = bodyResponse.photos || [];
                render();
                setStatus(bodyResponse.message, false);
            })
            .catch(function (error) {
                setStatus(error.message, true);
            })
            .then(function () { setBusy(false); });
    }

    function updateComment(name, comment) {
        request('llamada_photo_comment', name, comment);
    }

    function deletePhoto(name) {
        request('llamada_photo_delete', name, '');
    }

    function uploadPhoto() {
        if (busy || !fileInput.files.length || photos.length >= 5) return;
        var body = new FormData();
        body.append('type', 'llamada_photo_upload');
        body.append('id', reportId);
        body.append('csrf_token', csrfToken);
        body.append('comment', newComment.value);
        body.append('photo', fileInput.files[0]);
        var xhr = new XMLHttpRequest();
        setBusy(true);
        setStatus('Subiendo fotografía...', false);
        progress.hidden = false;
        progressBar.style.width = '0%';
        xhr.open('POST', 'process.php');
        xhr.responseType = 'json';
        xhr.upload.addEventListener('progress', function (event) {
            if (event.lengthComputable) {
                progressBar.style.width = Math.round(event.loaded * 100 / event.total) + '%';
            }
        });
        xhr.addEventListener('load', function () {
            var response = xhr.response;
            if (xhr.status < 200 || xhr.status >= 300 || !response || response.status !== 200) {
                setStatus(response && response.message ? response.message : 'No se pudo subir la fotografía.', true);
            } else {
                photos = response.photos || [];
                fileInput.value = '';
                newComment.value = '';
                render();
                setStatus(response.message, false);
            }
        });
        xhr.addEventListener('error', function () {
            setStatus('No se pudo conectar con el servidor.', true);
        });
        xhr.addEventListener('loadend', function () {
            progress.hidden = true;
            setBusy(false);
        });
        xhr.send(body);
    }

    fileInput.addEventListener('change', function () {
        uploadButton.disabled = busy || !fileInput.files.length || photos.length >= 5;
        setStatus(fileInput.files.length ? fileInput.files[0].name : '', false);
    });
    uploadButton.addEventListener('click', uploadPhoto);
    render();
}());
