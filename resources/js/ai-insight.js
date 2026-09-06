export const initializeAiInsight = () => {
    const workspace = document.querySelector('[data-ai-chat]');

    if (! workspace) {
        return;
    }

    const form = workspace.querySelector('[data-ai-form]');
    const input = form.querySelector('[data-ai-input]');
    const submit = form.querySelector('[type="submit"]');
    const messages = workspace.querySelector('[data-ai-messages]');
    const empty = workspace.querySelector('[data-ai-empty]');
    const error = workspace.querySelector('[data-ai-error]');
    const progress = workspace.querySelector('[data-ai-progress]');
    const clear = workspace.querySelector('[data-ai-clear]');
    const suggestions = workspace.querySelectorAll('[data-ai-suggestion]');
    const counter = workspace.querySelector('[data-ai-count]');
    const template = workspace.querySelector('[data-ai-message-template]');
    const configured = ! input.disabled;
    let busy = false;

    const updateCount = () => {
        counter.textContent = input.value.length.toLocaleString('id-ID');
    };

    const setBusy = (value) => {
        busy = value;
        workspace.setAttribute('aria-busy', String(value));
        input.disabled = value || ! configured;
        submit.disabled = value || ! configured;
        clear.disabled = value || ! messages.querySelector('[data-ai-message]');
        suggestions.forEach((button) => { button.disabled = value || ! configured; });
    };

    const appendMessage = (entry) => {
        const element = template.content.firstElementChild.cloneNode(true);
        element.dataset.role = entry.role;
        element.querySelector('[data-message-author]').textContent = entry.role === 'user' ? 'Anda' : 'Nadi';
        element.querySelector('[data-message-text]').textContent = entry.message;
        element.querySelector('[data-message-time]').textContent = entry.time + ' WIB';

        if (entry.source) {
            const link = element.querySelector('[data-message-source]');
            const url = new URL(entry.source.url, window.location.origin);

            if (url.origin === window.location.origin && ['http:', 'https:'].includes(url.protocol)) {
                link.href = url.href;
                link.textContent = entry.source.label;
                link.hidden = false;
            }
        }

        messages.append(element);
        empty.hidden = true;

        while (messages.querySelectorAll('[data-ai-message]').length > 10) {
            messages.querySelector('[data-ai-message]').remove();
        }
    };

    const sendRequest = async (url, method, body) => {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 40000);

        try {
            const response = await fetch(url, {
                method,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                },
                body: JSON.stringify({ ...body, business_id: Number(workspace.dataset.businessId) }),
                signal: controller.signal,
            });

            if (response.redirected || [401, 419].includes(response.status)) {
                throw new Error('Sesi Anda telah berubah atau berakhir. Muat ulang halaman lalu masuk kembali.');
            }

            const result = await response.json().catch(() => null);

            if (! response.ok) {
                const validationMessage = result?.errors ? Object.values(result.errors).flat()[0] : null;
                const fallback = response.status === 429
                    ? 'Terlalu banyak pertanyaan. Tunggu sebentar lalu coba lagi.'
                    : 'Permintaan belum berhasil. Silakan coba lagi.';
                throw new Error(validationMessage || (response.status !== 429 ? result?.message : null) || fallback);
            }

            if (! result) {
                throw new Error('Jawaban belum tersedia. Silakan coba lagi.');
            }

            return result;
        } finally {
            window.clearTimeout(timeout);
        }
    };

    input.addEventListener('input', updateCount);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && ! event.shiftKey && ! event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (busy || ! configured || ! input.value.trim()) {
            return;
        }

        error.hidden = true;
        progress.textContent = 'Nadi sedang memahami pertanyaan Anda…';
        progress.hidden = false;
        setBusy(true);

        try {
            const result = await sendRequest(form.action, 'POST', { message: input.value.trim() });

            if (! Array.isArray(result.messages)) {
                throw new Error('Jawaban belum tersedia. Silakan coba lagi.');
            }

            result.messages.forEach(appendMessage);
            input.value = '';
            updateCount();
            messages.scrollTop = messages.scrollHeight;
        } catch (exception) {
            error.textContent = exception.name === 'AbortError'
                ? 'Layanan AI terlalu lama merespons. Silakan coba lagi.'
                : (exception instanceof TypeError ? 'Koneksi terputus. Periksa jaringan Anda lalu coba lagi.' : exception.message);
            error.hidden = false;
        } finally {
            progress.hidden = true;
            setBusy(false);
            input.focus();
        }
    });

    clear.addEventListener('click', async () => {
        if (busy) {
            return;
        }

        error.hidden = true;
        setBusy(true);

        try {
            await sendRequest(workspace.dataset.clearUrl, 'DELETE', {});
            messages.querySelectorAll('[data-ai-message]').forEach((element) => element.remove());
            empty.hidden = false;
            input.value = '';
            updateCount();
        } catch {
            error.textContent = 'Percakapan belum dapat dihapus. Muat ulang halaman lalu coba lagi.';
            error.hidden = false;
        } finally {
            setBusy(false);
        }
    });

    messages.scrollTop = messages.scrollHeight;
};
