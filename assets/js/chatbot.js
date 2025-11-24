(function () {
    const widget = document.getElementById('chatbot-widget');
    if (!widget) return;

    const toggleBtn = document.getElementById('chatbot-toggle');
    const panel = document.getElementById('chatbot-panel');
    const closeBtn = document.getElementById('chatbot-close');
    const messagesContainer = document.getElementById('chatbot-messages');
    const quickContainer = document.getElementById('chatbot-quick');
    const textarea = document.getElementById('chatbot-text');
    const sendBtn = document.getElementById('chatbot-send');

    const endpoint = window.CHATBOT_ENDPOINT || '../controlador/ChatbotController.php';

    const quickActions = [
        { label: 'Agregar egresado', prompt: '¿Cómo agrego a un nuevo egresado?' },
        { label: 'Subir expediente', prompt: '¿Cómo subo un expediente PDF a un egresado?' },
        { label: 'Generar certificado', prompt: 'Enséñame cómo generar un certificado.' },
        { label: 'Ver estadísticas', prompt: '¿Dónde veo las estadísticas de egresados?' },
        { label: 'Exportar reporte', prompt: 'Necesito exportar el listado a PDF o Excel.' }
    ];

    let loadingIndicator;

    function renderQuickActions() {
        quickContainer.innerHTML = '';
        quickActions.forEach(action => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = action.label;
            btn.addEventListener('click', () => {
                textarea.value = action.prompt;
                sendMessage();
            });
            quickContainer.appendChild(btn);
        });
    }

    function scrollBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function createMessage(role, content) {
        const wrapper = document.createElement('div');
        wrapper.className = `chatbot-message ${role}`;

        const bubble = document.createElement('div');
        bubble.className = 'chatbot-bubble';
        bubble.innerHTML = formatContent(content);
        wrapper.appendChild(bubble);

        messagesContainer.appendChild(wrapper);
        scrollBottom();
    }

    function formatContent(text) {
        const escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');

        return escaped.replace(/\[(.+?)\]\((internal:\/\/[a-zA-Z0-9_\.\-\/]+)\)/g, (_, label, target) => {
            const clean = target.replace('internal://', '');
            return `<a href="#" data-internal-link="${clean}">${label}</a>`;
        });
    }

    function attachLinkHandlers() {
        messagesContainer.addEventListener('click', (ev) => {
            const link = ev.target.closest('[data-internal-link]');
            if (!link) return;
            ev.preventDefault();
            const href = link.getAttribute('data-internal-link');
            if (href) {
                window.location.href = `./${href}`;
            }
        });
    }

    function setLoading(isLoading) {
        if (isLoading) {
            loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'chatbot-loading';
            loadingIndicator.textContent = 'Pensando...';
            messagesContainer.appendChild(loadingIndicator);
            scrollBottom();
        } else if (loadingIndicator) {
            loadingIndicator.remove();
            loadingIndicator = null;
        }
    }

    async function fetchHistory() {
        try {
            const res = await fetch(endpoint);
            const data = await res.json();
            messagesContainer.innerHTML = '';
            if (Array.isArray(data.history) && data.history.length) {
                data.history.forEach(entry => createMessage(entry.role, entry.content));
            } else {
                const empty = document.createElement('div');
                empty.className = 'chatbot-empty-state';
                empty.textContent = 'Hola, ¿en qué puedo ayudarte hoy?';
                messagesContainer.appendChild(empty);
            }
        } catch (err) {
            console.error('Error al cargar historial', err);
        }
    }

    async function bootMessage() {
        try {
            setLoading(true);
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ boot: true })
            });
            const data = await res.json();
            setLoading(false);
            if (data.reply) {
                createMessage('assistant', data.reply);
            }
        } catch (err) {
            setLoading(false);
            console.error('Boot chatbot error', err);
        }
    }

    async function sendMessage() {
        const content = textarea.value.trim();
        if (!content) return;
        textarea.value = '';
        createMessage('user', content);
        setLoading(true);
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: content })
            });
            const data = await res.json();
            setLoading(false);
            if (data.reply) {
                createMessage('assistant', data.reply);
            } else if (data.error) {
                createMessage('assistant', 'Hubo un error: ' + data.error);
            }
        } catch (err) {
            setLoading(false);
            createMessage('assistant', 'No pude comunicarme con la IA local.');
            console.error('Chatbot error', err);
        }
    }

    function togglePanel(show) {
        const shouldShow = typeof show === 'boolean' ? show : panel.classList.contains('hidden');
        if (shouldShow) {
            panel.classList.remove('hidden');
            panel.classList.add('d-flex');
            panel.style.flexDirection = 'column';
            if (!panel.dataset.booted) {
                fetchHistory().then(() => bootMessage());
                panel.dataset.booted = 'true';
            }
        } else {
            panel.classList.add('hidden');
            panel.classList.remove('d-flex');
        }
    }

    toggleBtn.addEventListener('click', () => togglePanel(true));
    closeBtn.addEventListener('click', () => togglePanel(false));
    sendBtn.addEventListener('click', sendMessage);
    textarea.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter' && !ev.shiftKey) {
            ev.preventDefault();
            sendMessage();
        }
    });

    renderQuickActions();
    attachLinkHandlers();
})();
