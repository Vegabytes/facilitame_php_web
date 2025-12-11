<?php
$currentPage = 'chat';

// Obtener el ID real de la asesoría
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory_row = $stmt->fetch();
if (!$advisory_row) {
    header("Location: /home");
    exit;
}
$advisory_id = $advisory_row['id'];

// Obtener conversaciones
$conversations = get_advisory_conversations($advisory_id);

// Cliente seleccionado (si viene por URL)
$selected_customer_id = isset($_GET['customer']) ? intval($_GET['customer']) : 0;
$selected_customer = null;
$messages = [];

if ($selected_customer_id > 0) {
    // Verificar que el cliente pertenece a esta asesoría
    $stmt = $pdo->prepare("SELECT u.* FROM users u 
                           INNER JOIN customers_advisories ca ON ca.customer_id = u.id 
                           WHERE ca.advisory_id = ? AND u.id = ?");
    $stmt->execute([$advisory_id, $selected_customer_id]);
    $selected_customer = $stmt->fetch();
    
    if ($selected_customer) {
        $messages = get_advisory_messages($advisory_id, $selected_customer_id);
        
        // Marcar mensajes como leídos
        $stmt = $pdo->prepare("UPDATE advisory_messages SET is_read = 1 
                               WHERE advisory_id = ? AND customer_id = ? AND sender_type = 'customer'");
        $stmt->execute([$advisory_id, $selected_customer_id]);
    }
}
?>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content">
        <div class="card card-flush h-100">
            <div class="card-body p-0">
                <div class="chat-container">
                    <!-- Lista de conversaciones -->
                    <div class="chat-sidebar">
                        <div class="chat-sidebar-header">
                            <h4 class="fw-bold mb-0">Conversaciones</h4>
                            <span class="badge badge-light-primary"><?php echo count($conversations); ?></span>
                        </div>
                        <div class="chat-conversations-list">
                            <?php if (empty($conversations)): ?>
                                <div class="empty-conversations">
                                    <i class="ki-outline ki-message-text-2 fs-3x text-muted"></i>
                                    <p class="text-muted mt-3">No hay clientes aún</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <a href="/chat?customer=<?php echo $conv['customer_id']; ?>" 
                                       class="conversation-item <?php echo ($selected_customer_id == $conv['customer_id']) ? 'active' : ''; ?>">
                                        <div class="conversation-avatar">
                                            <?php echo strtoupper(substr($conv['name'], 0, 1) . substr($conv['lastname'], 0, 1)); ?>
                                        </div>
                                        <div class="conversation-info">
                                            <div class="conversation-name">
                                                <?php secho($conv['name'] . ' ' . $conv['lastname']); ?>
                                                <?php if ($conv['unread_count'] > 0): ?>
                                                    <span class="badge badge-danger badge-circle"><?php echo $conv['unread_count']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="conversation-preview">
                                                <?php 
                                                if ($conv['last_message']) {
                                                    echo htmlspecialchars(mb_strimwidth($conv['last_message'], 0, 40, '...'));
                                                } else {
                                                    echo '<span class="text-muted">Sin mensajes</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <?php if ($conv['last_message_at']): ?>
                                            <div class="conversation-time">
                                                <?php echo !empty($conv['last_message_at']) ? date('d/m H:i', strtotime($conv['last_message_at'])) : '-'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Área de chat -->
                    <div class="chat-main">
                        <?php if ($selected_customer): ?>
                            <!-- Header del chat -->
                            <div class="chat-main-header">
                                <div class="d-flex align-items-center">
                                    <div class="chat-customer-avatar">
                                        <?php echo strtoupper(substr($selected_customer['name'], 0, 1) . substr($selected_customer['lastname'], 0, 1)); ?>
                                    </div>
                                    <div class="ms-3">
                                        <h5 class="fw-bold mb-0"><?php secho($selected_customer['name'] . ' ' . $selected_customer['lastname']); ?></h5>
                                        <span class="text-muted fs-7"><?php secho($selected_customer['email']); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mensajes -->
                            <div class="chat-messages" id="chat-messages">
                                <?php if (empty($messages)): ?>
                                    <div class="chat-empty-messages">
                                        <i class="ki-outline ki-message-text-2 fs-3x text-muted"></i>
                                        <p class="text-muted mt-3">Inicia la conversación</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <div class="chat-message <?php echo ($msg['sender_type'] == 'advisory') ? 'sent' : 'received'; ?>">
                                            <div class="message-content">
                                                <?php secho($msg['content']); ?>
                                            </div>
                                            <div class="message-time">
                                                <?php echo fdatetime($msg['created_at']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Input de mensaje -->
                            <div class="chat-input-area">
                                <textarea id="message-input" 
                                          class="chat-textarea" 
                                          placeholder="Escribe tu mensaje..." 
                                          rows="2"></textarea>
                                <button type="button" class="btn btn-primary btn-icon" id="btn-send-message">
                                    <i class="ki-outline ki-send fs-2"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="chat-no-selection">
                                <i class="ki-outline ki-message-text-2 fs-4x text-muted"></i>
                                <h4 class="text-muted mt-4">Selecciona una conversación</h4>
                                <p class="text-gray-500">Elige un cliente de la lista para ver los mensajes</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
<?php if ($selected_customer): ?>
const customerId = <?php echo $selected_customer_id; ?>;

// Scroll al final
function scrollToBottom() {
    const container = document.getElementById('chat-messages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}
scrollToBottom();

// Enviar mensaje
document.getElementById('btn-send-message').addEventListener('click', sendMessage);
document.getElementById('message-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

async function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    const btn = document.getElementById('btn-send-message');
    btn.disabled = true;
    
    try {
        const response = await fetch('/api/advisory-chat-send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `customer_id=${customerId}&message=${encodeURIComponent(message)}`
        });
        
        const result = await response.json();
        
        if (result.status === 'ok') {
            // Añadir mensaje al chat
            const messagesContainer = document.getElementById('chat-messages');
            const emptyState = messagesContainer.querySelector('.chat-empty-messages');
            if (emptyState) emptyState.remove();
            
            const msgDiv = document.createElement('div');
            msgDiv.className = 'chat-message sent';
            msgDiv.innerHTML = `
                <div class="message-content">${escapeHtml(message)}</div>
                <div class="message-time">${new Date().toLocaleString('es-ES', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'})}</div>
            `;
            messagesContainer.appendChild(msgDiv);
            
            input.value = '';
            scrollToBottom();
        } else {
            toastr.error(result.message_html || 'Error al enviar mensaje');
        }
    } catch (error) {
        toastr.error('Error de conexión');
    } finally {
        btn.disabled = false;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Polling para nuevos mensajes (cada 10 segundos)
setInterval(async () => {
    try {
        const response = await fetch(`/api/advisory-chat-messages?customer_id=${customerId}`);
        const result = await response.json();
        if (result.status === 'ok') {
            // Aquí podrías actualizar los mensajes si hay nuevos
        }
    } catch (e) {}
}, 10000);
<?php endif; ?>
</script>

<style>
.chat-container {
    display: flex;
    height: calc(100vh - 200px);
    min-height: 500px;
}

.chat-sidebar {
    width: 320px;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
}

.chat-sidebar-header {
    padding: 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    transition: background 0.2s;
}

.conversation-item:hover {
    background: #f8fafc;
}

.conversation-item.active {
    background: rgba(0, 194, 203, 0.1);
    border-left: 3px solid var(--color-main-facilitame);
}

.conversation-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--color-main-facilitame);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.conversation-info {
    flex: 1;
    margin-left: 0.75rem;
    min-width: 0;
}

.conversation-name {
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.conversation-preview {
    font-size: 0.85rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-time {
    font-size: 0.75rem;
    color: #94a3b8;
    white-space: nowrap;
}

.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-main-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: #fafbfc;
}

.chat-customer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--color-main-facilitame);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    background: #f8fafc;
}

.chat-message {
    max-width: 70%;
    margin-bottom: 1rem;
}

.chat-message.sent {
    margin-left: auto;
}

.chat-message.received {
    margin-right: auto;
}

.message-content {
    padding: 0.75rem 1rem;
    border-radius: 12px;
    word-wrap: break-word;
}

.chat-message.sent .message-content {
    background: var(--color-main-facilitame);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-message.received .message-content {
    background: white;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.message-time {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-top: 0.25rem;
}

.chat-message.sent .message-time {
    text-align: right;
}

.chat-input-area {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
}

.chat-textarea {
    flex: 1;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem;
    resize: none;
    font-size: 0.95rem;
}

.chat-textarea:focus {
    outline: none;
    border-color: var(--color-main-facilitame);
    box-shadow: 0 0 0 3px rgba(0, 194, 203, 0.1);
}

.chat-no-selection, .chat-empty-messages, .empty-conversations {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #94a3b8;
}

@media (max-width: 768px) {
    .chat-sidebar {
        width: 100%;
        display: <?php echo $selected_customer ? 'none' : 'flex'; ?>;
    }
    .chat-main {
        display: <?php echo $selected_customer ? 'flex' : 'none'; ?>;
    }
}
</style>