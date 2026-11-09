const form = document.querySelector('[data-staff-ordering]');
const list = form.querySelector('[data-order-list]');
const order = form.elements.order;
const submit = form.querySelector('button[type="submit"]');
const status = form.querySelector('[data-order-status]');
const originalOrder = order.value;
let dragged = null;
let target = null;

const updateOrder = () => {
    const items = [...list.children];
    order.value = JSON.stringify(items.map((item) => item.dataset.orderId));
    submit.disabled = order.value === originalOrder;

    list.querySelectorAll('[data-order-move][aria-disabled="true"]').forEach((button) => {
        button.removeAttribute('aria-disabled');
        button.classList.remove('disabled');
    });
    const firstUp = list.firstElementChild.querySelector('[data-order-move="up"]');
    const lastDown = list.lastElementChild.querySelector('[data-order-move="down"]');
    for (const button of [firstUp, lastDown]) {
        button.setAttribute('aria-disabled', 'true');
        button.classList.add('disabled');
    }
};

const announce = (item) => {
    const name = item.querySelector('[data-order-label]').textContent;
    const position = [...list.children].indexOf(item) + 1;
    status.textContent = form.dataset.moveMessage
        .replace(':position', () => position)
        .replace(':name', () => name);
};

const clearTarget = () => {
    target?.classList.remove('staff-order-before', 'staff-order-after');
    target = null;
};

const finishDrag = () => {
    clearTarget();
    dragged?.classList.remove('opacity-50');
    dragged = null;
};

const insertBefore = (event, item) => {
    const bounds = item.getBoundingClientRect();
    return event.clientY < bounds.top + bounds.height / 2;
};

list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-order-move]');
    if (!button || button.getAttribute('aria-disabled') === 'true') {
        return;
    }
    const item = button.closest('[data-order-id]');
    if (button.dataset.orderMove === 'up') {
        item.previousElementSibling.before(item);
    } else {
        item.nextElementSibling.after(item);
    }
    updateOrder();
    announce(item);
    button.focus();
});

list.addEventListener('dragstart', (event) => {
    const handle = event.target.closest('[data-order-handle]');
    if (!handle) {
        return;
    }
    dragged = handle.closest('[data-order-id]');
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', dragged.dataset.orderId);
    dragged.classList.add('opacity-50');
});

list.addEventListener('dragover', (event) => {
    const item = event.target.closest('[data-order-id]');
    if (!dragged || !item || item === dragged) {
        clearTarget();
        return;
    }
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    if (target !== item) {
        clearTarget();
        target = item;
    }
    const before = insertBefore(event, item);
    item.classList.toggle('staff-order-before', before);
    item.classList.toggle('staff-order-after', !before);
});

list.addEventListener('dragleave', (event) => {
    if (!list.contains(event.relatedTarget)) {
        clearTarget();
    }
});

list.addEventListener('drop', (event) => {
    const item = event.target.closest('[data-order-id]');
    if (!dragged || !item || item === dragged) {
        return;
    }
    event.preventDefault();
    if (insertBefore(event, item)) {
        item.before(dragged);
    } else {
        item.after(dragged);
    }
    updateOrder();
    announce(dragged);
    finishDrag();
});

list.addEventListener('dragend', finishDrag);

updateOrder();
form.querySelectorAll('[data-order-controls]').forEach((control) => control.classList.remove('d-none'));
