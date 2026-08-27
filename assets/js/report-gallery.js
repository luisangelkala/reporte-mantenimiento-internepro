(function () {
    'use strict';

    var modal;
    var image;
    var counter;
    var message;
    var previousButton;
    var nextButton;
    var photos = [];
    var currentIndex = 0;
    var touchStartX = null;
    var lastTrigger = null;

    function buildModal() {
        modal = document.createElement('div');
        modal.className = 'report-photo-lightbox';
        modal.hidden = true;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', 'Galería de fotografías del reporte');
        modal.innerHTML =
            '<div class="report-photo-lightbox__panel">' +
                '<button type="button" class="report-photo-lightbox__close" aria-label="Cerrar galería">&times;</button>' +
                '<button type="button" class="report-photo-lightbox__previous" aria-label="Fotografía anterior">&#10094;</button>' +
                '<div class="report-photo-lightbox__content">' +
                    '<img alt="Fotografía ampliada del reporte">' +
                    '<p class="report-photo-lightbox__message" role="status"></p>' +
                    '<div class="report-photo-lightbox__counter" aria-live="polite"></div>' +
                '</div>' +
                '<button type="button" class="report-photo-lightbox__next" aria-label="Fotografía siguiente">&#10095;</button>' +
            '</div>';

        document.body.appendChild(modal);
        image = modal.querySelector('img');
        counter = modal.querySelector('.report-photo-lightbox__counter');
        message = modal.querySelector('.report-photo-lightbox__message');
        previousButton = modal.querySelector('.report-photo-lightbox__previous');
        nextButton = modal.querySelector('.report-photo-lightbox__next');

        modal.querySelector('.report-photo-lightbox__close').addEventListener('click', closeGallery);
        previousButton.addEventListener('click', function () { move(-1); });
        nextButton.addEventListener('click', function () { move(1); });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeGallery();
            }
        });
        modal.addEventListener('touchstart', function (event) {
            touchStartX = event.changedTouches.length ? event.changedTouches[0].clientX : null;
        }, { passive: true });
        modal.addEventListener('touchend', function (event) {
            if (touchStartX === null || !event.changedTouches.length) {
                return;
            }
            var distance = event.changedTouches[0].clientX - touchStartX;
            touchStartX = null;
            if (Math.abs(distance) > 50 && photos.length > 1) {
                move(distance > 0 ? -1 : 1);
            }
        }, { passive: true });
        image.addEventListener('load', function () {
            message.textContent = '';
            image.hidden = false;
        });
        image.addEventListener('error', function () {
            image.hidden = true;
            message.textContent = 'No se pudo cargar esta fotografía.';
        });
    }

    function parsePhotos(trigger) {
        var source = trigger.hasAttribute('data-report-photos')
            ? trigger
            : trigger.closest('[data-report-photos]');
        if (!source) {
            return [];
        }
        try {
            var parsed = JSON.parse(source.getAttribute('data-report-photos'));
            return Array.isArray(parsed) ? parsed.filter(function (url) { return typeof url === 'string'; }) : [];
        } catch (error) {
            return [];
        }
    }

    function showCurrent() {
        if (!photos.length) {
            return;
        }
        image.hidden = true;
        message.textContent = 'Cargando fotografía...';
        image.src = photos[currentIndex];
        counter.textContent = (currentIndex + 1) + ' / ' + photos.length;
        previousButton.hidden = photos.length < 2;
        nextButton.hidden = photos.length < 2;
    }

    function openGallery(trigger) {
        photos = parsePhotos(trigger);
        if (!photos.length) {
            return;
        }
        currentIndex = parseInt(trigger.getAttribute('data-photo-index') || '0', 10);
        if (isNaN(currentIndex) || currentIndex < 0 || currentIndex >= photos.length) {
            currentIndex = 0;
        }
        lastTrigger = trigger;
        modal.hidden = false;
        document.body.classList.add('report-photo-lightbox-open');
        showCurrent();
        modal.querySelector('.report-photo-lightbox__close').focus();
    }

    function closeGallery() {
        if (!modal || modal.hidden) {
            return;
        }
        modal.hidden = true;
        image.removeAttribute('src');
        document.body.classList.remove('report-photo-lightbox-open');
        if (lastTrigger) {
            lastTrigger.focus();
        }
    }

    function move(direction) {
        if (photos.length < 2) {
            return;
        }
        currentIndex = (currentIndex + direction + photos.length) % photos.length;
        showCurrent();
    }

    function prepareThumbnailErrors() {
        document.querySelectorAll('.report-photo-thumbnails img').forEach(function (thumbnail) {
            thumbnail.addEventListener('error', function () {
                var button = thumbnail.closest('button');
                if (!button || button.classList.contains('report-photo-thumbnail--unavailable')) {
                    return;
                }
                button.classList.add('report-photo-thumbnail--unavailable');
                button.disabled = true;
                button.removeAttribute('data-photo-gallery');
                button.innerHTML = '<span>Fotografía no disponible</span>';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        buildModal();
        prepareThumbnailErrors();
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-photo-gallery]');
            if (trigger) {
                event.preventDefault();
                openGallery(trigger);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (modal.hidden) {
                return;
            }
            if (event.key === 'Escape') {
                closeGallery();
            } else if (event.key === 'ArrowLeft') {
                move(-1);
            } else if (event.key === 'ArrowRight') {
                move(1);
            }
        });
    });
})();
