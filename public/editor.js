const initialized = new WeakSet();

const initializeFields = (root = document) => {
    root.querySelectorAll('[data-staff-account]').forEach((container) => {
        if (initialized.has(container)) {
            return;
        }
        initialized.add(container);
        const query = container.querySelector('[data-account-query]');
        const button = container.querySelector('[data-account-search]');
        const results = container.querySelector('[data-account-results]');
        const feedback = container.querySelector('[data-account-feedback]');
        button.addEventListener('click', async () => {
            button.disabled = true;
            feedback.textContent = '';
            try {
                const address = new URL(button.dataset.url, window.location.href);
                address.searchParams.set('account_lookup', '1');
                address.searchParams.set('q', query.value);
                const response = await fetch(address, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    throw new Error('Account search failed');
                }
                const envelope = await response.json();
                if (envelope.success !== true || !Array.isArray(envelope.data)) {
                    throw new Error('Account search failed');
                }
                const matches = envelope.data;
                const selected = results.selectedOptions[0];
                [...results.options].slice(1).forEach((option) => {
                    if (option !== selected) {
                        option.remove();
                    }
                });
                matches.forEach((item) => {
                    if (String(item.id) !== selected?.value) {
                        results.add(new Option(`${item.username} (#${item.id})`, String(item.id)));
                    }
                });
            } catch {
                feedback.textContent = button.dataset.error;
            } finally {
                button.disabled = false;
            }
        });
        query.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                button.click();
            }
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initializeFields(), {
        once: true,
    });
} else {
    initializeFields();
}

document.addEventListener('kraft:content-added', (event) => initializeFields(event.target));
