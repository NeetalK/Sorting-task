document.addEventListener('DOMContentLoaded', () => {
    const list = document.querySelector('[data-task-list]');

    if (!list) {
        return;
    }

    let draggedRow = null;
    let originalTaskIds = [];
    const status = document.querySelector('[data-save-status]');

    list.addEventListener('dragstart', (event) => {
        const row = event.target.closest('[data-task-id]');
        if (!row) {
            event.preventDefault();
            return;
        }

        draggedRow = row;
        originalTaskIds = [...list.querySelectorAll('[data-task-id]')]
            .map((task) => Number(task.dataset.taskId));
        row.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', row.dataset.taskId);
    });

    list.addEventListener('dragover', (event) => {
        event.preventDefault();
        const target = event.target.closest('[data-task-id]');
        if (!draggedRow || !target || target === draggedRow) {
            return;
        }

        const midpoint = target.getBoundingClientRect().top + target.offsetHeight / 2;
        list.insertBefore(draggedRow, event.clientY < midpoint ? target : target.nextSibling);
    });

    list.addEventListener('dragend', async () => {
        if (!draggedRow) {
            return;
        }

        draggedRow.classList.remove('is-dragging');
        draggedRow = null;
        const rows = [...list.querySelectorAll('[data-task-id]')];
        rows.forEach((row, index) => {
            row.querySelector('.priority').textContent = String(index + 1);
        });
        status.classList.remove('is-error');
        status.textContent = 'Saving order…';

        try {
            const response = await fetch(list.dataset.reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ task_ids: rows.map((row) => Number(row.dataset.taskId)) }),
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Could not save task order.');
            }

            status.textContent = result.message;
        } catch (error) {
            const rowsById = new Map(rows.map((row) => [Number(row.dataset.taskId), row]));
            originalTaskIds.forEach((taskId) => {
                const row = rowsById.get(taskId);
                if (row) {
                    list.appendChild(row);
                }
            });
            [...list.querySelectorAll('[data-task-id]')].forEach((row, index) => {
                row.querySelector('.priority').textContent = String(index + 1);
            });
            status.classList.add('is-error');
            status.textContent = error.message || 'Could not save task order. Refresh and try again.';
        }

        originalTaskIds = [];
    });
});
