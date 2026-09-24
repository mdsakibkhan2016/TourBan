const CHATBOT_API = (window.BASE_URL || '') + '/api/chatbot.php';

async function fetchAIResponse(prompt) {
    const response = await fetch(CHATBOT_API, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ message: prompt })
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Sorry, I cannot answer right now.');
    }

    return data.reply;
}

function toggleChatbot() {
    const modal = document.getElementById('chatbot-modal');
    const overlay = document.getElementById('modal-overlay');
    const icon = document.querySelector('.chatbot-icon');

    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
        overlay.style.display = 'none';
        if (window.innerWidth <= 768) {
            icon.style.display = 'flex';
        }
    } else {
        modal.style.display = 'flex';
        overlay.style.display = 'block';
        if (window.innerWidth <= 768) {
            icon.style.display = 'none';
        }
    }
}

// Helper function for typing effect (uses textContent to prevent XSS)
async function typeText(element, text, chatContainer) {
    element.textContent = '';
    let accumulated = '';
    for (let i = 0; i < text.length; i++) {
        accumulated += text.charAt(i);
        element.textContent = accumulated;
        await new Promise((resolve) => setTimeout(resolve, 5));
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
}

function appendUserMessage(text) {
    const userMessageBubble = document.createElement('div');
    userMessageBubble.classList.add('message-bubble', 'message-user');
    const paragraph = document.createElement('p');
    paragraph.textContent = text;
    userMessageBubble.appendChild(paragraph);
    return userMessageBubble;
}

function sendMessage(event) {
    event.preventDefault();
    const input = document.getElementById('user-input');
    const messagesContainer = document.getElementById('chatbot-messages');
    const userMessageText = input.value.trim();

    if (userMessageText === '') return;

    messagesContainer.appendChild(appendUserMessage(userMessageText));
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    input.value = '';

    const typingIndicator = document.createElement('div');
    typingIndicator.classList.add('message-bubble', 'message-ai');
    const typingText = document.createElement('p');
    typingText.textContent = 'Thinking...';
    typingIndicator.appendChild(typingText);
    messagesContainer.appendChild(typingIndicator);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    const aiMessageBubble = document.createElement('div');

    fetchAIResponse(userMessageText)
        .then((aiResponseText) => {
            typingIndicator.remove();
            aiMessageBubble.classList.add('message-bubble', 'message-ai');
            messagesContainer.appendChild(aiMessageBubble);
            return typeText(aiMessageBubble, aiResponseText, messagesContainer);
        })
        .catch((error) => {
            typingIndicator.remove();
            aiMessageBubble.classList.add('message-bubble', 'message-ai');
            const errorText = document.createElement('p');
            errorText.textContent = error.message || "I'm sorry, I can't answer that question.";
            aiMessageBubble.appendChild(errorText);
            messagesContainer.appendChild(aiMessageBubble);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
}

document.querySelector('.chatbot-input-container').addEventListener('submit', sendMessage);
