// The add/edit record form: sends everything in one request with a progress bar.
// Only an enhancement: without this script the form posts normally and the server checks every rule.

const form = document.querySelector('[data-upload-form]');

if (form) {
    const progressBox = document.querySelector('[data-upload-progress]');
    const progressBar = progressBox?.querySelector('progress');
    const percentLabel = progressBox?.querySelector('[data-upload-percent]');
    const errorBox = document.querySelector('[data-upload-errors]');
    let busy = false;

    const setLoading = (on, button) => {
        form.setAttribute('aria-busy', on ? 'true' : 'false');
        form.querySelectorAll('button[type="submit"]').forEach((b) => { b.disabled = on; });
        if (progressBox) progressBox.hidden = !on;
        // Task 7 adds Motion helpers on window.extrack; use them when they exist.
        if (on && button && window.extrack?.loading) window.extrack.loading(button);
    };

    const showErrors = (message, errors = {}) => {
        if (!errorBox) return;
        errorBox.replaceChildren();
        const intro = document.createElement('p');
        intro.textContent = message || 'Please fix the following:';
        errorBox.append(intro);
        const list = document.createElement('ul');
        Object.values(errors).flat().forEach((text) => {
            const item = document.createElement('li');
            item.textContent = text;
            list.append(item);
        });
        if (list.children.length) errorBox.append(list);
        errorBox.hidden = false;
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    form.addEventListener('submit', (event) => {
        if (typeof FormData === 'undefined' || typeof XMLHttpRequest === 'undefined') return; // plain post
        event.preventDefault();
        if (busy) return; // one save at a time

        const button = event.submitter;
        const data = new FormData(form, button);
        // Older browsers ignore FormData's submitter argument; the server needs to know which button.
        if (button?.name && !data.has(button.name)) data.append(button.name, button.value);
        const xhr = new XMLHttpRequest();

        xhr.open('POST', form.action); // _method in the form carries PUT on edit
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.responseType = 'json';

        xhr.upload.addEventListener('progress', (e) => {
            if (!e.lengthComputable || !progressBar) return;
            const pct = Math.round((e.loaded / e.total) * 100);
            progressBar.value = pct;
            if (percentLabel) percentLabel.textContent = `${pct}%`;
        });

        xhr.addEventListener('load', () => {
            busy = false;
            const body = xhr.response || {};
            if (xhr.status >= 200 && xhr.status < 300 && body.data?.redirect) {
                window.location.assign(body.data.redirect);
                return;
            }
            setLoading(false);
            if (xhr.status === 419) {
                showErrors('Your session expired. Reload the page and try again.');
            } else {
                showErrors(body.message || 'Something went wrong. Please try again.', body.errors);
            }
        });

        xhr.addEventListener('error', () => {
            busy = false;
            setLoading(false);
            showErrors('The upload did not reach the server. Check your connection and try again.');
        });

        busy = true;
        setLoading(true, button);
        xhr.send(data);
    });

    // Warn about an oversized file as soon as it is picked (the server still checks).
    const maxBytes = Number(form.dataset.maxMb || 0) * 1024 * 1024;
    form.addEventListener('change', (event) => {
        const input = event.target;
        if (input.type !== 'file' || !maxBytes) return;
        const tooBig = [...input.files].some((f) => f.size > maxBytes);
        input.setCustomValidity(tooBig ? `This file is larger than the ${form.dataset.maxMb} MB limit` : '');
        input.reportValidity();
    });

    // "Please check" marks disappear once the field is edited.
    form.addEventListener('input', (event) => {
        const name = (event.target.name || '').replace('[]', '');
        form.querySelectorAll(`[data-please-check="${CSS.escape(name)}"]`).forEach((mark) => mark.remove());
    });

    // Only show the barangays of the chosen municipality.
    const municipality = form.querySelector('[data-municipality]');
    const barangays = form.querySelector('[data-barangays]');
    const filterBarangays = () => {
        if (!municipality || !barangays) return;
        barangays.querySelectorAll('optgroup').forEach((group) => {
            const match = group.dataset.municipalityId === municipality.value;
            group.hidden = !match;
            group.disabled = !match;
            if (!match) group.querySelectorAll('option').forEach((o) => { o.selected = false; });
        });
    };
    municipality?.addEventListener('change', filterBarangays);
    filterBarangays();

    // Repeatable supporting-file rows.
    const rows = form.querySelector('[data-attachment-rows]');
    const addButton = form.querySelector('[data-add-attachment]');
    if (rows && addButton) {
        addButton.hidden = false;
        addButton.addEventListener('click', () => {
            const template = rows.querySelector('[data-attachment-row]');
            const index = rows.querySelectorAll('[data-attachment-row]').length;
            const row = template.cloneNode(true);
            row.querySelectorAll('[name], [id], label[for]').forEach((el) => {
                ['name', 'id', 'for'].forEach((attr) => {
                    const value = el.getAttribute(attr);
                    if (value) el.setAttribute(attr, value.replace(/attachments([[-])\d+/, `attachments$1${index}`));
                });
                if (el.type === 'file') el.value = '';
            });
            rows.append(row);
        });
    }
}
