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

        function sendMessage() {
            const input = document.getElementById('user-input');
            const messagesContainer = document.getElementById('chatbot-messages');
            const userMessageText = input.value.trim();

            if (userMessageText === '') return;

            // Add user message to chat history
            const userMessageBubble = document.createElement('div');
            userMessageBubble.classList.add('message-bubble', 'message-user');
            userMessageBubble.innerHTML = `<p>${userMessageText}</p>`;
            messagesContainer.appendChild(userMessageBubble);

            // Clear input field
            input.value = '';

            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            // Simple AI response placeholder
            setTimeout(() => {
                const aiMessageBubble = document.createElement('div');
                aiMessageBubble.classList.add('message-bubble', 'message-ai');
                aiMessageBubble.innerHTML = `<p>আমি আপনার প্রশ্ন পেয়েছি। আমাদের দল দ্রুতই আপনার সাথে যোগাযোগ করবে।</p>`;
                messagesContainer.appendChild(aiMessageBubble);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 1000);
        }