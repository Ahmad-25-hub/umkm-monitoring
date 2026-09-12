export const initializeOfflineSales = () => {
    const form = document.querySelector('[data-offline-sales]');

    if (! form) {
        return;
    }

    const items = form.querySelector('[data-sale-items]');
    const addButton = form.querySelector('[data-add-sale-item]');
    const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    let nextIndex = Math.max(...Array.from(items.querySelectorAll('[name]'), (input) => Number(input.name.match(/items\[(\d+)\]/)?.[1] ?? 0))) + 1;
    const update = () => {
        const rows = [...items.querySelectorAll('[data-sale-item]')];
        const total = rows.reduce((sum, row) => {
            const quantity = Number(row.querySelector('[data-item-quantity]').value);
            const price = Number(row.querySelector('[data-item-price]').value);

            return sum + (Number.isFinite(quantity) && Number.isFinite(price) ? Math.max(0, quantity) * Math.max(0, price) : 0);
        }, 0);
        form.querySelector('[data-sale-total]').textContent = formatter.format(total);
        rows.forEach((row) => { row.querySelector('[data-remove-sale-item]').disabled = rows.length === 1; });
        addButton.disabled = rows.length >= 100;
    };

    addButton.addEventListener('click', () => {
        if (items.children.length >= 100) {
            return;
        }
        const fragment = form.querySelector('[data-sale-item-template]').content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replace('__INDEX__', String(nextIndex));
        });
        nextIndex += 1;
        items.append(fragment);
        items.lastElementChild.querySelector('input').focus();
        update();
    });
    items.addEventListener('input', update);
    items.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-sale-item]');
        if (remove && items.children.length > 1) {
            remove.closest('[data-sale-item]').remove();
            update();
        }
    });
    update();
};
