const url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
const key = localStorage.getItem('google_api_key') || 'your_api_key';

async function fetchAIResponse(prompt) {
    const requestBody = {
        contents: {
            parts: [
                {
                    text: `You are a travel assistant. Answer the user's question based on the following information:\n\nSearchable Content:\n${window.searchableContent}\n\nPopular Destinations:\n${window.popularDestinations}\n\nUser Question: ${prompt}\n\nRespond as it's user input language.`
                }
            ]
        }
    };
    const response = await fetch(`${url}?key=${key}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestBody)
    });
    const data = await response.json();
    return data.candidates[0].content.parts[0].text;
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

function sendMessage(event) {
    event.preventDefault();
    const input = document.getElementById('user-input');
    const messagesContainer = document.getElementById('chatbot-messages');
    const userMessageText = input.value.trim();

    if (userMessageText === '') return;

    // Add user message to chat history
    const userMessageBubble = document.createElement('div');
    userMessageBubble.classList.add('message-bubble', 'message-user');
    userMessageBubble.innerHTML = `<p>${userMessageText}</p>`;
    messagesContainer.appendChild(userMessageBubble);
    // Scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    // Clear input field
    input.value = '';

    // Simple AI response placeholder
    fetchAIResponse(userMessageText).then((aiResponseText) => {
        const aiMessageBubble = document.createElement('div');
        aiMessageBubble.classList.add('message-bubble', 'message-ai');
        aiMessageBubble.innerHTML = `<p>${aiResponseText || "I'm sorry, I can't answer that question."}</p>`;
        messagesContainer.appendChild(aiMessageBubble);

        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    });
}

document.querySelector('.chatbot-input-container').addEventListener('submit', sendMessage);
