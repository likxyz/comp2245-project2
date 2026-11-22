document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const tbody = document.getElementById('tickets-tbody');
    const countEl = document.getElementById('ticket-count');

    if (!filterButtons.length || !tbody) return;

    filterButtons.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const filter = btn.dataset.filter;

            filterButtons.forEach((b) => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-pressed', 'true');

            try {
                const response = await fetch(`api/tickets.php?filter=${encodeURIComponent(filter)}`);
                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = 'login.php';
                        return;
                    }
                    throw new Error('Failed to fetch tickets');
                }

                const data = await response.json();
                renderTickets(data.tickets, data.count);
            } catch (err) {
                console.error(err);
            }
        });
    });

    function renderTickets(tickets, count) {
        countEl.textContent = `${count} ${count === 1 ? 'ticket' : 'tickets'}`;

        tbody.replaceChildren();

        if (!tickets.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 7;
            td.textContent = 'No tickets found.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        tickets.forEach((ticket) => {
            const tr = document.createElement('tr');

            const idTd = document.createElement('td');
            idTd.textContent = ticket.id;
            tr.appendChild(idTd);

            const titleTd = document.createElement('td');
            const link = document.createElement('a');
            link.href = `ticket.php?id=${encodeURIComponent(ticket.id)}`;
            link.textContent = ticket.title;
            titleTd.appendChild(link);
            tr.appendChild(titleTd);

            const priorityTd = document.createElement('td');
            const prioritySpan = document.createElement('span');
            prioritySpan.className = `badge priority-${ticket.priority.toLowerCase()}`;
            prioritySpan.textContent = ticket.priority;
            priorityTd.appendChild(prioritySpan);
            tr.appendChild(priorityTd);

            const statusTd = document.createElement('td');
            const statusSpan = document.createElement('span');
            statusSpan.className = `badge status-${ticket.status.toLowerCase().replace(/\s+/g, '-')}`;
            statusSpan.textContent = ticket.status;
            statusTd.appendChild(statusSpan);
            tr.appendChild(statusTd);

            const creatorTd = document.createElement('td');
            creatorTd.textContent = ticket.creator_name;
            tr.appendChild(creatorTd);

            const assigneeTd = document.createElement('td');
            assigneeTd.textContent = ticket.assignee_name || 'Nobody';
            tr.appendChild(assigneeTd);

            const dateTd = document.createElement('td');
            dateTd.textContent = ticket.formatted_date;
            tr.appendChild(dateTd);

            tbody.appendChild(tr);
        });
    }
});