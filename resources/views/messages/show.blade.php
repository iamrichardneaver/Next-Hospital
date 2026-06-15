@extends('layouts.app')

@section('title', 'Messages - ' . ($conversation->subject ?? 'Conversation'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Messages Window -->
        <div class="col-12">
            <div class="card">
                <!-- Chat Header -->
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('messages.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                            <div>
                                <h5 class="mb-0">
                                    {{ $conversation->subject ?? $conversation->participants->where('id', '!=', auth()->id())->pluck('name')->join(', ') }}
                                </h5>
                                <small class="text-muted">
                                    {{ $conversation->participants->count() }} participant{{ $conversation->participants->count() > 1 ? 's' : '' }}
                                </small>
                            </div>
                        </div>
                        <div>
                            <!-- Additional actions can be added here -->
                        </div>
                    </div>
                </div>

                <!-- Messages Container -->
                <div class="card-body messages-container" id="messages-container">
                    @foreach($conversation->messages as $message)
                        <div class="message-item {{ $message->sender_id === auth()->id() ? 'sent' : 'received' }}">
                            <div class="message-avatar">
                                @if($message->sender->staffProfile && $message->sender->staffProfile->photo)
                                    <img src="{{ asset('storage/' . $message->sender->staffProfile->photo) }}" alt="{{ $message->sender->name }}">
                                @else
                                    <div class="avatar-text">{{ strtoupper(substr($message->sender->name, 0, 1)) }}</div>
                                @endif
                            </div>
                            <div class="message-content">
                                <div class="message-header">
                                    <strong>{{ $message->sender->name }}</strong>
                                    <small class="text-muted ms-2">{{ $message->created_at->format('M d, Y h:i A') }}</small>
                                </div>
                                <div class="message-body">
                                    @if($message->type === 'image')
                                        <img src="{{ asset('storage/' . $message->file_path) }}" alt="Image" class="img-fluid rounded" style="max-width: 300px;">
                                    @elseif($message->type === 'file')
                                        <a href="{{ asset('storage/' . $message->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark me-2"></i>{{ $message->file_name }}
                                        </a>
                                    @endif
                                    <p class="mb-0">{{ $message->message }}</p>
                                </div>
                                @if($message->is_edited)
                                    <small class="text-muted"><i>Edited {{ $message->edited_at->diffForHumans() }}</i></small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Message Input -->
                <div class="card-footer">
                    <form id="message-form" class="d-flex">
                        @csrf
                        <input type="text" class="form-control me-2" id="message-input" placeholder="Type a message..." required>
                        <button type="button" class="btn btn-outline-secondary me-2" id="attach-file-btn">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i>
                        </button>
                        <input type="file" id="file-input" class="d-none" accept="image/*,.pdf,.doc,.docx">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.messages-container {
    height: 500px;
    overflow-y: auto;
    background-color: #f8f9fa;
}

.message-item {
    display: flex;
    margin-bottom: 1rem;
    padding: 0.5rem;
}

.message-item.received {
    justify-content: flex-start;
}

.message-item.sent {
    justify-content: flex-end;
    flex-direction: row-reverse;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    margin: 0 0.75rem;
}

.message-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-text {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #007bff;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.message-content {
    max-width: 60%;
    background-color: white;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.message-item.sent .message-content {
    background-color: #007bff;
    color: white;
}

.message-item.sent .message-content .text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}

.message-header {
    margin-bottom: 0.5rem;
}

.message-body {
    word-wrap: break-word;
}

.card-footer form {
    margin: 0;
}
</style>
@endpush

@push('scripts')
<script>
const conversationId = {{ $conversation->id }};

// Scroll to bottom on load
document.getElementById('messages-container').scrollTop = document.getElementById('messages-container').scrollHeight;

// Submit message
document.getElementById('message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    sendMessage();
});

// Attach file button
document.getElementById('attach-file-btn').addEventListener('click', function() {
    document.getElementById('file-input').click();
});

// Handle file selection
document.getElementById('file-input').addEventListener('change', function() {
    if (this.files.length > 0) {
        sendMessageWithFile(this.files[0]);
        this.value = ''; // Reset file input
    }
});

function sendMessage() {
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();

    if (!message) return;

    const formData = {
        message: message
    };

    axios.post(`/messages/${conversationId}/send`, formData, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.data.success) {
            messageInput.value = '';
            appendMessage(response.data.data);
            scrollToBottom();
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Failed to send message. Please try again.');
    });
}

function sendMessageWithFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('message', ''); // Empty message for file-only messages

    axios.post(`/messages/${conversationId}/send`, formData, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'multipart/form-data'
        }
    })
    .then(response => {
        if (response.data.success) {
            appendMessage(response.data.data);
            scrollToBottom();
        }
    })
    .catch(error => {
        console.error('Error sending file:', error);
        alert('Failed to send file. Please try again.');
    });
}

function appendMessage(message) {
    const container = document.getElementById('messages-container');
    const messageElement = createMessageElement(message);
    container.appendChild(messageElement);
}

function createMessageElement(message) {
    const div = document.createElement('div');
    div.className = `message-item ${message.sender_id === {{ auth()->id() }} ? 'sent' : 'received'}`;
    
    const senderName = message.sender ? message.sender.name : 'Unknown';
    const senderInitial = senderName.charAt(0).toUpperCase();
    const timestamp = new Date(message.created_at).toLocaleString();
    
    div.innerHTML = `
        <div class="message-avatar">
            <div class="avatar-text">${senderInitial}</div>
        </div>
        <div class="message-content">
            <div class="message-header">
                <strong>${senderName}</strong>
                <small class="text-muted ms-2">${timestamp}</small>
            </div>
            <div class="message-body">
                <p class="mb-0">${message.message}</p>
            </div>
        </div>
    `;
    
    return div;
}

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    container.scrollTop = container.scrollHeight;
}

// Mark conversation as read
axios.post(`/messages/${conversationId}/mark-read`, {}, {
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
});

// Auto-refresh messages every 10 seconds
setInterval(() => {
    // In production, use WebSockets for real-time updates
    // For now, you can implement polling if needed
}, 10000);
</script>
@endpush
@endsection
