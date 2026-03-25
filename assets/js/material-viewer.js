(function () {
  'use strict';

  var TRIGGER_SELECTOR = '[data-material-preview="1"]';
  var modalEl = null;
  var modalInstance = null;
  var refs = null;

  function ensureBootstrapModal() {
    return typeof window.bootstrap !== 'undefined' && typeof window.bootstrap.Modal !== 'undefined';
  }

  function createModal() {
    if (modalEl) {
      return;
    }

    var html = '' +
      '<div class="modal fade plei-material-modal" id="pleiMaterialViewer" tabindex="-1" aria-hidden="true">' +
      '  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">' +
      '    <div class="modal-content">' +
      '      <div class="modal-header">' +
      '        <h5 class="modal-title">Vista previa de material</h5>' +
      '        <div class="material-modal-actions">' +
      '          <button type="button" class="btn btn-sm btn-table-edit material-expand-btn">' +
      '            <i class="bi bi-arrows-fullscreen"></i> Expandir' +
      '          </button>' +
      '          <a href="#" class="btn btn-sm btn-table-edit material-download-btn">' +
      '            <i class="bi bi-download"></i> Descargar' +
      '          </a>' +
      '          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>' +
      '        </div>' +
      '      </div>' +
      '      <div class="modal-body">' +
      '        <div class="material-viewer-status">' +
      '          <div class="spinner-border text-light" role="status" aria-hidden="true"></div>' +
      '          <span>Cargando vista previa...</span>' +
      '        </div>' +
      '        <div class="material-viewer-error d-none" role="alert"></div>' +
      '        <div class="material-viewer-shell">' +
      '          <iframe class="material-viewer-frame d-none" title="Vista previa de material" loading="lazy" referrerpolicy="same-origin"></iframe>' +
      '          <img class="material-viewer-image d-none" alt="Vista previa de material" loading="lazy">' +
      '        </div>' +
      '      </div>' +
      '    </div>' +
      '  </div>' +
      '</div>';

    document.body.insertAdjacentHTML('beforeend', html);
    modalEl = document.getElementById('pleiMaterialViewer');
    modalInstance = new window.bootstrap.Modal(modalEl, {
      backdrop: true,
      keyboard: true,
      focus: true
    });

    refs = {
      title: modalEl.querySelector('.modal-title'),
      download: modalEl.querySelector('.material-download-btn'),
      expandBtn: modalEl.querySelector('.material-expand-btn'),
      status: modalEl.querySelector('.material-viewer-status'),
      error: modalEl.querySelector('.material-viewer-error'),
      frame: modalEl.querySelector('.material-viewer-frame'),
      image: modalEl.querySelector('.material-viewer-image')
    };

    refs.expandBtn.addEventListener('click', function () {
      var isExpanded = modalEl.classList.toggle('modal-expanded');
      refs.expandBtn.innerHTML = isExpanded
        ? '<i class="bi bi-fullscreen-exit"></i> Compacto'
        : '<i class="bi bi-arrows-fullscreen"></i> Expandir';
    });

    modalEl.addEventListener('hidden.bs.modal', resetViewer);
  }

  function resetViewer() {
    modalEl.classList.remove('modal-expanded');
    refs.expandBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i> Expandir';
    refs.error.classList.add('d-none');
    refs.error.textContent = '';
    refs.status.classList.remove('d-none');

    refs.frame.classList.add('d-none');
    refs.frame.removeAttribute('src');
    refs.frame.onload = null;

    refs.image.classList.add('d-none');
    refs.image.removeAttribute('src');
    refs.image.onload = null;
    refs.image.onerror = null;
  }

  function showError(message) {
    refs.status.classList.add('d-none');
    refs.error.textContent = message;
    refs.error.classList.remove('d-none');
  }

  function openMaterial(trigger) {
    var src = trigger.getAttribute('href') || '';
    var kind = (trigger.getAttribute('data-preview-kind') || '').toLowerCase();
    var title = trigger.getAttribute('data-preview-title') || 'Vista previa de material';
    var downloadUrl = trigger.getAttribute('data-download-url') || src;

    if (!src || !kind) {
      return;
    }

    resetViewer();
    refs.title.textContent = title;
    refs.download.setAttribute('href', downloadUrl);
    refs.download.setAttribute('target', '_blank');
    refs.download.setAttribute('rel', 'noopener');
    modalInstance.show();

    if (kind === 'pdf') {
      refs.frame.classList.remove('d-none');
      refs.frame.onload = function () {
        refs.status.classList.add('d-none');
      };
      refs.frame.setAttribute('src', src);
      window.setTimeout(function () {
        refs.status.classList.add('d-none');
      }, 900);
      return;
    }

    if (kind === 'jpg' || kind === 'jpeg' || kind === 'png') {
      refs.image.classList.remove('d-none');
      refs.image.onload = function () {
        refs.status.classList.add('d-none');
      };
      refs.image.onerror = function () {
        showError('No se pudo cargar la imagen. Probá con Descargar.');
      };
      refs.image.setAttribute('src', src);
      return;
    }

    showError('Tipo de material no compatible para vista previa.');
  }

  function bindTriggers() {
    var triggers = document.querySelectorAll(TRIGGER_SELECTOR);
    triggers.forEach(function (trigger) {
      trigger.addEventListener('click', function (event) {
        event.preventDefault();
        openMaterial(trigger);
      });
    });
  }

  function init() {
    if (!ensureBootstrapModal()) {
      return;
    }
    createModal();
    bindTriggers();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
