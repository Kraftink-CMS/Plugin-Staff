const initialized = new WeakSet();

const initializeProfile = (form) => {
    if (initialized.has(form)) {
        return;
    }
    initialized.add(form);
    const locale = form.querySelector('[data-profile-locale]');
    const badgeGroups = form.querySelectorAll('[data-badge-locale]');
    const emptyBadges = form.querySelector('[data-badge-empty]');
    const updateBadges = () => {
        let hasBadges = false;
        for (const group of badgeGroups) {
            const active = group.dataset.badgeLocale === locale.value;
            group.hidden = !active;
            for (const input of group.querySelectorAll('input')) {
                input.disabled = !active;
            }
            hasBadges ||= active;
        }
        emptyBadges.hidden = hasBadges;
    };
    locale.addEventListener('change', updateBadges);
    updateBadges();

    const social = form.querySelector('[data-social-links]');
    const rows = social.querySelector('[data-social-rows]');
    const template = social.querySelector('[data-social-template]');
    const add = social.querySelector('[data-social-add]');
    const status = social.querySelector('[data-social-status]');
    const updateLinks = () => {
        const entries = [...rows.children];
        entries.forEach((row, index) => {
            for (const field of row.querySelectorAll('[data-social-field]')) {
                field.name = `social_links[${index}][${field.dataset.socialField}]`;
            }
            row.querySelector('[data-social-move="up"]').disabled = index === 0;
            row.querySelector('[data-social-move="down"]').disabled = index === entries.length - 1;
            row.querySelector('[data-social-remove]').disabled = false;
        });
        add.disabled = entries.length >= Number(social.dataset.maxLinks);
    };
    add.addEventListener('click', () => {
        rows.append(template.content.cloneNode(true));
        updateLinks();
        rows.lastElementChild.querySelector('input').focus();
    });
    rows.addEventListener('click', (event) => {
        const button = event.target.closest('[data-social-move], [data-social-remove]');
        if (!button || button.disabled) {
            return;
        }
        const row = button.closest('[data-social-row]');
        if (button.dataset.socialMove) {
            if (button.dataset.socialMove === 'up') {
                row.previousElementSibling.before(row);
            } else {
                row.nextElementSibling.after(row);
            }
            updateLinks();
            status.textContent = status.dataset.socialStatus
                .replace(':position', [...rows.children].indexOf(row) + 1)
                .replace(':total', rows.children.length);
            (button.disabled ? row.querySelector('[data-social-move]:enabled') : button).focus();
            return;
        }
        const adjacent = row.nextElementSibling || row.previousElementSibling;
        row.remove();
        updateLinks();
        (adjacent?.querySelector('[data-social-remove]') || add).focus();
    });
    updateLinks();
};

const initializeProfiles = (root = document) => {
    if (root.matches?.('[data-staff-profile]')) {
        initializeProfile(root);
    }
    root.querySelectorAll('[data-staff-profile]').forEach(initializeProfile);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initializeProfiles(), { once: true });
} else {
    initializeProfiles();
}

document.addEventListener('kraft:content-added', (event) => initializeProfiles(event.target));
